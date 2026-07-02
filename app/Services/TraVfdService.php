<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use SimpleXMLElement;
use Exception;

class TraVfdService
{
    protected $env;
    protected $urls;
    protected $tin;
    protected $certSerial;
    protected $password;
    protected $certPath;

    protected $stateFile = 'tra/state.json';

    public function __construct()
    {
        $this->env = config('tra.env', 'test');
        $this->tin = config('tra.tin');
        $this->certSerial = config('tra.cert_serial');
        $this->password = config('tra.password');
        $this->certPath = config('tra.cert_path');
        $this->urls = config('tra.urls.' . $this->env, config('tra.urls.test', []));
    }

    protected function url(string $key): string
    {
        $url = $this->urls[$key] ?? '';
        if ($url === '') {
            throw new Exception("TRA URL not configured for env={$this->env} key={$key}");
        }

        return $url;
    }

    /**
     * Main entry point to fiscalize a booking
     */
    public function fiscalize(Booking $booking)
    {
        try {
            if (!config('tra.enabled', true)) {
                if ($this->shouldMockFiscalization()) {
                    return $this->applyMockFiscalization($booking);
                }
                return true;
            }

            if ($booking->tra_status === 'success') {
                return true;
            }

            if ($this->shouldMockFiscalization()) {
                return $this->applyMockFiscalization($booking);
            }

            // 1. Ensure we have valid token and config
            $this->ensureAuthenticated();

            // 2. Prepare Receipt Data
            $state = $this->getState();
            $rctNum = $state['gc'] + 1; // Increment Global Counter
            $dc = $this->getDailyCounter($state);

            // 3. Construct XML
            $xml = $this->buildReceiptXml($booking, $state, $rctNum, $dc);

            // 4. Sign Payload
            $signedXml = $this->signPayload($xml, 'RCT');

            // 5. Send to TRA
            $response = $this->sendReceiptRequest($signedXml, $state);

            // 6. Process Response
            return $this->handleReceiptResponse($response, $booking, $state, $rctNum, $dc);

        } catch (Exception $e) {
            Log::error("TRA Fiscalization Error (Booking {$booking->id}): " . $e->getMessage());
            if ($this->shouldMockFiscalization()) {
                return $this->applyMockFiscalization($booking);
            }
            $booking->update(['tra_status' => 'failed', 'tra_error' => $e->getMessage()]);
            return false;
        }
    }

    protected function shouldMockFiscalization(): bool
    {
        $settings = Setting::first();
        if (!($settings->test_mode ?? false)) {
            return false;
        }

        if (!config('tra.enabled', true)) {
            return true;
        }

        if (empty($this->certPath) || !is_file($this->certPath)) {
            return true;
        }

        if (empty($this->tin) || empty($this->certSerial) || empty($this->password)) {
            return true;
        }

        return false;
    }

    protected function applyMockFiscalization(Booking $booking): bool
    {
        if ($booking->tra_status === 'success') {
            return true;
        }

        $rctNum = (string) ((int) (Setting::first()->id ?? 1) * 100000 + (int) $booking->id);
        $vnum = 'T' . $rctNum;
        $verifyBase = $this->url('verify');
        $qrUrl = $verifyBase . '/' . $vnum . '_' . date('His');

        $booking->update([
            'tra_status' => 'success',
            'tra_rct_num' => $rctNum,
            'tra_z_num' => date('Ymd'),
            'tra_vnum' => $vnum,
            'tra_qr_url' => $qrUrl,
            'tra_response' => 'test_mode_mock',
            'tra_error' => null,
        ]);

        Log::info("TRA mock fiscalization applied (test mode) for booking {$booking->id}");

        return true;
    }

    // --- Authentication & State Management ---

    protected function ensureAuthenticated()
    {
        $state = $this->getState();

        // Stale state after switching TIN, TRA_ENV, or certificate causes bogus tokens / registration errors
        if (!empty($state['username']) && $this->stateConfigMismatch($state)) {
            Log::warning('TRA state.json does not match current TRA_TIN / TRA_ENV — deleting state and re-registering.');
            Storage::delete($this->stateFile);
            $state = $this->getState();
        }

        // If not registered, register first
        if (empty($state['username'])) {
            $this->register();
            $state = $this->getState(); // Reload state
        }

        // Check Token Expiry
        if (empty($state['token']) || $state['token_expires_at'] < time()) {
            $this->requestToken($state);
        }
    }

    protected function httpClient()
    {
        $options = [
            'timeout' => (int) config('tra.timeout', 60),
            'connect_timeout' => (int) config('tra.connect_timeout', 20),
        ];
        if (!config('tra.verify_ssl', true)) {
            $options['verify'] = false;
        }

        return Http::withOptions($options);
    }

