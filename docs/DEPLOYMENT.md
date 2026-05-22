# Deployment Guide

This repository is designed to run with:

- Supabase for PostgreSQL and Storage
- Railway for the Laravel API
- Vercel for the Next.js web apps

## 1. Supabase

Create one Supabase project in a nearby region such as Singapore.

Create these storage buckets:

- `ccc-labels` as public read for PDF labels
- `ccc-proofs` as private for delivery proof images and signatures

Collect these values for the backend environment:

- `SUPABASE_URL`
- `SUPABASE_ANON_KEY`
- `SUPABASE_SERVICE_ROLE_KEY`
- `SUPABASE_BUCKET_LABELS`
- `SUPABASE_BUCKET_PROOFS`

Use the Supabase database connection string in Railway for:

- `DB_CONNECTION=pgsql`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## 2. Railway

Create one Railway service from this GitHub repo and set the service root directory to `backend`.

Railway will use:

- `Dockerfile`
- `railway.json`
- `start-railway.sh`

Create a second Railway service from the same `backend` root directory for queue workers. Reuse the same environment variables, but override the start command to:

- `php artisan queue:work --tries=3 --timeout=120`

Add a Redis service in Railway and connect these environment variables:

- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`
- `REDIS_CLIENT=predis`
- `REDIS_HOST`
- `REDIS_PORT`
- `REDIS_PASSWORD`

Required backend environment variables:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY`
- `APP_URL=https://<railway-backend-domain>`
- `LOG_CHANNEL=stack`
- `LOG_LEVEL=info`
- `SANCTUM_STATEFUL_DOMAINS=<vercel-admin-domain>,<vercel-sender-domain>,<vercel-hub-domain>`
- `SESSION_DOMAIN=.vercel.app`
- `CORS_ALLOWED_ORIGINS=https://<sender-domain>,https://<tracking-domain>,https://<admin-domain>,https://<hub-domain>`
- `QR_TOKEN_SECRET`
- `WHATSAPP_PHONE_NUMBER_ID`
- `WHATSAPP_BUSINESS_ACCOUNT_ID`
- `WHATSAPP_ACCESS_TOKEN`
- `WHATSAPP_APP_SECRET`
- `WHATSAPP_WEBHOOK_VERIFY_TOKEN`
- `WHATSAPP_API_VERSION=v21.0`
- `NOTIFY_LK_USER_ID`
- `NOTIFY_LK_API_KEY`
- `NOTIFY_LK_SENDER_ID`

After the first successful deploy, set the Meta webhook URL to:

- `https://<railway-backend-domain>/api/v1/webhooks/whatsapp`

## 3. Vercel

Create four separate Vercel projects from the same GitHub repository with these root directories:

- `web-sender`
- `web-tracking`
- `web-admin`
- `web-hub`

Environment variables per project (all use the same variable names now):

**web-sender** — set in Vercel project settings → Environment Variables:
- `NEXT_PUBLIC_API_BASE_URL` = `https://<railway-backend-domain>/api/v1`
- `NEXT_PUBLIC_TRACKING_URL` = `https://<ccc-tracking.vercel.app>`

**web-tracking** — set in Vercel project settings → Environment Variables:
- `NEXT_PUBLIC_API_BASE_URL` = `https://<railway-backend-domain>/api/v1`

**web-admin** — set in Vercel project settings → Environment Variables:
- `NEXT_PUBLIC_API_BASE_URL` = `https://<railway-backend-domain>/api/v1`

**web-hub** — set in Vercel project settings → Environment Variables:
- `NEXT_PUBLIC_API_BASE_URL` = `https://<railway-backend-domain>/api/v1`

## 4. Launch Order

1. Push the repo to GitHub.
2. Create Supabase and collect DB plus storage credentials.
3. Deploy the backend to Railway with Supabase Postgres and Railway Redis.
4. Confirm `GET /api/health` is green on Railway.
5. Deploy the four Next.js apps to Vercel.
6. Update Railway CORS and Sanctum env vars with the final Vercel domains.
7. Configure the WhatsApp webhook in Meta.

## 5. Post-Launch Checks

- Booking works from sender web.
- Public tracking loads live parcel data.
- Admin login reaches the Railway API.
- Hub console can load inventory and scan endpoints.
- Railway queue workers are processing WhatsApp and SMS jobs.
