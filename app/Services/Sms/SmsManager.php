<?php

namespace App\Services\Sms;

use App\Models\Setting;
use App\Models\SmsGateway;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves which SMS gateway to use, sends through it, and records the attempt.
 *
 * Configuration precedence: the admin Settings row wins, and anything blank
 * there falls back to config/services.php (i.e. the .env values). That lets a
 * deployment run purely on .env until an admin fills the form in.
 */
class SmsManager
{
    public const DRIVERS = ['smscotz', 'africastalking', 'fasthub'];

    private static ?array $cachedConfig = null;

    /**
     * Forget the memoised settings — call after the admin saves the form.
     */
    public static function flushConfig(): void
    {
        self::$cachedConfig = null;
    }

    /**
     * @return array{driver: string, sender_id: string, at_username: string, at_api_key: string, at_sandbox: bool, cotz_username: string, cotz_password: string, gateway_id: int|null}
     */
    public function config(): array
    {
        if (self::$cachedConfig !== null) {
            return self::$cachedConfig;
        }

        $settings = $this->settingsRow();
        $services = config('services.sms', []);
        $at = config('services.africastalking', []);

        $pick = function (string $column, $fromEnv) use ($settings) {
            $v = $this->attr($settings, $column);
            if (filled($v)) {
                return trim((string) $v);
            }

            return trim((string) ($fromEnv ?? ''));
        };

        $driver = $pick('sms_driver', $services['driver'] ?? 'smscotz');
        if (!in_array($driver, self::DRIVERS, true)) {
            $driver = 'smscotz';
        }

        $sandbox = $this->attr($settings, 'at_sandbox');

        return self::$cachedConfig = [
            'driver' => $driver,
            'sender_id' => $pick('sms_sender_id', $services['sender_id'] ?? null),
            'at_username' => $pick('at_username', $at['username'] ?? null),
            'at_api_key' => $pick('at_api_key', $at['api_key'] ?? null),
            'at_sandbox' => $sandbox !== null ? (bool) $sandbox : (bool) ($at['sandbox'] ?? true),
            'cotz_username' => $pick('cotz_username', $services['username'] ?? null),
            'cotz_password' => $pick('cotz_password', $services['password'] ?? null),
            'gateway_id' => $this->gatewayId($settings),
        ];
    }

