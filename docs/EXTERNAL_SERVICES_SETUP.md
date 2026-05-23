# External Service Setup Guide

**Purpose:** Step-by-step instructions for setting up all external services required for CCC production deployment.

**Last Updated:** May 23, 2026

---

## Prerequisites Checklist

Before starting, ensure you have:

- [ ] **Business Registration Documents** (for merchant accounts)
- [ ] **Bank Account Details** (for payment processing)
- [ ] **Domain Names** purchased:
  - colombocargo.lk (or similar)
  - admin.colombocargo.lk
  - hub.colombocargo.lk
  - track.colombocargo.lk
  - api.colombocargo.lk
- [ ] **SSL Certificates** (or use free Let's Encrypt)
- [ ] **Company Logo** (PNG, 512×512 minimum)
- [ ] **Business Phone Number** (for WhatsApp Business)
- [ ] **Credit Card** (for service subscriptions)

---

## 1. Supabase Production Setup

**Service:** Database + Storage  
**Cost:** ~$25/month (Pro plan)  
**Setup Time:** 30 minutes

### Steps:

1. **Create Account:**
   - Go to [supabase.com](https://supabase.com)
   - Sign up with email or GitHub
   - Verify email

2. **Create Production Project:**
   - Click "New Project"
   - Organization: Create new "Colombo Cargo Connect"
   - Project name: `ccc-production`
   - Database Password: Generate strong password (save securely!)
   - Region: Choose closest to Sri Lanka (Singapore recommended)
   - Pricing Plan: **Pro** ($25/month)
   - Click "Create new project"
   - Wait 2-3 minutes for provisioning

3. **Enable PostGIS Extension:**
   ```sql
   -- In SQL Editor (Supabase Dashboard → SQL Editor):
   CREATE EXTENSION IF NOT EXISTS postgis;
   
   -- Verify installation:
   SELECT PostGIS_version();
   ```

4. **Run Migrations:**
   ```powershell
   # Connect to production database
   $env:DATABASE_URL="postgresql://postgres:[PASSWORD]@db.[PROJECT-REF].supabase.co:5432/postgres"
   
   cd D:\CCC\backend
   php artisan migrate --force
   ```

5. **Create Storage Buckets:**
   - Supabase Dashboard → Storage → Create Bucket
   
   **Bucket 1: `delivery-proofs`**
   - Public: No (Private)
   - File size limit: 5 MB
   - Allowed MIME types: `image/jpeg,image/png,image/heic`
   
   **Bucket 2: `parcel-labels`**
   - Public: No (Private)
   - File size limit: 2 MB
   - Allowed MIME types: `application/pdf`
   
   **Bucket 3: `documents`**
   - Public: No (Private)
   - File size limit: 10 MB
   - Allowed MIME types: `image/jpeg,image/png,application/pdf`

6. **Configure Policies:**
   ```sql
   -- delivery-proofs bucket policies
   CREATE POLICY "Authenticated users can upload proofs"
   ON storage.objects FOR INSERT
   TO authenticated
   WITH CHECK (bucket_id = 'delivery-proofs');
   
   CREATE POLICY "Users can view their proofs"
   ON storage.objects FOR SELECT
   TO authenticated
   USING (bucket_id = 'delivery-proofs');
   ```

7. **Save Credentials:**
   - Project URL: https://[PROJECT-REF].supabase.co
   - Anon Key: (Dashboard → Settings → API → anon public)
   - Service Role Key: (Dashboard → Settings → API → service_role - keep secret!)
   - Database URL: Connection string from Dashboard → Settings → Database

### Cost Breakdown:
- Pro Plan: $25/month
- Database: Included (8 GB RAM, 2 CPU cores)
- Storage: First 100 GB free, then $0.021/GB/month
- Bandwidth: First 250 GB free, then $0.09/GB

---

## 2. Railway Production Setup

**Service:** Backend API Hosting  
**Cost:** ~$20/month (Pro plan + resources)  
**Setup Time:** 45 minutes

### Steps:

1. **Create Account:**
   - Go to [railway.app](https://railway.app)
   - Sign up with GitHub (recommended for auto-deploy)
   - Verify email

2. **Create Production Project:**
   - Click "New Project"
   - Choose "Deploy from GitHub repo"
   - Connect GitHub account
   - Select repository: `yourusername/CCC`
   - Set root directory: `backend`
   - Click "Deploy"

3. **Add Redis Add-on:**
   - Project Dashboard → "New" → "Database" → "Redis"
   - Wait for provisioning (~1 minute)
   - Railway auto-injects `REDIS_URL` environment variable

4. **Configure Environment Variables:**
   - Project → Settings → Variables
   - Add all variables from `RAILWAY_ENV_VARS.md`
   - **Critical variables:**
     ```
     APP_ENV=production
     APP_DEBUG=false
     APP_URL=https://api.colombocargo.lk
     DATABASE_URL=[Supabase connection string]
     REDIS_HOST=redis.railway.internal
     REDIS_PASSWORD=[auto-injected by Railway]
     ```

5. **Configure Custom Domain:**
   - Project → Settings → Domains
   - Click "Custom Domain"
   - Enter: `api.colombocargo.lk`
   - Add CNAME record in your DNS:
     ```
     Type: CNAME
     Name: api
     Value: [generated-domain].railway.app
     TTL: 3600
     ```
   - Wait for DNS propagation (5-60 minutes)
   - Railway auto-provisions SSL certificate

6. **Configure Build Settings:**
   - Project → Settings → Build
   - Build command: `composer install --optimize-autoloader --no-dev`
   - Start command: `php artisan serve --host=0.0.0.0 --port=$PORT`
   - Add worker service for queue:
     - New Service → "Empty Service"
     - Start command: `php artisan queue:work --tries=3`

7. **Enable Auto-Deploy:**
   - Project → Settings → GitHub
   - Branch: `main`
   - Auto-deploy: Enabled
   - Every push to `main` triggers deployment

### Cost Breakdown:
- Pro Plan: $20/month (required for custom domains)
- Compute: ~$5/month (500 MB RAM, 1 vCPU)
- Redis: ~$5/month (250 MB)
- Estimated Total: $30/month

---

## 3. Vercel Production Setup

**Service:** Web App Hosting (4 apps)  
**Cost:** $0 (Hobby) or $20/month (Pro)  
**Setup Time:** 60 minutes (15 min per app)

### Setup for Each App:

**Repeat for:**
- web-sender
- web-admin
- web-hub
- web-tracking

### Steps:

1. **Create Account:**
   - Go to [vercel.com](https://vercel.com)
   - Sign up with GitHub
   - Verify email

2. **Import Project:**
   - Dashboard → "Add New" → "Project"
   - Import Git Repository: `yourusername/CCC`
   - Root Directory: `web-sender` (change per app)
   - Framework: Next.js (auto-detected)
   - Click "Deploy"

3. **Configure Environment Variables:**
   - Project → Settings → Environment Variables
   - Add for **Production** environment:
     ```
     NEXT_PUBLIC_API_URL=https://api.colombocargo.lk
     NEXT_PUBLIC_SUPABASE_URL=https://[project].supabase.co
     NEXT_PUBLIC_SUPABASE_ANON_KEY=[anon key]
     NEXT_PUBLIC_APP_NAME=CCC Sender Portal
     ```

4. **Configure Custom Domain:**
   - Project → Settings → Domains
   - Add Domain:
     - **web-sender:** `colombocargo.lk` or `app.colombocargo.lk`
     - **web-admin:** `admin.colombocargo.lk`
     - **web-hub:** `hub.colombocargo.lk`
     - **web-tracking:** `track.colombocargo.lk`
   
   - Add DNS records:
     ```
     Type: A
     Name: @  (or subdomain)
     Value: 76.76.21.21 (Vercel IP)
     TTL: 3600
     ```
   
   - Vercel auto-provisions SSL

5. **Enable Auto-Deploy:**
   - Settings → Git → Branch: `main`
   - Production Branch: `main`
   - Auto-deploy: Enabled

### Cost Breakdown:
- Hobby (Free): Limited to personal projects, 100 GB bandwidth
- Pro ($20/month): Commercial use, 1 TB bandwidth, team collaboration

**Recommendation:** Start with Hobby, upgrade to Pro when launching.

---

## 4. WhatsApp Cloud API Setup

**Service:** Automated WhatsApp Notifications  
**Cost:** First 1,000 conversations/month free, then ~LKR 2-50 per message  
**Setup Time:** 2-3 days (includes verification)

### Steps:

1. **Create Meta Business Account:**
   - Go to [business.facebook.com](https://business.facebook.com)
   - Click "Create Account"
   - Business Name: Colombo Cargo Connect
   - Your Name: [Your Name]
   - Business Email: admin@colombocargo.lk
   - Verify email

2. **Add WhatsApp Product:**
   - Business Manager → "Add Assets" → "Apps"
   - Create App → "Business" type
   - App Name: CCC WhatsApp Integration
   - Contact Email: admin@colombocargo.lk
   - Select Business Account
   - Click "Create App"

3. **Set Up WhatsApp:**
   - App Dashboard → "Add Products" → "WhatsApp"
   - Choose "Use a test number" (for initial setup)
   - Note: Phone Number ID and Access Token

4. **Complete Business Verification:**
   - Business Manager → Business Settings → Security Center
   - Start Verification:
     - Business Registration Certificate
     - Business Address Proof
     - Business Website
     - Facebook Page for business
   - Verification takes 2-3 business days

5. **Add Production Phone Number:**
   - After verification: WhatsApp → API Setup
   - Add Phone Number → New Number
   - Enter business phone number (+94771234567)
   - Verify ownership (SMS code)

6. **Create Message Templates:**
   - WhatsApp → Message Templates → Create Template
   
   **Template 1: booking_confirmed**
   ```
   Category: TRANSACTIONAL
   Name: booking_confirmed
   Language: English (US)
   
   Header: None
   Body:
   Your parcel {{1}} has been booked successfully!
   Route: {{2}}
   Departure: {{3}}
   Track: {{4}}
   
   Thank you for choosing CCC! 📦
   
   Footer: Colombo Cargo Connect
   Buttons: None
   ```
   
   **Template 2: in_transit**
   ```
   Category: TRANSACTIONAL
   Name: in_transit
   Language: English (US)
   
   Body:
   Your parcel {{1}} is now in transit!
   Expected delivery: {{2}}
   Track: {{3}}
   ```
   
   **Template 3: delivered**
   ```
   Category: TRANSACTIONAL
   Name: delivered
   Language: English (US)
   
   Body:
   ✅ Your parcel {{1}} has been delivered!
   Delivered to: {{2}}
   Time: {{3}}
   
   Thank you for using CCC!
   ```
   
   Submit all templates for approval (takes 24-48 hours)

7. **Configure Webhook:**
   - WhatsApp → Configuration
   - Webhook URL: `https://api.colombocargo.lk/api/webhooks/whatsapp`
   - Verify Token: Generate random string, save to env var `WHATSAPP_WEBHOOK_TOKEN`
   - Subscribe to: `messages`
   - Click "Verify and Save"

8. **Save Credentials:**
   ```
   WHATSAPP_PHONE_NUMBER_ID=[from API Setup]
   WHATSAPP_ACCESS_TOKEN=[generate long-lived token]
   WHATSAPP_WEBHOOK_TOKEN=[your verify token]
   ```

### Cost Breakdown:
- Free Tier: 1,000 service conversations/month
- Service Conversations: LKR 2-50 per conversation (varies by country)
- Marketing Conversations: Higher cost
- Template Approval: Free

---

## 5. Notify.lk SMS Setup

**Service:** SMS Notifications (Sri Lanka)  
**Cost:** ~LKR 0.50-1.50 per SMS  
**Setup Time:** 1-2 days

### Steps:

1. **Create Account:**
   - Go to [notify.lk](https://notify.lk)
   - Click "Sign Up"
   - Fill registration form:
     - Company Name: Colombo Cargo Connect
     - Contact Person: [Your Name]
     - Email: admin@colombocargo.lk
     - Phone: +94771234567
   - Submit for approval

2. **Complete KYC:**
   - Notify.lk support will contact you
   - Provide:
     - Business Registration Certificate
     - National ID of authorized person
     - Company letterhead
   - KYC approval takes 1-2 business days

3. **Configure Sender ID:**
   - Login → Settings → Sender IDs
   - Request new Sender ID: `CCC` or `ColomboCargo`
   - Provide justification: "Parcel tracking notifications"
   - Sender ID approval takes 24-48 hours

4. **Get API Credentials:**
   - Login → Settings → API Keys
   - Generate new API key
   - Note: User ID and API Key

5. **Test SMS Sending:**
   ```powershell
   curl "https://app.notify.lk/api/v1/send" `
     -d "user_id=YOUR_USER_ID" `
     -d "api_key=YOUR_API_KEY" `
     -d "sender_id=CCC" `
     -d "to=94771234567" `
     -d "message=Test message from CCC"
   ```

6. **Save Credentials:**
   ```
   NOTIFY_LK_USER_ID=[from dashboard]
   NOTIFY_LK_API_KEY=[generated key]
   NOTIFY_LK_SENDER_ID=CCC
   ```

### Cost Breakdown:
- SMS to Sri Lankan numbers: LKR 0.50-1.50 per SMS
- No monthly fees
- Pay-as-you-go or prepaid packages

---

## 6. WebxPay Payment Gateway Setup

**Service:** Online Payment Processing (Sri Lanka)  
**Cost:** 2-3% per transaction  
**Setup Time:** 5-7 business days

### Steps:

1. **Apply for Merchant Account:**
   - Go to [webxpay.com](https://webxpay.com)
   - Click "Become a Merchant"
   - Fill application form
   - Submit required documents:
     - Business Registration Certificate
     - Bank Account Details (Business Account)
     - National ID of Directors
     - Business Profile
     - Website URL

2. **Complete Merchant Verification:**
   - WebxPay team will contact for verification
   - Provide additional documents if requested
   - Verification takes 5-7 business days

3. **Receive Merchant Credentials:**
   - After approval, receive email with:
     - Merchant ID
     - API Key
     - Webhook Secret
     - Sandbox credentials (for testing)

4. **Configure Webhook:**
   - WebxPay Dashboard → Settings → Webhooks
   - Webhook URL: `https://api.colombocargo.lk/api/webhooks/webxpay`
   - Events: `payment.success`, `payment.failed`, `payment.pending`
   - Save

5. **Test in Sandbox:**
   ```powershell
   # Test payment creation
   curl -X POST https://sandbox.webxpay.com/api/v1/payments `
     -H "Authorization: Bearer SANDBOX_API_KEY" `
     -H "Content-Type: application/json" `
     -d '{
       "amount": 1000.00,
       "currency": "LKR",
       "reference": "TEST-001",
       "callback_url": "https://api.colombocargo.lk/api/webhooks/webxpay",
       "return_url": "https://colombocargo.lk/booking/success"
     }'
   ```

6. **Go Live:**
   - WebxPay Dashboard → Switch to Production
   - Update API credentials in Railway env vars:
     ```
     WEBXPAY_MERCHANT_ID=[production merchant ID]
     WEBXPAY_API_KEY=[production API key]
     WEBXPAY_WEBHOOK_SECRET=[production webhook secret]
     ```

### Cost Breakdown:
- Transaction Fee: 2-3% + LKR 5 per transaction
- Setup Fee: May apply (check with WebxPay)
- Monthly Fee: None
- Chargeback Fee: LKR 500 per chargeback

---

## 7. Firebase Cloud Messaging Setup

**Service:** Push Notifications (Mobile Driver App)  
**Cost:** Free  
**Setup Time:** 30 minutes

### Steps:

1. **Create Firebase Project:**
   - Go to [console.firebase.google.com](https://console.firebase.google.com)
   - Click "Add Project"
   - Project name: CCC Production
   - Enable Google Analytics: Yes
   - Click "Create Project"

2. **Add Android App:**
   - Project Overview → "Add app" → Android
   - Android package name: `com.colombocargo.driver`
   - App nickname: CCC Driver
   - Click "Register app"

3. **Download google-services.json:**
   - Follow setup wizard
   - Download `google-services.json`
   - Place in `mobile-driver/android/app/google-services.json`

4. **Get Server Key:**
   - Project Settings → Cloud Messaging
   - Copy "Server key"
   - Save to Railway env vars:
     ```
     FCM_SERVER_KEY=[server key]
     ```

5. **Test Notification:**
   - Install driver app on test device
   - Get device FCM token from app logs
   - Send test notification:
     ```powershell
     curl -X POST https://fcm.googleapis.com/fcm/send `
       -H "Authorization: key=YOUR_SERVER_KEY" `
       -H "Content-Type: application/json" `
       -d '{
         "to": "DEVICE_FCM_TOKEN",
         "notification": {
           "title": "Test Notification",
           "body": "CCC Driver App Test"
         }
       }'
     ```

### Cost Breakdown:
- Free (unlimited notifications)

---

## 8. Sentry Error Tracking Setup

**Service:** Error Monitoring & Tracking  
**Cost:** Free (Developer plan) or $26/month (Team plan)  
**Setup Time:** 20 minutes

### Steps:

1. **Create Account:**
   - Go to [sentry.io](https://sentry.io)
   - Sign up with GitHub
   - Create organization: "Colombo Cargo Connect"

2. **Create Project:**
   - Choose platform: PHP (Laravel)
   - Project name: CCC Backend
   - Click "Create Project"

3. **Install SDK:**
   ```powershell
   cd D:\CCC\backend
   composer require sentry/sentry-laravel
   php artisan sentry:publish --dsn=YOUR_DSN
   ```

4. **Configure:**
   - Add to Railway env vars:
     ```
     SENTRY_LARAVEL_DSN=[your DSN]
     SENTRY_ENVIRONMENT=production
     SENTRY_TRACES_SAMPLE_RATE=0.2
     ```

5. **Repeat for Frontend:**
   - Create projects for each web app
   - Install `@sentry/nextjs`
   - Configure in `next.config.js`

### Cost Breakdown:
- Developer (Free): 5,000 errors/month
- Team ($26/month): 50,000 errors/month

---

## 9. Better Stack Uptime Monitoring Setup

**Service:** Uptime Monitoring & Status Page  
**Cost:** $18/month (Hobby) or $42/month (Startup)  
**Setup Time:** 30 minutes

### Steps:

1. **Create Account:**
   - Go to [betterstack.com/uptime](https://betterstack.com/uptime)
   - Sign up
   - Choose plan: Hobby or Startup

2. **Add Monitors:**
   
   **Monitor 1: Backend API Health**
   - URL: `https://api.colombocargo.lk/api/health`
   - Method: GET
   - Interval: 1 minute
   - Regions: Asia Pacific
   - Alert when: Down for 2 minutes
   
   **Monitor 2: Sender Portal**
   - URL: `https://colombocargo.lk`
   - Interval: 3 minutes
   
   **Monitor 3: Admin Console**
   - URL: `https://admin.colombocargo.lk`
   - Interval: 5 minutes
   
   **Monitor 4: Hub Console**
   - URL: `https://hub.colombocargo.lk`
   - Interval: 5 minutes
   
   **Monitor 5: Tracking Page**
   - URL: `https://track.colombocargo.lk`
   - Interval: 3 minutes

3. **Configure Alerts:**
   - Alerts → Add Integration → SMS
   - Phone: +94771234567
   - Alerts → Add Integration → Email
   - Email: alerts@colombocargo.lk

4. **Create Status Page (Optional):**
   - Status Pages → Create
   - Name: CCC Status
   - URL: status.colombocargo.lk
   - Add all monitors
   - Public or Private

### Cost Breakdown:
- Hobby ($18/month): 10 monitors, 60-second checks
- Startup ($42/month): 20 monitors, 30-second checks

---

## 10. Domain & DNS Setup

**Service:** Domain Registration & DNS Management  
**Cost:** ~$15/year per domain  
**Setup Time:** 1 hour + propagation time

### Steps:

1. **Register Domain:**
   - Registrar: Namecheap, GoDaddy, or any registrar
   - Register: `colombocargo.lk` (or .com if .lk unavailable)
   - Duration: 1-3 years

2. **Configure DNS Records:**
   
   **A Records (for domains):**
   ```
   Type: A
   Name: @
   Value: 76.76.21.21  (Vercel)
   TTL: 3600
   
   Type: A
   Name: app
   Value: 76.76.21.21  (Vercel)
   TTL: 3600
   ```
   
   **CNAME Records (for subdomains):**
   ```
   Type: CNAME
   Name: admin
   Value: cname.vercel-dns.com
   TTL: 3600
   
   Type: CNAME
   Name: hub
   Value: cname.vercel-dns.com
   TTL: 3600
   
   Type: CNAME
   Name: track
   Value: cname.vercel-dns.com
   TTL: 3600
   
   Type: CNAME
   Name: api
   Value: [your-project].railway.app
   TTL: 3600
   ```
   
   **MX Records (for email):**
   ```
   Type: MX
   Name: @
   Value: [mail server from email provider]
   Priority: 10
   TTL: 3600
   ```

3. **Wait for DNS Propagation:**
   - Typically takes 5 minutes to 24 hours
   - Check: `nslookup colombocargo.lk`

---

## Completion Checklist

After completing all setups, verify:

- [ ] Supabase production database running with PostGIS
- [ ] Railway backend API deployed and accessible
- [ ] All 4 Vercel web apps deployed with custom domains
- [ ] WhatsApp templates approved and sending
- [ ] Notify.lk SMS sending successfully
- [ ] WebxPay payment processing working
- [ ] Firebase push notifications delivering
- [ ] Sentry capturing errors
- [ ] Better Stack monitoring all services
- [ ] All DNS records propagated
- [ ] SSL certificates active on all domains

---

## Total Cost Estimate

| Service | Monthly Cost |
|---------|-------------|
| Supabase (Pro) | $25 |
| Railway (Pro + resources) | $30 |
| Vercel (Hobby) | $0 (or $20 for Pro) |
| WhatsApp (est. 5,000 conversations) | $50 |
| Notify.lk SMS (est. 3,000 SMS) | $30 |
| WebxPay (2.5% of revenue) | Variable |
| Firebase FCM | $0 |
| Sentry (Team) | $26 |
| Better Stack (Hobby) | $18 |
| Domain Registration | $1.25 |
| **Total Fixed Costs** | **~$180-200/month** |

**Variable Costs:**
- WebxPay: 2.5% of payment volume
- WhatsApp: After free tier, ~$0.01-0.05 per message
- SMS: ~$0.01 per SMS

---

**Last Updated:** May 23, 2026  
**Next Review:** Monthly (as services are onboarded)
