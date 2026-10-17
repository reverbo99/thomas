# FastHub SMS Gateway

The system can send all SMS through the **FastHub Bulk SMS Gateway** documented in
`api-documentation.pdf`. FastHub sits alongside the existing `sms.co.tz` and
Africa's Talking drivers and is selected in **Admin → Settings → SMS Gateway**.

## How it works

- **Send**: `App\Services\Sms\FastHubDriver` posts the documented payload to the
  FastHub endpoint:

  ```json
  {
    "auth": { "client_id": "...", "client_secret": "..." },
    "messages": [
      { "text": "...", "msisdn": "255712345678", "source": "HIGHLINK", "reference": "<uuid>" }
    ]
  }
  ```

  The `reference` is a UUID (36 chars, above the documented 20-char minimum) and is
  stored as `sms_logs.message_id` so delivery reports can be matched back.

- **Delivery status callback**: FastHub posts to

  ```
  POST {APP_URL}/webhooks/fasthub/sms-status/{gatewayId}
  ```

  with `{"statuses":[{"status":"...","reference":"...","description":"...","msisdn":"..."}]}`.
  The endpoint answers `{"status":"Ok"}` and updates `sms_logs.status` /
  `delivered_at`. It is CSRF-exempt, so it can be registered directly in the
  FastHub portal.

## Setting it up

1. Go to **Admin → Settings**.
2. In **FastHub configurations**, click **Add configuration** and fill in:
   - **Name** – a label for the account, e.g. “Main bulk SMS account”.
   - **Client ID** – from FastHub.
   - **Client secret** – from FastHub (stored encrypted; never shown again).
   - **Status callback URL** – where FastHub should post delivery statuses. Leave
     blank to use the built-in `/webhooks/fasthub/sms-status/{id}` URL shown for
     the configuration. Copy that URL into the FastHub portal.
   - **API endpoint URL (optional)** – override for the send endpoint. Leave
     blank to use the built-in FastHub endpoint configured in
     `config/services.php` (`https://bulksms.fasthub.co.tz/api/sms/send`).
3. Click **Make default** (or pick the configuration under **Active gateway →
   FastHub** in the main settings form and save).
4. Send a test SMS from the **Send Test SMS** card.

Multiple configurations can be saved (for example production and a backup
account). Only the default one is used; switching is a click in the same screen.
Deleting the default configuration automatically falls back to the next saved
configuration, or to the legacy gateway if none is left.

## Notes

- `client_secret` is encrypted with the app key. If `APP_KEY` is rotated the
  stored secret becomes unreadable; re-enter it in the configuration.
- The PDF does not include the FastHub send endpoint URL, so the gateway URL is
  defined in `config/services.php` (`fasthub.endpoint`) and can be overridden per
  saved configuration. No `.env` values are required.
- `SMS_API_SENDER_ID` (Settings → Sender ID) is sent as the message `source`.