    /**
     * Id of the FastHub configuration selected as default, or null.
     */
    private function gatewayId(?Setting $settings): ?int
    {
        $value = $this->attr($settings, 'sms_gateway_id');

        if (!filled($value)) {
            return null;
        }

        try {
            $gateway = $this->gateway((int) $value);

            return $gateway?->getKey();
        } catch (\Throwable $e) {
            Log::warning('Could not read SMS gateway selection', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Load a saved FastHub configuration by id (null when it no longer exists).
     */
    public function gateway(int $id): ?SmsGateway
    {
        try {
            if ($id <= 0 || !Schema::hasTable('sms_gateways')) {
                return null;
            }

            return SmsGateway::query()->find($id);
        } catch (\Throwable $e) {
            Log::warning('Could not load SMS gateway', ['gateway_id' => $id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function driver(?string $name = null): SmsDriver
    {
        $config = $this->config();
        $name = $name ?: $config['driver'];

        if ($name === 'africastalking') {
            return new AfricasTalkingDriver(
                $config['at_username'],
                $config['at_api_key'],
                $config['sender_id'] ?: null,
                $config['at_sandbox'],
            );
        }

        if ($name === 'fasthub') {
            return $this->fastHubDriver($config);
        }

        return new SmsCoTzDriver(
            $config['cotz_username'],
            $config['cotz_password'],
            $config['sender_id'] ?: 'HIGHLINK',
        );
    }

    /**
     * Build the FastHub driver from the selected configuration.
     *
     * A missing configuration is not an exception: the driver is returned
     * empty and fails the send with an actionable message, so SMS stays a
     * non-fatal side effect everywhere it is used.
     */
    private function fastHubDriver(array $config): FastHubDriver
    {
        $endpoint = (string) config('services.fasthub.endpoint', '');
        $gateway = $this->gateway((int) ($config['gateway_id'] ?? 0));

        if (!$gateway) {
            return new FastHubDriver('', '', $config['sender_id'] ?: 'HIGHLINK', $endpoint);
        }

        try {
            $secret = (string) $gateway->getAttribute('client_secret');
        } catch (\Throwable $e) {
            // A rotated APP_KEY makes the encrypted cast unreadable; fail the
            // send instead of 500-ing the request that triggered it.
            Log::warning('Could not decrypt FastHub client secret', [
                'gateway_id' => $gateway->getKey(),
                'error' => $e->getMessage(),
            ]);
            $secret = '';
        }

        return new FastHubDriver(
            (string) $gateway->client_id,
            $secret,
            $config['sender_id'] ?: 'HIGHLINK',
            trim((string) ($gateway->api_endpoint ?: $endpoint)),
            (int) $gateway->getKey(),
        );
    }

    /**
     * Send one message. Never throws — SMS is always a side effect of some
     * more important operation (a booking, a payment) and must not break it.
     */
    public function send($destination, string $message, ?string $driverName = null): SmsResult
    {
        $driver = $this->driver($driverName);

        $normalised = $driver instanceof AfricasTalkingDriver
            ? PhoneNumber::e164($destination)
            : PhoneNumber::digits($destination);

        if ($normalised === null) {
            Log::warning('SMS skipped: invalid or empty destination', [
                'destination_raw' => (string) $destination,
            ]);

            return SmsResult::fail('Invalid destination number');
        }

        try {
            $result = $driver->send($normalised, $message);
        } catch (\Throwable $e) {
            Log::error('SMS driver threw', [
                'driver' => $driver->name(),
                'destination' => $normalised,
                'error' => $e->getMessage(),
            ]);
            $result = SmsResult::fail($e->getMessage());
        }

        $this->record($driver->name(), $normalised, $message, $result);
        $this->touchFastHubGateway($driver);

        if (!$result->success) {
            Log::info('SMS send failed', [
                'driver' => $driver->name(),
                'destination' => $normalised,
                'error' => $result->error,
            ]);
        }

        return $result;
    }

    /**
     * Backwards-compatible helper: message id on success, false on failure.
     *
     * @return string|false
     */
    public function sendLegacy($destination, string $message)
    {
        $result = $this->send($destination, $message);

        if (!$result->success) {
            return false;
        }

        // Some gateways omit an id; callers only check truthiness / !== false.
        return $result->messageId ?: 'sent';
    }

    /**
     * Remember when the selected FastHub configuration was last used, so the
     * settings screen can show which account is actually live.
     */
    private function touchFastHubGateway(SmsDriver $driver): void
    {
        if (!$driver instanceof FastHubDriver) {
            return;
        }

        $gatewayId = $this->config()['gateway_id'] ?? null;

        if (!$gatewayId) {
            return;
        }

        try {
            SmsGateway::query()->whereKey($gatewayId)->update(['last_used_at' => now()]);
        } catch (\Throwable $e) {
            // Telemetry only — never fail the send over it.
            Log::warning('Could not update FastHub gateway last_used_at', ['error' => $e->getMessage()]);
        }
    }

    private function record(string $driver, string $destination, string $message, SmsResult $result): void
    {        if (!Schema::hasTable('sms_logs')) {
            return;
        }

        try {
            SmsLog::create([
                'driver' => $driver,
                'destination' => $destination,
                'message' => $message,
                'message_id' => $result->messageId,
                'status' => $result->success ? ($result->status ?: 'sent') : 'failed',
                'failure_reason' => $result->error ? mb_substr($result->error, 0, 190) : null,
                'cost' => $result->cost,
                'currency' => $result->currency,
            ]);
        } catch (\Throwable $e) {
            // Logging must never take the send down with it.
            Log::warning('Could not write sms_logs row', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Read one settings column defensively — the encrypted casts throw if the
     * app key has been rotated since the secret was saved, and a broken key
     * should degrade to the .env fallback rather than 500 the whole request.
     */
    private function attr(?Setting $settings, string $column)
    {
        if (!$settings) {
            return null;
        }

        try {
            return $settings->getAttribute($column);
        } catch (\Throwable $e) {
            Log::warning('Could not read SMS setting', ['column' => $column, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function settingsRow(): ?Setting
    {
        try {
            if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'sms_driver')) {
                return null;
            }

            return Setting::query()->first();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