    protected function register()
    {
        if ($this->tin === null || $this->tin === '' || $this->certSerial === null || $this->certSerial === '') {
            throw new Exception(
                'TRA_TIN and TRA_CERT_SERIAL must be set in .env. They must match your TRA VFD registration.'
            );
        }

        $payload = '<REGDATA><TIN>' . $this->tin . '</TIN><CERTKEY>' . $this->certSerial . '</CERTKEY></REGDATA>';
        $signature = $this->signData($payload);

        $xml = '<?xml version="1.0"?><EFDMS><REGDATA><TIN>' . $this->tin . '</TIN><CERTKEY>' . $this->certSerial . '</CERTKEY></REGDATA><EFDMSSIGNATURE>' . $signature . '</EFDMSSIGNATURE></EFDMS>';

        $url = $this->url('register');

        Log::info("TRA Registration Request to $url", [
            'tin' => $this->tin,
            'certkey' => $this->certSerial,
            'cert_serial_header_len' => strlen($certSerialHeader = $this->buildCertSerialHeader()),
        ]);

        $response = $this->httpClient()->withHeaders([
            'Content-Type' => 'application/xml',
            'Cert-Serial' => $certSerialHeader,
            'Client' => config('tra.client', 'webapi'),
        ])->send('POST', $url, ['body' => $xml]);

        if ($response->failed()) {
            Log::error('TRA Registration HTTP failed', ['body' => $response->body(), 'status' => $response->status()]);
            $this->throwRegistrationFailure(
                'Registration HTTP Failed: ' . $response->body(),
                $certSerialHeader
            );
        }

        Log::info('TRA Registration response', ['body' => $response->body()]);

        $xmlResp = simplexml_load_string($response->body());
        if (!$xmlResp || !isset($xmlResp->EFDMSRESP)) {
            throw new Exception('Registration response is not valid XML: ' . $response->body());
        }
        if ((string) $xmlResp->EFDMSRESP->ACKCODE !== '0') {
            $this->throwRegistrationFailure(
                'Registration API Failed: ' . (string) $xmlResp->EFDMSRESP->ACKMSG,
                $certSerialHeader
            );
        }

        // Save Registration Data
        $data = $xmlResp->EFDMSRESP;
        $this->updateState([
            'username' => (string) $data->USERNAME,
            'password' => (string) $data->PASSWORD, // API pwd
            'receipt_code' => (string) $data->RECEIPTCODE,
            'routing_key' => (string) $data->ROUTINGKEY,
            'reg_id' => (string) $data->REGID,
            'gc' => (int) $data->GC,
            'registered_at' => now()->toIso8601String(),
            'tra_tin' => (string) $this->tin,
            'tra_env' => (string) $this->env,
        ]);

        Log::info("TRA Registration Successful");
    }

    protected function requestToken($state)
    {
        $url = $this->url('token');

        $response = $this->httpClient()->asForm()->post($url, [
            'username' => $state['username'],
            'password' => $state['password'],
            'grant_type' => 'password'
        ]);

        if ($response->failed()) {
            throw new Exception("Token Request Failed: " . $response->body());
        }

        $data = $response->json();
        if (!isset($data['access_token'])) {
            throw new Exception("Token not found in response");
        }

        $this->updateState([
            'token' => $data['access_token'],
            'token_expires_at' => time() + ($data['expires_in'] ?? 3600) - 60 // Buffer
        ]);
    }

    // --- Receipt Construction ---

