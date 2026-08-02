<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Delivery report callback for Africa's Talking.
 *
 * Configure the URL in the AT dashboard under SMS > SMS Callback URLs >
 * Delivery Reports. AT posts: id, status, phoneNumber, networkCode,
 * failureReason, retryCount.
 *
 * AT does not sign these callbacks, so set AT_DLR_TOKEN and append
 * ?token=<value> to the URL you register if you want a shared secret.
 */
class AfricasTalkingWebhookController extends Controller
{
    public function deliveryReport(Request $request)
    {
        $expected = (string) config('services.africastalking.dlr_token', '');

        if ($expected !== '' && !hash_equals($expected, (string) $request->query('token', ''))) {
            Log::warning("Rejected Africa's Talking DLR with bad token", ['ip' => $request->ip()]);

            return response()->json(['status' => 'rejected'], 403);
        }

        $messageId = (string) $request->input('id', '');
        $status = (string) $request->input('status', '');

        if ($messageId === '') {
            return response()->json(['status' => 'ignored'], 200);
        }

        $log = SmsLog::query()->where('message_id', $messageId)->first();

        if (!$log) {
            Log::info("Africa's Talking DLR for unknown message", ['message_id' => $messageId, 'status' => $status]);

            // Still 200 — a non-2xx makes AT retry a report we can never match.
            return response()->json(['status' => 'unknown'], 200);
        }

        $log->status = $status !== '' ? $status : $log->status;
        $log->failure_reason = $request->filled('failureReason')
            ? mb_substr((string) $request->input('failureReason'), 0, 190)
            : $log->failure_reason;

        if (strcasecmp($status, 'Success') === 0) {
            $log->delivered_at = now();
        }

        $log->save();

        return response()->json(['status' => 'ok'], 200);
    }
}
