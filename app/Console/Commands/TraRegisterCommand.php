<?php

namespace App\Console\Commands;

use App\Services\TraVfdService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TraRegisterCommand extends Command
{
    protected $signature = 'tra:register {--show-headers : Print computed Cert-Serial header modes}';

    protected $description = 'Test TRA VFD registration only (no booking fiscalization)';

    public function handle(): int
    {
        if ($this->option('show-headers')) {
            $this->printHeaderModes();
        }

        if (Storage::exists('tra/state.json')) {
            Storage::delete('tra/state.json');
            $this->warn('Deleted storage/app/tra/state.json');
        }

        $booking = new \App\Models\Booking([
            'id' => 0,
            'booking_code' => 'TRA-REGISTER-TEST',
            'amount' => 1,
            'customer_name' => 'TRA Register Test',
            'payment_status' => 'Paid',
            'tra_status' => 'pending',
        ]);

        $tra = new TraVfdService();
        $ok = $tra->fiscalize($booking);

        if ($ok && Storage::exists('tra/state.json')) {
            $state = json_decode(Storage::get('tra/state.json'), true);
            $this->info('Registration successful.');
            $this->line('REGID: ' . ($state['reg_id'] ?? 'n/a'));
            $this->line('RECEIPTCODE: ' . ($state['receipt_code'] ?? 'n/a'));
            $this->line('USERNAME: ' . ($state['username'] ?? 'n/a'));
            $this->line('GC: ' . ($state['gc'] ?? 'n/a'));
            return self::SUCCESS;
        }

        $this->error('Registration failed. Check storage/logs/laravel.log for TRA response body.');
        return self::FAILURE;
    }

    private function printHeaderModes(): void
    {
        $path = config('tra.cert_path');
        $password = config('tra.password');
        $store = @file_get_contents($path);
        if ($store === false) {
            $this->error('Cannot read cert: ' . $path);
            return;
        }
        $info = [];
        if (!openssl_pkcs12_read($store, $info, $password)) {
            $this->error('Cannot open PFX — check TRA_PASSWORD');
            return;
        }
        $cert = openssl_x509_parse($info['cert']);
        $hex = strtolower($cert['serialNumberHex'] ?? '');
        $this->table(['Mode', 'Value', 'Length'], [
            ['hex_string (default)', base64_encode($hex), strlen(base64_encode($hex))],
            ['hex_bytes (legacy)', base64_encode(hex2bin($hex) ?: ''), strlen(base64_encode(hex2bin($hex) ?: ''))],
            ['certkey literal', base64_encode((string) config('tra.cert_serial')), strlen(base64_encode((string) config('tra.cert_serial')))],
        ]);
    }
}
