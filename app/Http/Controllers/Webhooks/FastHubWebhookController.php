<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\SmsGateway;
use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Delivery-status callback for the FastHub Bulk SMS Gateway.
 *
 * Register the URL shown in Settings > SMS Gateway (FastHub configurations) in
 * the FastHub portal. FastHub posts:
 *
 *   {
 *     "statuses": [
 *       { "status": "...", "reference": "...", "description": "...", "msisdn": "..." }
 *     ]
 *   }
 *
 * `reference` is the UUID we sent in the outbound message and stored as
 * sms_logs.message_id. Always answer 200 so FastHub does not keep retrying a
 * report we can never match.
 */
class FastHubWebhookController extends Controller
{
    public function status(Request $request, SmsGateway $gateway)
    {
        $statuses = $request->input('statuses', []);

        if (!is_array($statuses)) {
            return response()->json(['status' => 'Ok'], 200);
        }

        foreach ($statuses as $row) {
            if (!is_array($row)) {
                continue;
            }

            $reference = trim((string) ($row['reference'] ?? ''));

            if ($reference === '') {
                continue;
            }

            $log = SmsLog::query()->where('message_id', $reference)->first();

            if (!$log) {
                Log::info('FastHub status callback for unknown message', [
                    'gateway_id' => $gateway->getKey(),
                    'reference' => $reference,
                ]);

                continue;
            }

            $status = trim((string) ($row['status'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));

            if ($status !== '') {
                $log->status = mb_substr($status, 0, 64);
            }

            if ($description !== '' && !$this->isSuccessStatus($status)) {
                $log->failure_reason = mb_substr($description, 0, 190);
            }

            if ($this->isSuccessStatus($status) && !$log->delivered_at) {
                $log->delivered_at = now();
            }

            $log->save();
        }

        return response()->json(['status' => 'Ok'], 200);
    }

    private function isSuccessStatus(string $status): bool
    {
        return (bool) preg_match('/^(success|delivered|sent|ok)$/i', trim($status));
    }
}
