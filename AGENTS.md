# Agent Notes — SmartShop (LUWI) e-commerce

## Local development
- Serve: `php artisan serve --port=8001` (nohup, log to /tmp/opencode/serve.log).
- Tests: `php artisan test` (full suite ~9s, 167 tests / 1121 assertions).
- **Queue**: local `.env` uses `QUEUE_CONNECTION=sync` so queued mail (reset
  links, OTP codes, order confirmations) is sent immediately. Do NOT set it back
  to `database` locally — there is no queue worker running, and mail would pile
  up in the `jobs` table and never be delivered.
- Production has a real `queue:work` worker (systemd/www-data) and uses the
  database queue.

## Mail
- SMTP via Gmail (app password). Production `MAIL_FROM_ADDRESS=no-reply@smartshop-luwi.tech`
  must stay consistent with the Gmail account allowed by Gmail SMTP settings —
  changing it breaks delivery (Gmail rejects From mismatches).
- Password reset tokens are stored hashed (bcrypt) in `password_reset_tokens`;
  the plaintext token in the emailed link is generated via the Password broker.