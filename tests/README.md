Testing scripts for Paystack integration

1) send_webhook.php
- Purpose: Send a sample Paystack webhook payload to your local `server/paystack_webhook.php` endpoint using the configured `PAYSTACK_WEBHOOK_SECRET`.
- Usage:
  - Ensure `PAYSTACK_WEBHOOK_SECRET` is set in your environment.
  - Optionally enable `PAYSTACK_MOCK_VERIFY=1` to avoid external API calls and use the fixture in `tests/fixtures/verify.json` for transaction verification.

```powershell
# Example (PowerShell)
$env:PAYSTACK_WEBHOOK_SECRET = 'your_test_webhook_secret'
$env:PAYSTACK_MOCK_VERIFY = '1'
php tests/send_webhook.php http://localhost/server/paystack_webhook.php tests/fixtures/webhook_charge_success.json
```

2) Fixtures
- `tests/fixtures/webhook_charge_success.json` — sample `charge.success` payload.
- `tests/fixtures/verify.json` — mock transaction verify response returned when `PAYSTACK_MOCK_VERIFY=1`.

Notes:
- The webhook script computes the `X-Paystack-Signature` header using `hash_hmac('sha512', payload, PAYSTACK_WEBHOOK_SECRET)`.
- For full end-to-end testing against Paystack, set `PAYSTACK_MOCK_VERIFY` to `0` and use real Paystack test keys; register your webhook URL in the Paystack test dashboard.