    protected function buildReceiptXml(Booking $booking, $state, $rctNum, $dc)
    {
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $znum = date('Ymd');

        $custIdType = '6'; // NIL — bus passengers rarely provide TIN at booking
        $custId = '';
        $custName = htmlspecialchars($booking->customer_name ?? 'Costumer');
        $mobile = $this->normalizePhone($booking->customer_phone);

        $rctVNum = $state['receipt_code'] . $rctNum; // Verification Num: CODE + GC

        $amount = number_format($booking->amount, 2, '.', '');
        $taxCode = '1'; // Standard Rate 18% - Adjust logic if needed (e.g. buses might be exempt?)
        // Calculate Net and Tax based on 18%
        // Gross = Net * 1.18 -> Net = Gross / 1.18
        $net = number_format($amount / 1.18, 2, '.', '');
        $tax = number_format($amount - $net, 2, '.', '');

        $xml = "<RCT><DATE>$date</DATE><TIME>$time</TIME><TIN>{$this->tin}</TIN><REGID>{$state['reg_id']}</REGID><EFDSERIAL>{$this->certSerial}</EFDSERIAL><CUSTIDTYPE>$custIdType</CUSTIDTYPE><CUSTID>$custId</CUSTID><CUSTNAME>$custName</CUSTNAME><MOBILENUM>$mobile</MOBILENUM><RCTNUM>$rctNum</RCTNUM><DC>$dc</DC><GC>$rctNum</GC><ZNUM>$znum</ZNUM><RCTVNUM>$rctVNum</RCTVNUM><ITEMS><ITEM><ID>1</ID><DESC>Transport Ticket {$booking->booking_code}</DESC><QTY>1</QTY><TAXCODE>$taxCode</TAXCODE><AMT>$amount</AMT></ITEM></ITEMS><TOTALS><TOTALTAXEXCL>$net</TOTALTAXEXCL><TOTALTAXINCL>$amount</TOTALTAXINCL><DISCOUNT>0.00</DISCOUNT></TOTALS><PAYMENTS><PMTTYPE>EMONEY</PMTTYPE><PMTAMOUNT>$amount</PMTAMOUNT></PAYMENTS><VATTOTALS><VATRATE>A</VATRATE><NETTAMOUNT>$net</NETTAMOUNT><TAXAMOUNT>$tax</TAXAMOUNT></VATTOTALS></RCT>";

        return $xml;
    }

    protected function sendReceiptRequest($signedXml, $state)
    {
        $url = $this->url('receipt');

        $response = $this->httpClient()->withHeaders([
            'Content-Type' => 'application/xml',
            'Routing-Key' => $state['routing_key'],
            'Cert-Serial' => $this->buildCertSerialHeader(),
            'Client' => config('tra.client', 'webapi'),
            'Authorization' => 'bearer ' . $state['token'],
        ])->send('POST', $url, ['body' => $signedXml]);

        return $response;
    }

    protected function handleReceiptResponse($response, $booking, $state, $rctNum, $dc)
    {
        if ($response->failed()) {
            throw new Exception("Receipt API HTTP Error: " . $response->body());
        }

        $xml = simplexml_load_string($response->body());
        $ackCode = (string) $xml->RCTACK->ACKCODE;

        if ($ackCode === '0') {
            // Success
            $this->updateState([
                'gc' => $rctNum, // Confirm GC increment
                'last_dc' => $dc,
                'last_znum' => date('Ymd')
            ]);

            $rctVNum = $state['receipt_code'] . $rctNum;

            // Build QR Link
            $verifyBase = $this->url('verify');
            $timeStr = date('His');
            $qrUrl = "$verifyBase/{$rctVNum}_{$timeStr}";

            $booking->update([
                'tra_status' => 'success',
                'tra_rct_num' => $rctNum,
                'tra_z_num' => date('Ymd'),
                'tra_vnum' => $rctVNum,
                'tra_qr_url' => $qrUrl,
                'tra_response' => $response->body()
            ]);

            Log::info("TRA Receipt Success for Booking {$booking->id}");
            return true;
        } else {
            // Logic Error from TRA
            $msg = (string) $xml->RCTACK->ACKMSG;
            Log::warning("TRA Receipt Rejected (Booking {$booking->id}): $msg");
            $booking->update(['tra_status' => 'rejected', 'tra_error' => $msg]);
            return false;
        }
    }

    // --- Helper Functions ---

    protected function signPayload($xmlContent, $tag = 'RCT')
    {
        $signature = $this->signData($xmlContent);

        $wrapped = "<?xml version='1.0' encoding='UTF-8'?><EFDMS>$xmlContent<EFDMSSIGNATURE>$signature</EFDMSSIGNATURE></EFDMS>";
        return $wrapped;
    }

    protected function signData($data)
    {
        $certs = $this->getCertDetails();
        $privateKey = $certs['pkey'];

        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        return base64_encode($signature);
    }

    protected function getCertDetails()
    {
        if (empty($this->certPath) || !file_exists($this->certPath)) {
            throw new Exception(
                "TRA certificate file not found. Set TRA_CERT_PATH in .env to your .pfx file path (e.g. full path to certificate.pfx). " .
                "Current value: " . ($this->certPath ?: '(empty)')
            );
        }

        $certStore = file_get_contents($this->certPath);
        if ($certStore === false) {
            throw new Exception("Could not read certificate file: " . $this->certPath);
        }

        if (empty($this->password)) {
            throw new Exception("TRA certificate password is not set. Set TRA_PASSWORD in .env to the password for your .pfx file.");
        }

        $certInfo = null;
        if (!openssl_pkcs12_read($certStore, $certInfo, $this->password)) {
            $hint = "Check: (1) TRA_PASSWORD in .env matches the .pfx password, (2) the file is a valid PKCS#12 (.pfx) certificate.";
            throw new Exception("Failed to read certificate. " . $hint);
        }

        return $certInfo;
    }

