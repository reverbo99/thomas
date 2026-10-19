<?php

namespace Tests\Unit;

use App\Services\Sms\FastHubDriver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FastHubDriverTest extends TestCase
{
    private function driver(): FastHubDriver
    {
        return new FastHubDriver(
            'client-123',
            'secret-456',
            'HIGHLINK',
            'https://gw.example.test/send',
            7,
        );
    }

    public function test_it_sends_the_documented_payload_and_returns_the_reference(): void
    {
        Http::fake([
            '*' => Http::response(['status' => true, 'message' => 'Queued', 'data' => []], 200),
        ]);

        $result = $this->driver()->send('255712345678', 'Hello there');

        $this->assertTrue($result->success);
        $this->assertSame('sent', $result->status);
        $this->assertNotNull($result->messageId);
        // Documented minimum reference length is 20 characters.
        $this->assertGreaterThanOrEqual(20, strlen((string) $result->messageId));

        Http::assertSent(function (Request $request) use ($result) {
            $body = $request->data();

            return $request->url() === 'https://gw.example.test/send'
                && $request->method() === 'POST'
                && ($body['auth']['client_id'] ?? null) === 'client-123'
                && ($body['auth']['client_secret'] ?? null) === 'secret-456'
                && ($body['messages'][0]['text'] ?? null) === 'Hello there'
                && ($body['messages'][0]['msisdn'] ?? null) === '255712345678'
                && ($body['messages'][0]['source'] ?? null) === 'HIGHLINK'
                && ($body['messages'][0]['reference'] ?? null) === $result->messageId;
        });
    }

    public function test_it_sends_the_number_with_the_255_country_code(): void
    {
        Http::fake([
            '*' => Http::response(['status' => true, 'message' => 'Queued'], 200),
        ]);

        $expected = [
            '0712345678' => '255712345678',
            '712345678' => '255712345678',
            '+255 712 345 678' => '255712345678',
            '00255712345678' => '255712345678',
            '255712345678' => '255712345678',
        ];

        foreach (array_keys($expected) as $input) {
            $this->driver()->send((string) $input, 'Hello');
        }

        $sent = Http::recorded();

        $this->assertCount(count($expected), $sent);

        foreach (array_values($expected) as $index => $msisdn) {
            $body = $sent[$index][0]->data();
            $this->assertSame($msisdn, $body['messages'][0]['msisdn']);
        }
    }

    public function test_it_rejects_a_destination_without_digits(): void
    {
        Http::fake();

        $result = $this->driver()->send('  ', 'Hello');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Invalid destination', (string) $result->error);
        Http::assertNothingSent();
    }

    public function test_it_reports_a_gateway_rejection(): void
    {
        Http::fake([
            '*' => Http::response(['status' => false, 'message' => 'Invalid credentials', 'data' => []], 200),
        ]);

        $result = $this->driver()->send('255712345678', 'Hello');

        $this->assertFalse($result->success);
        $this->assertSame('Invalid credentials', $result->error);
    }

    public function test_it_surfaces_the_gateway_message_on_a_400_response(): void
    {
        Http::fake([
            '*' => Http::response(['status' => false, 'message' => 'Invalid credentials', 'data' => null], 400),
        ]);

        $result = $this->driver()->send('255712345678', 'Hello');

        $this->assertFalse($result->success);
        $this->assertSame('Invalid credentials', $result->error);
    }

    public function test_it_reports_an_http_failure(): void
    {
        Http::fake([
            '*' => Http::response('upstream error', 503),
        ]);

        $result = $this->driver()->send('255712345678', 'Hello');

        $this->assertFalse($result->success);
        $this->assertSame('HTTP 503', $result->error);
    }

    public function test_it_fails_without_sending_when_no_configuration_is_selected(): void
    {
        Http::fake();

        $driver = new FastHubDriver('', '', 'HIGHLINK', 'https://gw.example.test/send');

        $result = $driver->send('255712345678', 'Hello');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not configured', (string) $result->error);
        Http::assertNothingSent();
    }

    public function test_it_uses_the_configured_default_endpoint_when_the_configuration_has_none(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'ok', 'message' => 'Queued'], 200),
        ]);

        // Blank per-configuration endpoint -> system default from config/services.php.
        $driver = new FastHubDriver('client-123', 'secret-456', 'HIGHLINK', '', 7);

        $result = $driver->send('255712345678', 'Hello');

        $this->assertTrue($result->success);
        Http::assertSent(function (Request $request) {
            return $request->url() === config('services.fasthub.endpoint');
        });
    }

    public function test_it_accepts_an_ok_string_status(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'ok', 'message' => 'Success'], 200),
        ]);

        $result = $this->driver()->send('255712345678', 'Hello');

        $this->assertTrue($result->success);
    }
}
