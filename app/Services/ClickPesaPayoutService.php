<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ClickPesa disbursement (payout) API client.
 *
 * Mobile money: POST /payouts/create-mobile-money-payout
 * Bank:         POST /payouts/create-bank-payout
 * Status:       GET  /payouts/{orderReference}
 * Balance:      GET  /account/balance
 * Bank list:    GET  /list/banks
 *
 * ClickPesa allows only one payout *create* per merchant every 60 seconds,
 * which is why the caller processes a single item per scheduled run.
 */
class ClickPesaPayoutService
{
    private string $baseUrl;
    private ?string $apiKey;
    private ?string $clientId;
    private ?string $checksumKey;
    private string $currency;
    private int $timeout;
    private int $connectTimeout;

    public function __construct()
    {
        $config = (array) config('disbursements.clickpesa', []);

        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.clickpesa.com/third-parties'), '/');
        $this->apiKey = $config['api_key'] ?? null;
        $this->clientId = $config['client_id'] ?? null;
        $this->checksumKey = $config['checksum_key'] ?? null;
        $this->currency = (string) ($config['currency'] ?? 'TZS');
        $this->timeout = (int) ($config['timeout'] ?? 45);
        $this->connectTimeout = (int) ($config['connect_timeout'] ?? 15);
    }

    public function isConfigured(): bool
    {
        return trim((string) $this->apiKey) !== '' && trim((string) $this->clientId) !== '';
    }

    public function currency(): string
    {
        return $this->currency;
    }

    /**
     * Normalize a Tanzanian mobile money MSISDN to 255XXXXXXXXX.
     *
     * @return array{ok: bool, phone: string, error: string|null}
     */
    public function normalizeMsisdn(string $raw): array
    {
        $phone = preg_replace('/\D+/', '', $raw) ?? '';

        if ($phone === '') {
            return ['ok' => false, 'phone' => '', 'error' => 'Missing mobile money number.'];
        }
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }
        if (str_starts_with($phone, '255')) {
            while (strlen($phone) > 12 && isset($phone[3]) && $phone[3] === '0') {
                $phone = substr($phone, 0, 3) . substr($phone, 4);
            }
        } elseif (str_starts_with($phone, '0')) {
            $phone = '255' . substr($phone, 1);
        } else {
            $phone = '255' . $phone;
        }
        while (strlen($phone) > 12 && isset($phone[3]) && $phone[3] === '0') {
            $phone = substr($phone, 0, 3) . substr($phone, 4);
        }
        if (! preg_match('/^255[67]\d{8}$/', $phone)) {
            return [
                'ok' => false,
                'phone' => $phone,
                'error' => 'Invalid Tanzania mobile money number (expected 255 followed by 9 digits).',
            ];
        }