    /**
     * HTTP Cert-Serial header per TRA VFD API:
     * base64-encode the certificate serial hex string (see tra-docs + golang tra-vfd).
     * Set TRA_CERT_SERIAL_HEADER_BASE64 if TRA supplied a fixed override value.
     */
    protected function buildCertSerialHeader(): string
    {
        $override = config('tra.cert_serial_header_base64');
        if ($override !== null && $override !== '') {
            return trim((string) $override);
        }

        $hex = $this->normalizeCertSerialHex($this->extractSerialHexFromPfx());
        $mode = config('tra.cert_serial_header_mode', 'hex_string');

        if ($mode === 'hex_bytes') {
            $binary = hex2bin($hex);
            if ($binary === false || $binary === '') {
                throw new Exception('Could not encode TRA Cert-Serial from certificate hex bytes.');
            }

            return base64_encode($binary);
        }

        return base64_encode($hex);
    }

    protected function normalizeCertSerialHex(string $hex): string
    {
        $hex = preg_replace('/^0x/i', '', $hex);
        $hex = str_replace([':', ' ', '-'], '', $hex);
        $hex = strtolower($hex);
        if ($hex === '' || !ctype_xdigit($hex)) {
            throw new Exception('TRA certificate serial is not valid hexadecimal (check TRA_CERT_PATH / .pfx).');
        }
        if (strlen($hex) % 2 === 1) {
            $hex = '0' . $hex;
        }

        return $hex;
    }

    protected function extractSerialHexFromPfx(): string
    {
        $details = $this->getCertDetails();
        $cert = openssl_x509_parse($details['cert']);
        if ($cert === false) {
            throw new Exception('Could not parse TRA .pfx certificate for serial number.');
        }

        $hex = $cert['serialNumberHex'] ?? null;
        if (!empty($hex)) {
            return (string) $hex;
        }

        // Large serials: PHP may omit serialNumberHex; derive hex from decimal serialNumber
        $dec = $cert['serialNumber'] ?? null;
        if ($dec !== null && $dec !== '' && function_exists('gmp_strval')) {
            $hex = gmp_strval(gmp_init((string) $dec, 10), 16);
            if (strlen($hex) % 2 === 1) {
                $hex = '0' . $hex;
            }

            return $hex;
        }

        throw new Exception(
            'Certificate serial could not be read. Install PHP gmp extension or set TRA_CERT_SERIAL_HEADER_BASE64 (see config/tra.php).'
        );
    }

    protected function stateConfigMismatch(array $state): bool
    {
        $savedTin = $state['tra_tin'] ?? null;
        $savedEnv = $state['tra_env'] ?? null;
        if ($savedTin === null && $savedEnv === null) {
            return false;
        }

        return (string) $savedTin !== (string) $this->tin || (string) $savedEnv !== (string) $this->env;
    }

    /**
     * @throws Exception
     */
    protected function throwRegistrationFailure(string $message, string $certSerialHeader): void
    {
        $hint = '';
        if (stripos($message, 'Cert-Serial') !== false || stripos($message, 'certificate not found') !== false) {
            $hint = ' Check: (1) .pfx is the one TRA issued for this TIN on ' . ($this->env === 'production' ? 'production' : 'test') .
                ' VFD; (2) TRA_CERT_SERIAL matches the EFD serial on that cert; (3) set TRA_CERT_SERIAL_HEADER_BASE64 if TRA supplied it; ' .
                '(4) delete storage/app/tra/state.json after fixing env vars; (5) temporarily TRA_ENABLED=false if you must accept payments without TRA.';
        }

        throw new Exception($message . $hint . ' Cert-Serial header length (base64): ' . strlen($certSerialHeader) . '.');
    }

    protected function getState()
    {
        if (Storage::exists($this->stateFile)) {
            return json_decode(Storage::get($this->stateFile), true);
        }
        return ['gc' => 0];
    }

    protected function updateState($updates)
    {
        $state = $this->getState();
        $newState = array_merge($state, $updates);
        Storage::put($this->stateFile, json_encode($newState, JSON_PRETTY_PRINT));
    }

    protected function getDailyCounter($state)
    {
        $today = date('Ymd');
        if (($state['last_znum'] ?? '') !== $today) {
            return 1; // Reset DC for new day
        }
        return ($state['last_dc'] ?? 0) + 1;
    }

    protected function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10 && strpos($phone, '0') === 0) {
            return '255' . substr($phone, 1);
        }
        return $phone;
    }
}
