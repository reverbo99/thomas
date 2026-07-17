<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Firebase Cloud Messaging (HTTP v1) sender.
 *
 * Uses the Admin SDK service-account JSON to mint a short-lived OAuth2 access
 * token (JWT signed with the account's private key), then posts messages to
 * the FCM v1 endpoint. No extra Composer package required.
 */
class FcmService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    private ?array $credentials = null;

    private Client $http;

    public function __construct(?Client $http = null)
    {
        if ($http !== null) {
            $this->http = $http;

            return;
        }

        $options = ['timeout' => 15];
        // Optional CA bundle (e.g. for local dev environments whose php.ini
        // has no curl.cainfo). Leave unset in production to use system certs.
        $caBundle = config('services.fcm.ca_bundle');
        if ($caBundle && is_file($caBundle)) {
            $options['verify'] = $caBundle;
        }
        $this->http = new Client($options);
    }

    /**
     * Send a notification to every registered device for the given user.
     * Returns the number of devices successfully delivered to.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], ?string $app = null): int
    {
        $query = DeviceToken::where('user_id', $user->id);
        if ($app !== null) {
            $query->where('app', $app);
        }
        $tokens = $query->pluck('token')->all();

        if (empty($tokens)) {
            return 0;
        }

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send to a single device token. Returns true on success. Prunes the
     * token from the DB when FCM reports it is invalid/unregistered.
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return false;
        }

        // FCM data payload values must all be strings.
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'high_importance_channel',
                        'sound' => 'default',
                    ],
                ],
            ],
        ];

        try {
            $this->http->post(
                'https://fcm.googleapis.com/v1/projects/' . $this->projectId() . '/messages:send',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $message,
                ]
            );

            DeviceToken::where('token', $token)->update(['last_used_at' => now()]);

            return true;
        } catch (ClientException $e) {
            $status = $e->getResponse()?->getStatusCode();
            // 404 UNREGISTERED / 400 INVALID_ARGUMENT for a bad token → prune it.
            if (in_array($status, [400, 404], true)) {
                DeviceToken::where('token', $token)->delete();
            }
            Log::warning('FCM send failed', [
                'status' => $status,
                'body' => (string) $e->getResponse()?->getBody(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('FCM send error: ' . $e->getMessage());

            return false;
        }
    }

    private function projectId(): string
    {
        return (string) (config('services.fcm.project_id') ?: ($this->loadCredentials()['project_id'] ?? ''));
    }

    /**
     * Get (and cache) a Google OAuth2 access token for the FCM scope.
     */
    private function accessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3300, function (): ?string {
            $creds = $this->loadCredentials();
            if ($creds === null) {
                return null;
            }

            $now = time();
            $jwt = $this->signJwt([
                'iss' => $creds['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_URI,
                'iat' => $now,
                'exp' => $now + 3600,
            ], $creds['private_key']);

            if ($jwt === null) {
                return null;
            }

            try {
                $response = $this->http->post(self::TOKEN_URI, [
                    'form_params' => [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion' => $jwt,
                    ],
                ]);
                $payload = json_decode((string) $response->getBody(), true);

                return $payload['access_token'] ?? null;
            } catch (\Throwable $e) {
                Log::error('FCM token exchange failed: ' . $e->getMessage());

                return null;
            }
        });
    }

    /**
     * RS256-sign a JWT claim set with the service-account private key.
     */
    private function signJwt(array $claims, string $privateKey): ?string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($claims)),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $privateKey, 'sha256WithRSAEncryption')) {
            Log::error('FCM JWT signing failed');

            return null;
        }
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Load and cache the service-account credentials from disk.
     */
    private function loadCredentials(): ?array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = config('services.fcm.credentials');
        if (! $path || ! is_file($path)) {
            Log::error('FCM credentials file not found at: ' . $path);

            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded) || empty($decoded['private_key']) || empty($decoded['client_email'])) {
            Log::error('FCM credentials file is invalid.');

            return null;
        }

        return $this->credentials = $decoded;
    }
}
