# WhatsApp invoice delivery diagnostic

Checked 2026-10-22 against the running local Docker Compose app. This checkout does not establish that the container is the production deployment.

## Verified runtime findings

- The running container is `salonos-app:local`, created 2026-10-05. The image does not contain `WhatsAppInvoiceService`, the invoice-consent migration, or the current WhatsApp credential mapping in `config/services.php`. The running app therefore does not contain the invoice-sending implementation in this checkout.
- The runtime has an access token and phone-number ID. A read-only Graph API request returned HTTP success for the token permission lookup; `whatsapp_business_messaging` and `whatsapp_business_management` are granted. A read-only lookup for the configured phone-number ID also succeeded.
- The configured Graph API version is `v26.0`; the phone-number lookup and template lookup succeeded at that version.
- The configured WABA contains `salon_invoice` in `APPROVED` status, language `en`. Its body has four positional placeholders, `{{1}}` through `{{4}}`, in this order:
  1. Customer name
  2. Invoice number
  3. Amount
  4. Invoice URL
- `WHATSAPP_APP_SECRET` is missing from the running container. With the current checkout's signed-webhook validation, status POSTs cannot be authenticated until this deployment secret is set.
- The template name and language variables are absent from the running container; the current checkout defaults them to `salon_invoice` and `en`.
- The database has the invoice-tracking migration, but not the customer-consent migration. The new delivery-event timestamp migration is also pending. No migration was run during this diagnostic.
- The message is a template containing a public invoice URL. It is not a WhatsApp PDF/document attachment. No test message was sent.

## Root cause

The local source and running image are out of sync. The running image predates and lacks the invoice sender/configuration added in this checkout, so editing the checkout alone cannot repair the running app. Once the current source is built and deployed through the verified deployment workflow, webhook delivery tracking will still require `WHATSAPP_APP_SECRET` in the deployment secret store. The token, phone-number ID, API permissions, API version, template approval, language, and four-parameter layout were verified successfully and are not the observed blocker.

## Corrective actions

1. Set `WHATSAPP_APP_SECRET` in the deployment secret store. Keep the verified token and sender credentials in the secret store; do not copy secret values into this report.
2. Confirm the actual production image and release process. Build and deploy the reviewed source through that process; do not treat the local Compose image as proof of production state.
3. Back up the production database through its established backup process, then apply the additive customer-consent and delivery-timestamp migrations as part of the release.
4. Refresh Laravel's configuration cache and check the webhook verification and invoice delivery status using a designated, consenting test recipient. Do not use customer numbers.
5. Roll back application code to the prior release if release checks fail. Leave additive consent/status columns in place during code rollback to preserve records.

No production deployment, database change, or customer message was performed.
