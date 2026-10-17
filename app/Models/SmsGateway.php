<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One named set of FastHub SMS API credentials.
 *
 * The secret is encrypted at rest (Laravel's `encrypted` cast) and is never
 * rendered back to the settings form — a blank secret on edit means "keep the
 * stored value".
 */
class SmsGateway extends Model
{
    protected $table = 'sms_gateways';

    protected $fillable = [
        'name',
        'client_id',
        'client_secret',
        'status_callback_url',
        'api_endpoint',
        'last_used_at',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'client_secret',
    ];

    /**
     * Safe-to-render form of the client id, e.g. "abcd…wxyz".
     */
    public function maskedClientId(): string
    {
        $id = trim((string) $this->client_id);
        $length = mb_strlen($id);

        if ($length === 0) {
            return '—';
        }

        if ($length <= 8) {
            return mb_substr($id, 0, 2) . '…' . mb_substr($id, -2);
        }

        return mb_substr($id, 0, 4) . '…' . mb_substr($id, -4);
    }

    /**
     * Callback URL to show the admin: the saved value when present, otherwise
     * the built-in endpoint the app exposes for this configuration.
     */
    public function effectiveCallbackUrl(): string
    {
        $saved = trim((string) ($this->status_callback_url ?? ''));

        return $saved !== '' ? $saved : $this->builtInCallbackUrl();
    }

    public function builtInCallbackUrl(): string
    {
        return route('webhooks.fasthub.status', ['gateway' => $this->getKey()]);
    }
}
