# CCC Operations Runbook

**Version:** 1.0  
**Last Updated:** May 23, 2026  
**Status:** Production Ready

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Deployment Procedures](#deployment-procedures)
3. [Common Tasks](#common-tasks)
4. [Troubleshooting](#troubleshooting)
5. [Emergency Procedures](#emergency-procedures)
6. [Environment Variables](#environment-variables)
7. [Quick Reference Commands](#quick-reference-commands)

---

## Architecture Overview

### Component Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                         CLIENTS                              │
├────────────┬────────────┬────────────┬─────────────┬────────┤
│ Web Sender │ Web Admin  │ Web Hub    │ Web Tracking│ Mobile │
│            │            │            │             │ Driver │
└─────┬──────┴─────┬──────┴─────┬──────┴──────┬──────┴────┬───┘
      │            │            │             │           │
      └────────────┴────────────┴─────────────┴───────────┘
                              │
                    ┌─────────▼─────────┐
                    │   Laravel API     │
                    │  (Railway/DO)     │
                    └─────────┬─────────┘
                              │
            ┌─────────────────┼─────────────────┐
            │                 │                 │
    ┌───────▼────┐    ┌──────▼──────┐   ┌─────▼─────┐
    │ PostgreSQL │    │    Redis    │   │  Supabase │
    │  + PostGIS │    │   Cache     │   │  Storage  │
    └────────────┘    └─────────────┘   └───────────┘
                              │
            ┌─────────────────┼─────────────────┐
            │                 │                 │
    ┌───────▼────┐    ┌──────▼──────┐   ┌─────▼─────┐
    │ WhatsApp   │    │  Notify.lk  │   │  WebxPay  │
    │ Cloud API  │    │    SMS      │   │  Payment  │
    └────────────┘    └─────────────┘   └───────────┘
```

### Data Flow

**Booking Flow:**
1. Customer submits booking via web/mobile
2. API validates input and calculates pricing
3. Trip assignment service finds available trip
4. Payment service creates payment intent
5. Customer completes payment
6. Booking confirmed, label PDF generated
7. WhatsApp/SMS notification sent
8. Parcel assigned to trip

**Delivery Flow:**
1. Driver scans parcel at each stage
2. API validates status transition
3. Tracking events logged
4. Notifications sent to sender/receiver
5. Final delivery: NIC + signature + photo captured
6. Proof stored in Supabase Storage
7. Status updated to DELIVERED

---

## Deployment Procedures

### Backend Deployment (Railway/DigitalOcean)

**Prerequisites:**
- Railway CLI installed: `npm install -g @railway/cli`
- Logged in: `railway login`
- Project linked: `railway link`

**Steps:**

```powershell
# 1. Navigate to backend directory
cd D:\CCC\backend

# 2. Run tests
vendor\bin\pest

# 3. Check git status
git status

# 4. Commit changes
git add .
git commit -m "feat: description of changes"

# 5. Push to trigger deployment
git push origin main

# 6. Monitor deployment
railway logs --follow

# 7. Verify health
curl https://api.colombocargo.lk/api/health
```

**Rollback Procedure:**

```powershell
# Railway UI: Go to deployment history → Select previous deployment → Redeploy
# OR via CLI:
railway rollback

# Verify rollback
railway logs --follow
curl https://api.colombocargo.lk/api/health
```

---

### Web App Deployment (Vercel)

**Prerequisites:**
- Vercel CLI installed: `npm install -g vercel`
- Logged in: `vercel login`
- Projects linked

**Deploy Sender Portal:**

```powershell
cd D:\CCC\web-sender
npm run build
vercel --prod
```

**Deploy Admin Console:**

```powershell
cd D:\CCC\web-admin
npm run build
vercel --prod
```

**Deploy Hub Console:**

```powershell
cd D:\CCC\web-hub
npm run build
vercel --prod
```

**Deploy Tracking Page:**

```powershell
cd D:\CCC\web-tracking
npm run build
vercel --prod
```

**Rollback:**
- Vercel UI → Project → Deployments → Select previous → Promote to Production

---

### Mobile App Distribution

**Android (Firebase App Distribution):**

```powershell
cd D:\CCC\mobile-driver

# Update config with production API
# Edit lib/config.dart:
# static const API_BASE_URL = 'https://api.colombocargo.lk';

# Build release APK
flutter build apk --release

# Upload to Firebase App Distribution
firebase appdistribution:distribute build/app/outputs/flutter-apk/app-release.apk `
  --app 1:1234567890:android:abcdef `
  --groups "drivers" `
  --release-notes "Version x.x.x release notes"
```

**iOS (TestFlight):**
- Build in Xcode with Production configuration
- Archive and upload to App Store Connect
- Submit to TestFlight for internal testing

---

## Common Tasks

### Create New Route

**API Method:** `POST /api/v1/routes`

```powershell
curl -X POST https://api.colombocargo.lk/api/v1/routes `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -d '{
    "code": "CMB-JAFFNA",
    "origin_hub_id": "uuid-colombo",
    "destination_hub_id": "uuid-jaffna",
    "distance_km": 395,
    "estimated_duration_minutes": 480,
    "is_active": true
  }'
```

**Via Admin Portal:**
1. Login to admin.colombocargo.lk
2. Navigate to Routes → Create New Route
3. Fill form:
   - Route Code: `CMB-JAFFNA`
   - Origin Hub: Colombo Hub
   - Destination Hub: Jaffna Hub
   - Distance: 395 km
   - Duration: 8 hours
4. Click Save

---

### Schedule Trip

**API Method:** `POST /api/v1/trips`

```powershell
curl -X POST https://api.colombocargo.lk/api/v1/trips `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -d '{
    "route_id": "uuid-cmb-jaffna",
    "lorry_id": "uuid-lorry-lx1234",
    "driver_id": "uuid-driver",
    "departure_date": "2026-05-25",
    "departure_time": "06:00:00",
    "estimated_arrival": "2026-05-25 14:00:00"
  }'
```

---

### Update Pricing

**Database Method:**

```sql
-- Connect to production database
psql $DATABASE_URL

-- Update specific route pricing
UPDATE pricing
SET 
  price_s = 400,
  price_m = 800,
  price_l = 1600,
  price_xl = 3200,
  price_bale = 5500,
  updated_at = NOW()
WHERE route_id = (SELECT id FROM routes WHERE code = 'CMB-JAFFNA');

-- Clear pricing cache
-- (API will auto-clear cache on next request or after TTL)
```

**Via Admin Portal:**
1. Login to admin.colombocargo.lk
2. Navigate to Pricing → Edit Route Pricing
3. Select Route: CMB-JAFFNA
4. Update prices
5. Click Save (cache auto-clears)

---

### Handle Refund

**Steps:**

1. **Verify refund eligibility:**
   - Parcel status: BOOKED, LABEL_PRINTED, or PICKED_UP only
   - Payment status: PAID
   - Refund not already processed

2. **Process refund via WebxPay:**
   ```powershell
   # Via WebxPay dashboard or API
   # Record refund in CCC system
   ```

3. **Update booking status:**
   ```sql
   UPDATE parcels
   SET 
     status = 'CANCELLED',
     refund_status = 'REFUNDED',
     refund_amount = total_price,
     refund_processed_at = NOW(),
     updated_at = NOW()
   WHERE parcel_number = 'CCC-20260523-001234-7';
   ```

4. **Send notification:**
   - System auto-sends WhatsApp/SMS notification
   - Manual notification if needed via Admin Portal

---

### Reset User Password

**API Method:** `POST /api/v1/auth/password/reset`

**Via Database:**

```sql
-- Generate new password hash (use Laravel tinker or external tool)
-- Never use plaintext passwords

-- Update user password
UPDATE users
SET 
  password = '$2y$10$...',  -- bcrypt hash
  updated_at = NOW()
WHERE phone = '+94771234567';

-- Force password reset on next login
UPDATE users
SET password_reset_required = true
WHERE phone = '+94771234567';
```

---

### Process Dispute

**Steps:**

1. **Review dispute details** (Admin Portal → Disputes)
2. **Investigate:**
   - Check tracking events
   - Review delivery proof (NIC, signature, photo)
   - Contact driver if needed
   - Contact customer for clarification
3. **Resolve:**
   - Full refund
   - Partial refund
   - Re-delivery
   - No action (invalid claim)
4. **Update dispute status:**
   - Mark as RESOLVED or REJECTED
   - Add resolution notes
5. **Send outcome notification** to customer

---

## Troubleshooting

### API Returns 500 Error

**Symptoms:**
- API returns generic "An unexpected error occurred"
- No detailed stack trace in response

**Diagnosis:**

```powershell
# Check Railway logs
railway logs --tail 100

# Check error tracking (Sentry)
# Go to sentry.io → CCC Project → Issues

# Check database connection
railway run php artisan tinker
>>> DB::connection()->getPdo();

# Check Redis connection
railway run php artisan tinker
>>> Redis::ping();
```

**Common Causes:**

1. **Database connection timeout:**
   - Solution: Check DB_HOST, DB_PORT, DB_DATABASE in env vars
   - Verify Supabase connection pooler is up
   - Check if database reached connection limit

2. **Redis connection failure:**
   - Solution: Verify REDIS_HOST, REDIS_PORT, REDIS_PASSWORD
   - Check if Redis add-on is provisioned in Railway

3. **Missing environment variable:**
   - Solution: Check Railway env vars vs. `.env.example`
   - Redeploy after adding missing vars

4. **PHP memory limit exceeded:**
   - Solution: Increase memory_limit in Railway settings
   - Optimize query (check for N+1 queries)

---

### Login Fails

**Symptoms:**
- User enters correct credentials but login fails
- Gets "Invalid credentials" or 500 error

**Diagnosis:**

```powershell
# Check if user exists
railway run php artisan tinker
>>> User::where('phone', '+94771234567')->first();

# Check if password hash is correct
>>> $user = User::where('phone', '+94771234567')->first();
>>> Hash::check('password123', $user->password);

# Check Sanctum token creation
>>> $user->createToken('test-token');
```

**Common Causes:**

1. **CORS misconfiguration:**
   - Solution: Verify `FRONTEND_URL` includes correct origin
   - Check `config/cors.php` allows credentials

2. **Sanctum middleware issue:**
   - Solution: Verify `bootstrap/app.php` has correct middleware
   - Ensure `statefulApi()` is NOT enabled (we use stateless tokens)

3. **Password hash algorithm mismatch:**
   - Solution: Re-hash password using bcrypt
   - Update user record in database

---

### Scan Not Working

**Symptoms:**
- Driver scans QR code but nothing happens
- "Invalid QR code" error

**Diagnosis:**

```powershell
# Check QR token validity
railway run php artisan tinker
>>> app(App\Services\QrTokenService::class)->verify('SCANNED_TOKEN_VALUE');

# Check parcel status
>>> Parcel::where('parcel_number', 'CCC-20260523-001234-7')->first()->status;

# Check tracking events
>>> TrackingEvent::where('parcel_id', 'UUID')->orderBy('created_at', 'desc')->get();
```

**Common Causes:**

1. **QR code expired:**
   - Solution: QR tokens expire after 24 hours
   - Generate new label PDF with fresh token

2. **Invalid status transition:**
   - Solution: Check current status vs. attempted transition
   - Refer to ParcelStatus enum for valid transitions

3. **JWT signing key mismatch:**
   - Solution: Verify `APP_KEY` hasn't changed since QR was generated
   - If key rotated, all existing QR codes are invalid

---

### WhatsApp Not Sending

**Symptoms:**
- Status changes but no WhatsApp notification received
- Queue shows failed jobs

**Diagnosis:**

```powershell
# Check queue
railway run php artisan queue:failed

# Check WhatsApp service logs
railway logs | Select-String "WhatsApp"

# Test WhatsApp API manually
curl -X POST https://graph.facebook.com/v21.0/PHONE_NUMBER_ID/messages `
  -H "Authorization: Bearer $WHATSAPP_ACCESS_TOKEN" `
  -H "Content-Type: application/json" `
  -d '{
    "messaging_product": "whatsapp",
    "to": "94771234567",
    "type": "template",
    "template": {
      "name": "booking_confirmed",
      "language": { "code": "en" }
    }
  }'
```

**Common Causes:**

1. **WhatsApp Business Account suspended:**
   - Solution: Check Meta Business Manager status
   - Verify account is not rate-limited

2. **Template not approved:**
   - Solution: Check Meta Business Manager → WhatsApp → Templates
   - Re-submit template for approval if rejected

3. **Invalid phone number format:**
   - Solution: Verify phone is in E.164 format (+94XXXXXXXXX)
   - Strip leading zeros or country code prefix

4. **Access token expired:**
   - Solution: Refresh `WHATSAPP_ACCESS_TOKEN`
   - Update Railway env var and redeploy

---

### Payment Webhook Failed

**Symptoms:**
- Payment completed in WebxPay but booking still shows PENDING
- Payment webhook returns error

**Diagnosis:**

```powershell
# Check webhook logs
railway logs | Select-String "webhook"

# Check payment record
railway run php artisan tinker
>>> Payment::where('transaction_id', 'WEBXPAY_TXN_ID')->first();

# Retry webhook manually
>>> app(App\Services\PaymentService::class)->verifyWebhook($webhookData);
```

**Common Causes:**

1. **Webhook signature invalid:**
   - Solution: Verify `WEBXPAY_API_KEY` matches merchant account
   - Check signature calculation algorithm

2. **Booking not found:**
   - Solution: Verify transaction metadata includes correct `booking_id`
   - Check for UUID format issues

3. **Duplicate webhook:**
   - Solution: Implement idempotency check
   - Return 200 OK for duplicate webhooks to prevent retries

---

### Database Connection Timeout

**Symptoms:**
- API requests hang then timeout
- "SQLSTATE[HY000] [2002] Connection timed out"

**Diagnosis:**

```powershell
# Check connection pool
railway run php artisan tinker
>>> DB::table('parcels')->count();

# Check Supabase dashboard
# Look for active connections, slow queries

# Check PostgreSQL logs
# Supabase → Project → Logs → Postgres Logs
```

**Solutions:**

1. **Connection pool exhausted:**
   - Increase `DB_POOL_SIZE` in Railway
   - Use connection pooler (PgBouncer)
   - Optimize long-running queries

2. **Network issue:**
   - Check Railway network status
   - Check Supabase status page
   - Verify firewall rules

3. **Database overloaded:**
   - Scale Supabase instance
   - Add read replicas
   - Implement caching

---

### Redis Connection Timeout

**Symptoms:**
- "Connection refused" or "Connection timed out"
- Cache miss rate 100%

**Diagnosis:**

```powershell
# Test Redis connection
railway run php artisan tinker
>>> Redis::ping();

# Check Redis add-on status
railway status
```

**Solutions:**

1. **Redis not provisioned:**
   - Add Redis add-on in Railway dashboard
   - Update `REDIS_HOST`, `REDIS_PASSWORD` env vars

2. **Redis memory full:**
   - Check Redis memory usage
   - Increase Redis instance size
   - Reduce cache TTLs

3. **Network issue:**
   - Verify internal network connectivity
   - Check Railway service mesh

---

## Emergency Procedures

### Service Outage Response

**1. Assess Impact:**
- Check status page (Better Stack)
- Verify all services down or partial
- Check recent deployments (potential bad deploy)

**2. Initial Response (0-5 minutes):**
- Post status update: "We're investigating an issue"
- Alert on-call engineer via Slack/SMS
- Check Railway deployment logs
- Check Sentry for error spikes

**3. Mitigation (5-15 minutes):**
- If recent deployment caused issue: **Rollback immediately**
  ```powershell
  railway rollback
  ```
- If database issue: Check Supabase status, consider read-only mode
- If Redis issue: Consider disabling cache temporarily
- If external API issue: Enable fallback mode

**4. Communication (15-30 minutes):**
- Post detailed status update
- ETA for resolution
- Affected features
- Workaround if available

**5. Resolution:**
- Fix root cause
- Deploy fix or rollback
- Monitor for 30 minutes
- Post resolution update

**6. Post-Mortem (within 48 hours):**
- Document incident timeline
- Root cause analysis
- Corrective actions
- Prevention measures

---

### Data Breach Response

**CRITICAL: Follow this procedure immediately if data breach suspected.**

**1. Containment (0-15 minutes):**
- Revoke all API tokens
- Rotate database credentials
- Block suspicious IP addresses
- Take affected services offline if necessary

**2. Assessment (15-60 minutes):**
- Identify what data was accessed
- Identify affected users
- Determine breach vector (SQL injection, leaked token, etc.)
- Preserve logs and evidence

**3. Notification (1-4 hours):**
- Notify management immediately
- Notify affected users within 72 hours (GDPR/Data Protection Act)
- Contact authorities if required by law
- Prepare public statement

**4. Remediation:**
- Patch vulnerability
- Reset affected user passwords
- Issue new API tokens
- Deploy security fix

**5. Recovery:**
- Monitor for unusual activity
- Implement additional security measures
- Conduct security audit

---

### Payment Failure Response

**Symptoms:**
- Multiple payment failures reported
- WebxPay API returning errors

**Steps:**

1. **Check WebxPay Status:**
   - Visit WebxPay status page
   - Check merchant dashboard

2. **Switch to Manual Payment (Temporary):**
   - Enable bank transfer payment method
   - Disable card payments temporarily
   - Post notice on website

3. **Contact WebxPay Support:**
   - Report issue
   - Get ETA for resolution

4. **Monitor & Restore:**
   - Test card payments every 30 minutes
   - Re-enable when working
   - Process queued payments

---

### Critical Bug Escalation

**Severity Levels:**

| Level | Response Time | Example |
|-------|--------------|---------|
| P0 (Critical) | < 1 hour | Payment processing broken, data loss risk |
| P1 (High) | < 4 hours | Booking creation fails, login broken |
| P2 (Medium) | < 24 hours | Notification delays, UI bugs |
| P3 (Low) | < 7 days | Cosmetic issues, minor UX improvements |

**Escalation Path:**

1. **Developer** → Attempts fix (15-30 min)
2. **Tech Lead** → Reviews and coordinates fix (30-60 min)
3. **Project Manager** → Coordinates resources, communicates to stakeholders
4. **CTO** → Escalates to external support if needed

**Contact Information:**
- Tech Lead: [Your Name] - +94771234567
- Project Manager: [PM Name] - +94771234568
- On-call Rotation: Check Slack `#ccc-oncall`

---

## Environment Variables

### Production Environment Variables (Railway Backend)

**Critical Variables (App will not start without these):**

```bash
# Application
APP_NAME="Colombo Cargo Connect"
APP_ENV=production
APP_KEY=base64:... (generate with: php artisan key:generate --show)
APP_DEBUG=false
APP_URL=https://api.colombocargo.lk

# Database (Supabase Production)
DATABASE_URL=postgresql://user:pass@db.region.supabase.co:5432/postgres?sslmode=require
DB_CONNECTION=pgsql
DB_HOST=db.region.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=***SECURE_PASSWORD***

# Redis (Railway Add-on)
REDIS_HOST=redis.railway.internal
REDIS_PASSWORD=***REDIS_PASSWORD***
REDIS_PORT=6379

# Supabase Storage
SUPABASE_URL=https://projectid.supabase.co
SUPABASE_KEY=***ANON_KEY***

# CORS Origins (All production web apps)
FRONTEND_URL=https://colombocargo.lk
ADMIN_URL=https://admin.colombocargo.lk
HUB_URL=https://hub.colombocargo.lk
TRACKING_URL=https://track.colombocargo.lk

# Session & Sanctum
SESSION_DRIVER=redis
SESSION_DOMAIN=.colombocargo.lk
SANCTUM_STATEFUL_DOMAINS=""  # Empty for stateless API
```

**Service Integrations (Required for full functionality):**

```bash
# WhatsApp Cloud API
WHATSAPP_PHONE_NUMBER_ID=123456789012345
WHATSAPP_ACCESS_TOKEN=***LONG_LIVED_TOKEN***

# SMS (Notify.lk)
NOTIFY_LK_USER_ID=***USER_ID***
NOTIFY_LK_API_KEY=***API_KEY***
NOTIFY_LK_SENDER_ID=CCC

# Payment (WebxPay)
WEBXPAY_MERCHANT_ID=***MERCHANT_ID***
WEBXPAY_API_KEY=***API_KEY***
WEBXPAY_WEBHOOK_SECRET=***WEBHOOK_SECRET***

# Firebase Cloud Messaging
FCM_SERVER_KEY=***SERVER_KEY***

# Error Tracking (Sentry)
SENTRY_LARAVEL_DSN=https://...@sentry.io/...

# Monitoring
BETTER_STACK_SOURCE_TOKEN=***TOKEN***
```

**Optional Variables:**

```bash
# Queue
QUEUE_CONNECTION=redis

# Cache
CACHE_DRIVER=redis

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error  # production uses error, staging uses debug

# Performance
OCTANE_SERVER=swoole  # optional: for high-performance
```

### Web App Environment Variables (Vercel)

**web-sender/.env.production:**
```bash
NEXT_PUBLIC_API_URL=https://api.colombocargo.lk
NEXT_PUBLIC_SUPABASE_URL=https://projectid.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=***ANON_KEY***
NEXT_PUBLIC_APP_NAME="CCC Sender Portal"
```

**web-admin/.env.production:**
```bash
NEXT_PUBLIC_API_URL=https://api.colombocargo.lk
NEXT_PUBLIC_SUPABASE_URL=https://projectid.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=***ANON_KEY***
NEXT_PUBLIC_APP_NAME="CCC Admin Console"
```

**web-hub/.env.production:**
```bash
NEXT_PUBLIC_API_URL=https://api.colombocargo.lk
NEXT_PUBLIC_SUPABASE_URL=https://projectid.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=***ANON_KEY***
NEXT_PUBLIC_APP_NAME="CCC Hub Console"
```

**web-tracking/.env.production:**
```bash
NEXT_PUBLIC_API_URL=https://api.colombocargo.lk
NEXT_PUBLIC_SUPABASE_URL=https://projectid.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=***ANON_KEY***
NEXT_PUBLIC_APP_NAME="CCC Tracking"
```

---

## Quick Reference Commands

### Backend (Laravel)

```powershell
# Run tests
vendor\bin\pest

# Run specific test
vendor\bin\pest tests/Feature/BookingTest.php

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Generate API documentation
php artisan l5-swagger:generate

# Queue workers
php artisan queue:work --tries=3
php artisan queue:failed  # List failed jobs
php artisan queue:retry all  # Retry all failed jobs

# Tinker (REPL)
php artisan tinker
```

### Database

```powershell
# Connect to production database
psql $DATABASE_URL

# Backup database
pg_dump $DATABASE_URL > backup_$(date +%Y%m%d).sql

# Restore database
psql $DATABASE_URL < backup_20260523.sql

# Check connection
psql $DATABASE_URL -c "SELECT version();"
```

### Railway CLI

```powershell
# Login
railway login

# Link project
railway link

# View logs
railway logs --tail 100
railway logs --follow

# Run command in production
railway run php artisan migrate

# Environment variables
railway variables
railway variables set KEY=VALUE

# Deployments
railway status
railway rollback
```

### Vercel CLI

```powershell
# Login
vercel login

# Deploy to production
vercel --prod

# View logs
vercel logs [deployment-url]

# Environment variables
vercel env ls
vercel env add KEY production
```

### Git

```powershell
# Standard commit
git add .
git commit -m "type: description"
git push origin main

# Undo last commit (keep changes)
git reset --soft HEAD~1

# Undo last commit (discard changes)
git reset --hard HEAD~1

# View history
git log --oneline --graph --all
```

---

## Support Contacts

**Technical Support:**
- Tech Lead: [Your Name] - tech@colombocargo.lk
- DevOps: [DevOps Name] - devops@colombocargo.lk
- On-call: Check Slack `#ccc-oncall` channel

**External Vendors:**
- Railway Support: support@railway.app
- Vercel Support: support@vercel.com
- Supabase Support: support@supabase.com
- WebxPay Support: support@webxpay.com
- Notify.lk Support: support@notify.lk

**Emergency Contacts:**
- P0/P1 Incidents: Call +94771234567 (24/7)
- Security Incidents: security@colombocargo.lk
- Legal/Compliance: legal@colombocargo.lk

---

**Document Version:** 1.0  
**Last Reviewed:** May 23, 2026  
**Next Review:** June 23, 2026
