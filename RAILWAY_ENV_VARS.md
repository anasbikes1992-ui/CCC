# Railway Production Environment Variables

Copy these into **Railway → your backend service → Variables**.
Replace every `<placeholder>` with your real value.

---

## Core Application

```
APP_NAME=Colombo Cargo Connect
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:ODppQxq1id/cMfPbnRd6A6cNwwyjUfqk7RfPg3q4Ag0=
APP_TIMEZONE=Asia/Colombo
APP_URL=https://<your-railway-backend-domain>
LOG_CHANNEL=stack
LOG_LEVEL=info
```

> `APP_KEY` — the value above is from your existing local `.env`.
> If you want a fresh key run `php artisan key:generate --show` in the Railway shell.

---

## Database (Supabase PostgreSQL)

Get these from **Supabase → Project Settings → Database → Connection string (URI)**.
Use the **Transaction pooler** (port 6543) for a web server, or direct (port 5432).

```
DB_CONNECTION=pgsql
DB_HOST=<supabase-db-host>
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=<supabase-db-password>
```

> Supabase host looks like: `aws-0-ap-southeast-1.pooler.supabase.com`

---

## Redis (Railway Redis service)

Add a Redis service inside Railway, then click **Connect → Service Variables** to
inject these automatically, or copy the values manually:

```
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=${{Redis.RAILWAY_PRIVATE_DOMAIN}}
REDIS_PORT=6379
REDIS_PASSWORD=${{Redis.REDISPASSWORD}}
```

> Railway reference variables (`${{...}}`) only work in the Railway variable editor.
> If you set them as plain text, replace the references with the actual values shown
> in the Redis service's Variables tab.

---

## Storage (Supabase S3-compatible)

```
FILESYSTEM_DISK=supabase
SUPABASE_URL=https://afmjlngcsxrwfmznaxpf.supabase.co
SUPABASE_SERVICE_ROLE_KEY=<your-supabase-service-role-key>
SUPABASE_ANON_KEY=<your-supabase-anon-key>
SUPABASE_BUCKET_LABELS=ccc-labels
SUPABASE_BUCKET_PROOFS=ccc-proofs
```

> Get `SUPABASE_SERVICE_ROLE_KEY` from Supabase → Project Settings → API.
> Keep it secret — it bypasses Row Level Security.

---

## CORS + Sanctum (set AFTER you know your Vercel URLs)

Replace the domains below with your actual Vercel deployment URLs once known.

```
CORS_ALLOWED_ORIGINS=https://ccc-sender.vercel.app,https://ccc-tracking.vercel.app,https://ccc-admin.vercel.app,https://ccc-hub.vercel.app
SANCTUM_STATEFUL_DOMAINS=ccc-sender.vercel.app,ccc-admin.vercel.app,ccc-hub.vercel.app
SESSION_DOMAIN=.vercel.app
```

> If you use custom domains (e.g. `send.cargo.lk`) add those too, comma-separated.

---

## QR Token

```
QR_TOKEN_SECRET=AMoP2f2p1xK9UPSkLLyMs05jCxcIqthNg7t0T1pln5A=
QR_TOKEN_TTL_DAYS=30
```

> This secret is pre-generated and unique to this project. Keep it safe — changing it
> invalidates all existing QR codes instantly.

---

## WhatsApp Cloud API (Meta)

Fill in after WhatsApp Business setup on Meta Developers.

```
WHATSAPP_PHONE_NUMBER_ID=<meta-phone-number-id>
WHATSAPP_BUSINESS_ACCOUNT_ID=<meta-waba-id>
WHATSAPP_ACCESS_TOKEN=<meta-permanent-access-token>
WHATSAPP_APP_SECRET=<meta-app-secret>
WHATSAPP_WEBHOOK_VERIFY_TOKEN=<random-string-you-choose>
WHATSAPP_API_VERSION=v21.0
```

After first Railway deploy, register the webhook at:
`https://<your-railway-backend-domain>/api/v1/webhooks/whatsapp`

---

## SMS (Notify.lk)

```
NOTIFY_LK_USER_ID=<your-notify-lk-user-id>
NOTIFY_LK_API_KEY=<your-notify-lk-api-key>
NOTIFY_LK_SENDER_ID=CCC
```

---

## Payments (WebxPay)

```
WEBXPAY_MERCHANT_ID=<webxpay-merchant-id>
WEBXPAY_SECRET_KEY=<webxpay-secret-key>
WEBXPAY_RETURN_URL=https://ccc-sender.vercel.app/payment/callback
```

---

## Firebase Push (optional — add when mobile apps go live)

```
FCM_CREDENTIALS_PATH=storage/app/firebase/service-account.json
FCM_PROJECT_ID=<firebase-project-id>
```

Upload the Firebase `service-account.json` file to Railway's persistent storage or
use a volume mount. Skip this until you wire up mobile push.

---

## Monitoring (optional)

```
SENTRY_LARAVEL_DSN=https://<key>@o0.ingest.sentry.io/<project>
SENTRY_TRACES_SAMPLE_RATE=0.1
```

---

## Mail

```
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@cargo.lk
MAIL_FROM_NAME=Colombo Cargo Connect
```

> Keep `MAIL_MAILER=log` until you configure an SMTP provider (e.g. Resend, Mailgun).

---

## Queue Worker (second Railway service)

Create a **second** Railway service from the same repo with the same env vars,
but override the start command to:

```
php artisan queue:work --tries=3 --timeout=120
```

This processes WhatsApp, SMS, and push notification jobs asynchronously.

---

## Quick Checklist

- [ ] `APP_KEY` set (existing value is fine, or regenerate)
- [ ] `DB_*` pointing at Supabase
- [ ] `REDIS_*` pointing at Railway Redis service
- [ ] `SUPABASE_SERVICE_ROLE_KEY` and `SUPABASE_ANON_KEY` filled in
- [ ] `CORS_ALLOWED_ORIGINS` includes all 4 Vercel domains
- [ ] `SANCTUM_STATEFUL_DOMAINS` includes admin, sender, hub domains
- [ ] `QR_TOKEN_SECRET` set (pre-generated value above)
- [ ] First deploy passes `GET /up` health check
- [ ] WhatsApp and Notify.lk credentials added when ready
