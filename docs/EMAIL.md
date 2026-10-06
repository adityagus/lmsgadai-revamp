# Email via Gmail SMTP or Resend

## Activity report

Report emails attach the SAME workbook as Excel Aktivitas on master/audit-logs, via shared ActivityLogExcelExporter. It contains one Aktivitas Log sheet with the original 15 columns, yellow header, employee identity/NIK/name/position/branch, read/unread status, first/last read and open count. The email body is only a short attachment notice. PHP ZIP must be enabled: in PowerShell set `$env:PHP_INI_SCAN_DIR = Join-Path $PWD '.runtime/php'` before invoking the command if needed.

Run `php artisan email:activity-report aditwebdev@gmail.com` to send a report immediately through the selected provider. Add `--preview` to inspect the report without sending. No Redis, queue worker, or automatic schedule is needed.

The Excel follows the button's existing employee/document rows and archived-document exclusion. CLI --preview remains a brief text summary of lifetime counts and up to 20 recently read documents; it does not send email. Neither report is a daily event count: document_views stores cumulative counters. Each send invocation sends a new email; after ambiguous failures check the provider before retrying.

Verified live report accepted for aditwebdev@gmail.com: 01a0e654-b653-7201-8d99-6a90832b8b6c.

Current provider selection follows `MAIL_MAILER`: `smtp` sends using Laravel SMTP; `resend` uses the direct HTTPS API below. Other mailers are rejected to avoid reporting log-only delivery as real email.

For Gmail use MAIL_MAILER=smtp, MAIL_SCHEME=smtp, MAIL_HOST=smtp.gmail.com, MAIL_PORT=587, MAIL_USERNAME=aditwebdev@gmail.com, MAIL_FROM_ADDRESS=aditwebdev@gmail.com. Set MAIL_PASSWORD to a Google App Password belonging to that account, not the normal login password. TLS verification stays enabled. Run `php artisan config:clear` after editing .env.

Latest Gmail test: authentication rejected with code 534 (App Password/account authorization required). A connection/authentication-only diagnostic confirmed this without sending another message. Delivery remains pending valid Gmail authorization.

With MAIL_MAILER=resend, EmailService::sendText($recipient, $subject, $body) sends through the Resend HTTPS API and returns the accepted email ID. No extra SDK, queue worker, database changes or public sending endpoint is required.

Configure local .env:

```dotenv
# Existing MAIL_API is supported; do not duplicate real secrets in documentation.
MAIL_API=your-resend-api-key
RESEND_FROM_ADDRESS=onboarding@resend.dev
```

API key: MAIL_API. Sender: RESEND_FROM_ADDRESS when set, otherwise MAIL_FROM_ADDRESS (fallback MAIL_USERNAME). Set MAIL_MAILER=resend for Resend.

The onboarding@resend.dev test sender can only send to the Resend account owner's email. To send to other recipients, verify a domain in Resend and set RESEND_FROM_ADDRESS to an address on that domain. A Gmail address cannot be used as a domain you own.

```powershell
php artisan config:clear
php artisan email:test adityagustian11@gmail.com
```

Success indicates acceptance by the API, not inbox delivery. Check the Resend dashboard and Inbox/Spam. No automatic retries are performed; after an ambiguous timeout check the dashboard before another attempt. Provider response bodies and API credentials are not printed.

Verification 2026-09-28: tests use HTTP fakes with stray requests disabled. Live request using MAIL_API reached Resend but received HTTP 403: the requested Gmail recipient is not allowed with this account's onboarding sender. No email was accepted.

Official references:
- https://resend.com/docs/api-reference/emails/send-email
- https://resend.com/docs/knowledge-base/403-error-resend-dev-domain


Latest user choice: Resend. MAIL_MAILER is set to resend. Live send with aditwebdev@gmail.com as sender was rejected because the sender domain is not verified. Gmail is not a domain the user can verify in Resend; a verified owned-domain sender or the restricted onboarding test sender is required.
