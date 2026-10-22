# WhatsApp invoice delivery

Billing commits the bill and payment first, generates its PDF, then makes one bounded synchronous Cloud API send attempt. A WhatsApp outage does not roll back the completed bill. The Docker Compose deployment has no queue worker, so invoice delivery does not depend on queued jobs.

Staff must check the invoice WhatsApp consent box after the customer agrees. Without that consent, the invoice is marked `skipped`. Existing customer records default to no consent. Cash, UPI, card, other and split payments use the same send path. The approved template currently shows the invoice amount and link, but not service names or payment methods.

## Required runtime configuration

Set these variables in the deployment secret store or `.env.docker`:

- `WHATSAPP_ACCESS_TOKEN`: valid system-user token with WhatsApp messaging permission.
- `WHATSAPP_PHONE_NUMBER_ID`: the registered sender's Phone Number ID.
- `WHATSAPP_API_VERSION`: a currently supported Graph API version.
- `WHATSAPP_INVOICE_TEMPLATE`: approved utility template name.
- `WHATSAPP_INVOICE_TEMPLATE_LANGUAGE`: approved template language code.
- `WHATSAPP_APP_SECRET`: Meta app secret used to authenticate webhook signatures.
- `WHATSAPP_VERIFY_TOKEN`: webhook subscription verification token.

The code expects the approved `salon_invoice` template to have four body text parameters in this order: customer name, invoice number, grand total, public invoice URL. A read-only Meta Graph check on 2026-10-22 verified the configured token permissions, Phone Number ID lookup, and approved English template with placeholders 1–4 in that order. Meta returned `code_verification_status=EXPIRED` for the phone number; an owner must confirm the sender remains registered and able to send before any live test. No message was sent.

After configuration, deploy the additive customer-consent, delivery-timestamp, and invoice-access migrations, refresh Laravel's cached configuration, and roll out the app through the verified deployment process. Do not use real customer numbers for a smoke test. Verify the approved template with a designated consenting test recipient, then confirm the API acceptance and matching `sent`/`delivered` webhook status. API acceptance alone is not proof of delivery.

New invoice links use a random 256-bit bearer token; only its SHA-256 digest is stored. Links are reusable for 90 days and can be revoked by an administrator. The route is no-store, no-referrer, and excluded from the bundled Nginx access log. `APP_URL` must be the verified public HTTPS host in production. The invoice-access migration hashes existing UUID links, clears those values from the legacy column, and gives links a 90-day compatibility period. Previously sent links keep working through digest lookup. Old application versions that query the plaintext UUID column are not compatible; rollback must use a security-compatible release.

An HTTP 429 response is retried once after a short delay. Other 4xx errors are not retried automatically. Connection failures and 5xx responses are recorded without automatic retry because the remote API may have accepted a request before the response was lost; replaying those requests could send duplicate invoices. The status is associated with the returned Meta message ID, and signed webhooks update it to `sent`, `delivered`, `read`, or `failed`. Webhook status timestamps are kept so older events cannot replace later delivery states; duplicate events are idempotent.

## Deployment and rollback

Before rollout, back up and verify the correct database using the approved production process. Deploy code and run the three new additive migrations through the normal release workflow. Existing invoice tracking migrations must remain in their current deployed state; do not rerun or roll back them manually. The target, backup procedure, and release workflow still require owner confirmation.

Do not roll back to an image that exposes invoice PDFs through the old unauthenticated UUID route. If release checks fail, roll back to a compatible application release that retains token authorization, while leaving the consent, status-timestamp, and invoice-access columns applied. Do not roll back the consent migration if that would lose consent records; rolling back invoice-access storage would invalidate the new hashed links.