        return ['ok' => true, 'phone' => $phone, 'error' => null];
    }

    public function createMobilePayout(string $orderReference, string $phoneNumber, float $amount, ?string $currency = null): array
    {
        $payload = [
            'amount' => round($amount, 2),
            'phoneNumber' => $phoneNumber,
            'currency' => $currency ?: $this->currency,
            'orderReference' => $orderReference,
        ];

        return $this->post('/payouts/create-mobile-money-payout', $payload);
    }

    public function createBankPayout(
        string $orderReference,
        string $accountNumber,
        string $accountName,
        string $bic,
        float $amount,
        ?string $currency = null
    ): array {
        $payload = [
            'amount' => round($amount, 2),
            'accountNumber' => $accountNumber,
            'accountName' => $accountName,
            'currency' => $currency ?: $this->currency,
            'accountCurrency' => 'TZS',
            'bic' => $bic,
            'orderReference' => $orderReference,
        ];

        return $this->post('/payouts/create-bank-payout', $payload);
    }

    public function previewMobilePayout(string $orderReference, string $phoneNumber, float $amount): array
    {
        return $this->post('/payouts/preview-mobile-money-payout', [
            'amount' => round($amount, 2),
            'phoneNumber' => $phoneNumber,
            'currency' => $this->currency,
            'orderReference' => $orderReference,
        ]);
    }

    public function previewBankPayout(
        string $orderReference,
        string $accountNumber,
        string $accountName,
        string $bic,
        float $amount
    ): array {
        return $this->post('/payouts/preview-bank-payout', [
            'amount' => round($amount, 2),
            'accountNumber' => $accountNumber,
            'accountName' => $accountName,
            'currency' => $this->currency,
            'accountCurrency' => 'TZS',
            'bic' => $bic,
            'orderReference' => $orderReference,
        ]);
    }

    public function queryPayout(string $orderReference): array
    {
        return $this->request('GET', '/payouts/' . rawurlencode($orderReference));
    }

    public function accountBalance(): array
    {
        return $this->request('GET', '/account/balance');
    }

    /**
     * @return array<int, array{name: string, bic: string}>
     */
    public function banks(): array
    {
        return Cache::remember('clickpesa:payout:banks', now()->addDay(), function () {
            $response = $this->request('GET', '/list/banks');

            if (empty($response['ok']) || ! is_array($response['data'])) {
                return [];
            }

            $rows = $response['data'];
            // Some responses wrap the list under "data".
            if (isset($rows['data']) && is_array($rows['data'])) {
                $rows = $rows['data'];
            }

            return collect($rows)
                ->filter(fn ($row) => is_array($row) && ! empty($row['name']) && ! empty($row['bic']))
                ->map(fn ($row) => [
                    'name' => (string) $row['name'],
                    'bic' => (string) $row['bic'],
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Match a human bank name ("CRDB Bank", "NMB", ...) to a BIC from ClickPesa.
     */
    public function resolveBankBic(?string $bankName): ?string
    {
        $bankName = trim((string) $bankName);
        if ($bankName === '') {
            return null;
        }

        // Already a BIC (e.g. CORUTZTZ, NMBMTZTZ).
        if (preg_match('/^[A-Z0-9]{8}([A-Z0-9]{3})?$/', strtoupper($bankName))) {
            return strtoupper($bankName);
        }

        $needle = $this->normalizeBankName($bankName);
        if ($needle === '') {
            return null;
        }

        $aliases = [
            'nmb' => 'NMB',
            'nationalmicrofinance' => 'NMB',
            'crdb' => 'CRDB',
            'nbc' => 'NBC',
            'equity' => 'Equity',
            'gtbank' => 'GTBank',
            'guarantytrustbank' => 'GTBank',
            'stanbic' => 'Stanbic',
            'absa' => 'Absa',
            'barclays' => 'Absa',
            'exim' => 'Exim',
            'tpb' => 'TPB',
            'access' => 'Access',
            'diamond' => 'Diamond',
            'dcb' => 'DCB',
            'azania' => 'Azania',
            'amana' => 'Amana',
            'pbz' => 'PBZ',
            'peoplesbankofzanzibar' => 'PBZ',
            'ecobank' => 'Ecobank',
            'kcb' => 'KCB',
            'standardchartered' => 'Standard Chartered',
            'citibank' => 'Citibank',
            'habib' => 'Habib',
            'mkombozi' => 'Mkombozi',
            'yetu' => 'Yetu',
            'tib' => 'TIB',
            'tnbc' => 'TNBC',
            'ubc' => 'UBC',
        ];

        $alias = null;
        foreach ($aliases as $key => $canonical) {
            if (str_contains($needle, $key)) {
                $alias = $canonical;
                break;
            }
        }

        foreach ($this->banks() as $bank) {
            $haystack = $this->normalizeBankName($bank['name']);

            if ($haystack === $needle) {
                return $bank['bic'];
            }
            if ($alias !== null && str_contains($haystack, $this->normalizeBankName($alias))) {
                return $bank['bic'];
            }
            if (str_contains($haystack, $needle) || str_contains($needle, $haystack)) {
                return $bank['bic'];
            }
        }

        return null;
    }

    public function isSuccessStatus(?string $status): bool
    {
        return in_array(strtoupper(trim((string) $status)), ['SUCCESS', 'COMPLETED', 'PAID'], true);
    }

    public function isFailureStatus(?string $status): bool
    {
        return in_array(strtoupper(trim((string) $status)), ['FAILED', 'REFUNDED', 'REVERSED', 'CANCELLED'], true);
    }

    public function isPendingStatus(?string $status): bool
    {
        return in_array(strtoupper(trim((string) $status)), [
            'AUTHORIZED', 'PROCESSING', 'PENDING', 'QUEUED', 'IN_PROGRESS',
        ], true);
    }

    private function normalizeBankName(string $name): string
    {
        $name = strtolower($name);
        $name = str_replace(['&', 'and'], '', $name);

        return preg_replace('/[^a-z0-9]+/', '', $name) ?? '';
    }

    private function post(string $path, array $payload): array
    {
        if ($this->checksumKey !== null && trim($this->checksumKey) !== '') {
            $payload['checksum'] = $this->createPayloadChecksum($payload);
        }

        return $this->request('POST', $path, $payload);
    }

    /**
     * @return array{ok: bool, http: int, data: array|null, error: string|null, raw: string}
     */
    private function request(string $method, string $path, ?array $payload = null, bool $retryOnUnauthorized = true): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'http' => 0,
                'data' => null,
                'error' => 'ClickPesa payout credentials are not configured (CLICKPESA_API_KEY / CLICKPESA_CLIENT_ID).',
                'raw' => '',
            ];
        }

        $token = $this->token();
        if ($token === null) {
            return [
                'ok' => false,
                'http' => 0,
                'data' => null,
                'error' => 'Unable to obtain a ClickPesa access token.',
                'raw' => '',
            ];
        }

        $url = $this->baseUrl . $path;
        $ch = curl_init();
        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ];

        if (strtoupper($method) === 'POST') {
            $body = json_encode($payload ?? [], JSON_UNESCAPED_SLASHES);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $curlError !== '') {
            Log::error('ClickPesa payout request failed', [
                'method' => $method,
                'path' => $path,
                'error' => $curlError,
            ]);

            return [
                'ok' => false,
                'http' => $httpCode,
                'data' => null,
                'error' => $curlError ?: 'Network error calling ClickPesa.',
                'raw' => '',
            ];
        }

        $data = json_decode((string) $raw, true);
        if (! is_array($data)) {
            $data = null;
        }

        // Expired/invalid token: clear cache and retry once.
        if ($httpCode === 401 && $retryOnUnauthorized) {
            Cache::forget('clickpesa:payout:token');

            return $this->request($method, $path, $payload, false);
        }

        $ok = $httpCode >= 200 && $httpCode < 300;
        $error = null;

        if (! $ok) {
            $error = is_array($data) && isset($data['message'])
                ? (string) $data['message']
                : ('ClickPesa returned HTTP ' . $httpCode);
        }

        Log::info('ClickPesa payout request', [
            'method' => $method,
            'path' => $path,
            'http' => $httpCode,
            'ok' => $ok,
            'error' => $error,
        ]);

        return [
            'ok' => $ok,
            'http' => $httpCode,
            'data' => $data,
            'error' => $error,
            'raw' => (string) $raw,
        ];
    }

    private function token(): ?string
    {
        return Cache::remember('clickpesa:payout:token', now()->addMinutes(50), function () {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/generate-token');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'api-key: ' . $this->apiKey,
                'client-id: ' . $this->clientId,
                'Accept: application/json',
            ]);

            $raw = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($raw === false || $curlError !== '' || $httpCode < 200 || $httpCode >= 300) {
                Log::error('ClickPesa payout token failed', [
                    'http' => $httpCode,
                    'error' => $curlError,
                ]);

                return null;
            }

            $data = json_decode((string) $raw, true);
            $token = null;

            if (is_array($data)) {
                $token = $data['token'] ?? $data['access_token'] ?? null;
                if ($token === null && isset($data[0]['token'])) {
                    $token = $data[0]['token'];
                }
            }

            if (! is_string($token) || trim($token) === '') {
                return null;
            }

            return preg_replace('/^Bearer\s+/i', '', trim($token));
        });
    }

    private function createPayloadChecksum(array $payload): string
    {
        $canonical = $this->canonicalize($payload);

        return hash_hmac(
            'sha256',
            json_encode($canonical, JSON_UNESCAPED_SLASHES) ?: '',
            (string) $this->checksumKey
        );
    }

    private function canonicalize($value)
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_values($value) === $value) {
            return array_map(fn ($item) => $this->canonicalize($item), $value);
        }

        ksort($value);

        return array_map(fn ($item) => $this->canonicalize($item), $value);
    }
}
