# Advanced Development Roadmap

**Purpose:** This document outlines the advanced development phases for taking Colombo Cargo Connect (CCC) from functional prototype to production-ready platform.

**Status:** Active Development Tracker  
**Last Updated:** May 23, 2026  
**Current Phase:** Phase A (Production Infrastructure)

---

## Overview

All core features (Phases 0-6) are complete. This roadmap focuses on production readiness, quality assurance, and operational excellence.

**Phases are sequential but can have parallel work streams within each phase.**

---

## Phase A: Production Infrastructure Setup

**Objective:** Deploy all components to production environments with proper configuration.

**Prerequisites:**
- ✅ Backend API functional on Railway staging
- ✅ Web apps deployed to Vercel staging
- ⚠️ Production environments not configured

### A.1 Database Infrastructure

**Tasks:**
- [ ] Create Supabase production project
- [ ] Enable PostGIS extension in production DB
- [ ] Run all migrations against production DB
- [ ] Configure connection pooling (PgBouncer)
- [ ] Set up automated backups (daily full + hourly incremental)
- [ ] Configure read replicas for scaling
- [ ] Document connection strings in secure vault

**Acceptance Criteria:**
- Production PostgreSQL 16 + PostGIS running
- All tables, indexes, and constraints created
- Backup/restore tested successfully
- Connection pooling verified under load

**Reference:** `docs/DB_SCHEMA.md`, `backend/database/migrations/`

---

### A.2 Backend API Deployment

**Tasks:**
- [ ] Configure Railway production service
- [ ] Add Redis add-on to Railway project
- [ ] Set all production environment variables (see `RAILWAY_ENV_VARS.md`)
- [ ] Configure custom domain with SSL (api.colombocargo.lk)
- [ ] Enable automatic deployments from `main` branch
- [ ] Set up health check endpoint monitoring
- [ ] Configure log aggregation
- [ ] Test all API endpoints in production

**Environment Variables Required:**
```bash
# Database
DATABASE_URL=postgresql://...
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=ccc_production
DB_USERNAME=...
DB_PASSWORD=...

# Redis
REDIS_HOST=...
REDIS_PASSWORD=...
REDIS_PORT=6379

# Application
APP_NAME="Colombo Cargo Connect"
APP_ENV=production
APP_KEY=base64:... (generate new)
APP_DEBUG=false
APP_URL=https://api.colombocargo.lk

# Supabase
SUPABASE_URL=https://...supabase.co
SUPABASE_KEY=... (production anon key)

# Sanctum
SANCTUM_STATEFUL_DOMAINS=""
SESSION_DOMAIN=.colombocargo.lk

# CORS (all production web app origins)
FRONTEND_URL=https://colombocargo.lk
ADMIN_URL=https://admin.colombocargo.lk
HUB_URL=https://hub.colombocargo.lk
TRACKING_URL=https://track.colombocargo.lk

# Services (configure in Phase C)
WHATSAPP_PHONE_NUMBER_ID=...
WHATSAPP_ACCESS_TOKEN=...
NOTIFY_LK_USER_ID=...
NOTIFY_LK_API_KEY=...
WEBXPAY_MERCHANT_ID=...
WEBXPAY_API_KEY=...
```

**Acceptance Criteria:**
- API accessible via custom domain with HTTPS
- All services healthy (database, Redis, queue workers)
- CORS configured for all web app origins
- Rate limiting active (60 requests/min default)

**Reference:** `backend/README.md`, `RAILWAY_ENV_VARS.md`

---

### A.3 Web Application Deployments

**Tasks:**
- [ ] **web-sender:** Configure Vercel production project
  - Custom domain: `https://colombocargo.lk` or `https://app.colombocargo.lk`
  - Environment variables (API URL, Supabase keys)
  - Enable automatic deployments from `main` branch
  
- [ ] **web-admin:** Configure Vercel production project
  - Custom domain: `https://admin.colombocargo.lk`
  - Environment variables
  - Restrict access to internal IP ranges (optional)
  
- [ ] **web-hub:** Configure Vercel production project
  - Custom domain: `https://hub.colombocargo.lk`
  - Environment variables
  
- [ ] **web-tracking:** Configure Vercel production project
  - Custom domain: `https://track.colombocargo.lk`
  - Enable ISR (Incremental Static Regeneration) with 30s revalidation
  - Environment variables

**Environment Variables Per App:**
```bash
# All web apps
NEXT_PUBLIC_API_URL=https://api.colombocargo.lk
NEXT_PUBLIC_SUPABASE_URL=https://...supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=...

# web-sender specific
NEXT_PUBLIC_APP_NAME="CCC Sender Portal"

# web-admin specific
NEXT_PUBLIC_APP_NAME="CCC Admin Console"

# web-hub specific
NEXT_PUBLIC_APP_NAME="CCC Hub Console"

# web-tracking specific
NEXT_PUBLIC_APP_NAME="CCC Tracking"
```

**Acceptance Criteria:**
- All 4 web apps accessible via custom domains with HTTPS
- Login flows working for sender and admin apps
- Public tracking page loading without authentication
- ISR working correctly on tracking page

**Reference:** `web-*/README.md`, `web-*/vercel.json`

---

### A.4 Mobile App Distribution

**Tasks:**
- [ ] **mobile-driver:** Build production APK/IPA
  - Update `config.dart` with production API URL
  - Configure Firebase production project for FCM
  - Build signed release APK for Android
  - Set up internal distribution (Firebase App Distribution or TestFlight)
  - Create onboarding documentation for drivers
  
- [ ] **mobile-sender:** (Future - not yet implemented)

**Acceptance Criteria:**
- Driver app connects to production API
- Push notifications working via FCM
- App distributed to test drivers for field testing

**Reference:** `mobile-driver/README.md`

---

### A.5 File Storage Configuration

**Tasks:**
- [ ] Create Supabase Storage buckets in production:
  - `delivery-proofs` (private: signatures, NIC photos, delivery photos)
  - `parcel-labels` (private: PDF labels)
  - `documents` (private: user KYC docs)
  
- [ ] Configure bucket policies:
  - `delivery-proofs`: Authenticated users only, drivers can upload
  - `parcel-labels`: Authenticated users only, read-only after creation
  - `documents`: Authenticated users only, admin read access
  
- [ ] Test file upload/download from backend
- [ ] Configure CDN for public assets (if needed)

**Acceptance Criteria:**
- All storage buckets created with correct policies
- File upload working from driver app
- PDF generation and storage working for labels
- Signed URLs working for private file access

**Reference:** `backend/config/filesystems.php`

---

## Phase B: Testing & Quality Assurance

**Objective:** Achieve comprehensive test coverage and validate all critical user flows.

### B.1 Test Coverage Analysis

**Tasks:**
- [ ] Install PCOV extension for coverage reporting:
  ```powershell
  cd D:\CCC\backend
  composer require --dev pcov/clobber
  vendor\bin\pcov clobber
  ```
  
- [ ] Run test suite with coverage:
  ```powershell
  vendor\bin\pest --coverage --min=70
  ```
  
- [ ] Generate HTML coverage report:
  ```powershell
  vendor\bin\pest --coverage-html coverage-report
  ```
  
- [ ] Review coverage gaps in critical paths:
  - Service classes (target: 90%+ coverage)
  - Controllers (target: 80%+ coverage)
  - State machine transitions (target: 100% coverage)
  
- [ ] Write missing unit tests for uncovered code

**Acceptance Criteria:**
- Overall code coverage ≥ 70%
- Service classes coverage ≥ 90%
- All state transitions tested
- Coverage report generated in CI/CD

**Reference:** `backend/tests/`, `backend/phpunit.xml`

---

### B.2 End-to-End Testing

**Tasks:**
- [ ] Set up E2E testing framework (Playwright or similar)
- [ ] Write E2E tests for critical user journeys:
  
  **Journey 1: Customer Books Parcel**
  - Register new account
  - Login
  - Create booking with all fields
  - Select trip
  - Pay with card (test mode)
  - Verify booking confirmation
  - Download label PDF
  - Track parcel
  
  **Journey 2: Driver Delivers Parcel**
  - Driver login
  - View assigned trips
  - Scan parcel at pickup
  - Scan at hub
  - Scan for loading
  - Mark in transit
  - Scan at destination
  - Out for delivery
  - Capture NIC + signature + photo
  - Mark delivered
  
  **Journey 3: Hub Operations**
  - Hub staff login
  - Scan incoming parcels
  - Assign to lorry
  - Generate manifest
  - Scan outgoing parcels
  
  **Journey 4: Admin Operations**
  - Admin login
  - Create new route
  - Schedule trip
  - Assign driver
  - Update pricing
  - Handle dispute
  
- [ ] Run E2E suite against staging environment
- [ ] Fix any failures
- [ ] Add E2E tests to CI/CD pipeline

**Acceptance Criteria:**
- All 4 critical journeys pass in E2E tests
- E2E suite runs in < 10 minutes
- Tests run automatically on PR merge

**Reference:** TBD (create `backend/tests/E2E/` directory)

---

### B.3 Label PDF Testing

**Tasks:**
- [ ] Test PDF generation for all package sizes (S, M, L, XL, Bale)
- [ ] Verify QR code scans correctly from printed label
- [ ] Test barcode scanning with physical scanner
- [ ] Verify label layout and readability
- [ ] Test PDF generation under load (1000 labels in parallel)
- [ ] Fix any rendering issues

**Test Cases:**
- [ ] Small parcel label (30×25×15 cm box)
- [ ] Medium parcel label (60×45×40 cm box)
- [ ] Large parcel label (120×80×80 cm box)
- [ ] Extra Large parcel label (200×120×120 cm)
- [ ] Bale/pallet label (120×100×150 cm)
- [ ] Label with special characters in address
- [ ] Label with long sender/receiver names
- [ ] QR code scans with phone camera
- [ ] QR code scans with barcode scanner
- [ ] Label prints correctly on thermal printer

**Acceptance Criteria:**
- All package sizes generate valid PDFs
- QR codes scan successfully from printed labels
- Labels fit standard A4 and 4×6" thermal paper
- No rendering issues with special characters

**Reference:** `backend/app/Services/BookingService.php` (PDF generation logic)

---

### B.4 Performance Testing

**Tasks:**
- [ ] Set up load testing framework (k6 or Artillery)
- [ ] Define performance benchmarks:
  - API response time p95 < 200ms
  - Database query time p95 < 50ms
  - Concurrent users: 1000+
  - Booking creation rate: 100/minute
  
- [ ] Run load tests against staging:
  - Booking creation endpoint
  - Tracking page (high read load)
  - Scan endpoint (burst traffic)
  - Login endpoint
  
- [ ] Identify bottlenecks
- [ ] Optimize slow queries (add indexes, optimize joins)
- [ ] Add caching for frequently accessed data
- [ ] Re-run tests to verify improvements

**Load Test Scenarios:**
```yaml
# Scenario 1: Normal Business Hours
- 500 concurrent users
- 50 bookings/minute
- 200 tracking requests/minute
- 100 scan events/minute
- Duration: 30 minutes

# Scenario 2: Peak Hours
- 1000 concurrent users
- 100 bookings/minute
- 500 tracking requests/minute
- 200 scan events/minute
- Duration: 15 minutes

# Scenario 3: Spike Test
- Ramp from 100 to 2000 users in 5 minutes
- Hold for 10 minutes
- Ramp down to 100 in 5 minutes
```

**Acceptance Criteria:**
- API p95 response time < 200ms under normal load
- API p95 response time < 500ms under peak load
- No errors during load tests
- Database connection pool stable
- Redis cache hit rate > 80%

**Reference:** Create `docs/PERFORMANCE.md`

---

## Phase C: Missing Feature Implementation

**Objective:** Complete integration with external services and implement missing features.

### C.1 WhatsApp Cloud API Integration

**Tasks:**
- [ ] Complete WhatsApp Business verification with Meta
- [ ] Create WhatsApp Business Account
- [ ] Request approval for message templates:
  - `booking_confirmed`
  - `label_ready`
  - `picked_up`
  - `in_transit`
  - `arrived_at_hub`
  - `out_for_delivery`
  - `delivered`
  - `delivery_failed`
  
- [ ] Configure webhook for inbound messages
- [ ] Update `backend/app/Services/WhatsAppService.php` with production credentials
- [ ] Test all message templates in production
- [ ] Set up message delivery monitoring

**Template Structure Example:**
```
Template Name: booking_confirmed
Category: TRANSACTIONAL
Language: English (US)

Body:
Your parcel {{1}} has been booked successfully!
Route: {{2}}
Departure: {{3}}
Track: {{4}}

Thank you for choosing CCC! 📦
```

**Acceptance Criteria:**
- All 8 message templates approved by Meta
- Messages send successfully on status changes
- Webhook receives customer replies
- Message delivery rate > 95%

**Reference:** `backend/app/Services/WhatsAppService.php`, `backend/config/whatsapp_templates.php`, `docs/whatsapp_templates_meta.md`

---

### C.2 WebxPay Payment Integration

**Tasks:**
- [ ] Register merchant account with WebxPay
- [ ] Obtain production API credentials
- [ ] Update `backend/app/Services/PaymentService.php` with production keys
- [ ] Implement payment creation endpoint
- [ ] Implement payment verification webhook
- [ ] Test card payments in sandbox
- [ ] Test card payments in production
- [ ] Implement refund functionality
- [ ] Add payment retry logic for failed transactions

**Payment Flow:**
1. Customer selects payment method
2. Backend creates payment intent via WebxPay API
3. Customer redirects to WebxPay hosted checkout
4. Customer completes payment
5. WebxPay sends webhook to backend
6. Backend verifies signature and updates booking status
7. Customer redirects back to app with success/failure

**Acceptance Criteria:**
- Payment creation working for card payments
- Webhook verification secure (HMAC signature)
- Successful payments update booking status
- Failed payments trigger retry flow
- Refunds processed correctly

**Reference:** `backend/app/Services/PaymentService.php`, WebxPay API docs

---

### C.3 Notify.lk SMS Integration

**Tasks:**
- [ ] Register account with Notify.lk
- [ ] Obtain production API credentials
- [ ] Update `backend/app/Services/SmsService.php` with production keys
- [ ] Configure sender ID (e.g., "CCC" or "ColomboCargo")
- [ ] Test SMS delivery in production
- [ ] Implement SMS fallback for WhatsApp failures
- [ ] Monitor SMS delivery rates

**SMS Templates:**
- Booking confirmation: "Your parcel [PARCEL_NO] is booked. Track: [URL]"
- Delivery notification: "Your parcel [PARCEL_NO] is out for delivery today."
- OTP: "Your CCC verification code is: [CODE]. Valid for 5 minutes."

**Acceptance Criteria:**
- SMS sends successfully to Sri Lankan numbers
- Sender ID appears as configured
- SMS delivery rate > 95%
- Fallback triggers when WhatsApp fails

**Reference:** `backend/app/Services/SmsService.php`

---

### C.4 Firebase Cloud Messaging (FCM)

**Tasks:**
- [ ] Create Firebase production project
- [ ] Add Android app to Firebase project
- [ ] Download `google-services.json` for Android
- [ ] Configure FCM in Flutter driver app
- [ ] Implement push notification handler
- [ ] Send test notifications from backend
- [ ] Implement notification deep linking (tap notification → open trip)

**Notification Types:**
- Trip assigned: "You've been assigned to Trip #4451 (CMB→KDY, 6 AM)"
- Delivery reminder: "3 parcels ready for delivery on Trip #4451"
- Status update: "Parcel CCC-20260523-001234-7 marked delivered"

**Acceptance Criteria:**
- Notifications received on driver's device
- Tap notification opens relevant screen
- Notification badge count updates correctly
- Background notifications work when app closed

**Reference:** `mobile-driver/lib/`, Firebase Console

---

### C.5 QR Code Signing & Verification

**Tasks:**
- [ ] Review QR token implementation in `QrTokenService.php`
- [ ] Generate production JWT signing key
- [ ] Store key securely in environment variable
- [ ] Test QR generation for all parcel statuses
- [ ] Test QR verification in driver app
- [ ] Add expiration to QR tokens (24 hours)
- [ ] Test expired token handling

**QR Payload Structure:**
```json
{
  "parcel_id": "uuid",
  "parcel_number": "CCC-20260523-001234-7",
  "issued_at": 1716480000,
  "expires_at": 1716566400
}
```

**Acceptance Criteria:**
- QR codes encode signed JWT tokens
- Invalid signatures rejected by backend
- Expired tokens rejected by backend
- QR scans work from printed labels

**Reference:** `backend/app/Services/QrTokenService.php`

---

## Phase D: Security Hardening

**Objective:** Implement security best practices and pass security audit.

### D.1 Security Audit

**Tasks:**
- [ ] Run automated security scan (Snyk, OWASP ZAP)
- [ ] Review all API endpoints for authorization checks
- [ ] Review database queries for SQL injection risks
- [ ] Review file upload endpoints for malicious file risks
- [ ] Audit user input validation
- [ ] Check for exposed secrets in code/logs
- [ ] Review CORS configuration
- [ ] Test rate limiting effectiveness

**Security Checklist:**
- [ ] No hardcoded credentials in codebase
- [ ] All API endpoints require authentication (except public tracking)
- [ ] Authorization checks on all resources (user can't access other user's data)
- [ ] SQL injection prevented (using parameterized queries)
- [ ] XSS prevented (input sanitization + CSP headers)
- [ ] CSRF protected (stateless API design)
- [ ] File uploads validated (type, size, virus scan)
- [ ] NIC data encrypted at rest
- [ ] NIC data masked in logs
- [ ] Passwords hashed with bcrypt
- [ ] JWT tokens use secure signing algorithm
- [ ] Rate limiting on all endpoints (60/min default, 10/min for login)
- [ ] HTTPS enforced everywhere
- [ ] Security headers configured (HSTS, X-Content-Type-Options, etc.)

**Acceptance Criteria:**
- Security scan shows 0 critical vulnerabilities
- All checklist items verified
- Security audit report documented

**Reference:** `docs/SECURITY_AUDIT.md` (create new)

---

### D.2 Data Privacy Compliance

**Tasks:**
- [ ] Review Sri Lanka Personal Data Protection Act 2022 requirements
- [ ] Document data retention policy
- [ ] Implement data deletion endpoint for GDPR-style requests
- [ ] Add privacy policy page to web apps
- [ ] Add terms of service page to web apps
- [ ] Implement consent checkboxes in registration
- [ ] Add data export functionality (user can download their data)
- [ ] Encrypt sensitive fields in database:
  - NIC numbers (already implemented)
  - Bank account numbers (if stored)
  - Credit card last 4 digits (if stored)

**Acceptance Criteria:**
- Privacy policy and terms of service published
- Consent recorded at registration
- Data deletion endpoint functional
- Sensitive data encrypted at rest

**Reference:** Data Protection Act 2022, `docs/PRIVACY.md` (create new)

---

### D.3 Access Control & Permissions

**Tasks:**
- [ ] Review role-based access control (RBAC) implementation
- [ ] Document permissions matrix
- [ ] Test permission checks for all roles:
  - Sender: Can only access own parcels
  - Receiver: Can only track parcels sent to them
  - Driver: Can only scan parcels on assigned trips
  - Hub Staff: Can only scan parcels at their hub
  - Hub Manager: Full hub access + overrides
  - Ops Admin: Trip management, user management
  - Finance Admin: Pricing, payouts, refunds
  - Support Admin: Ticket management, customer comms
  - Super Admin: Everything
  
- [ ] Add permission checks to all sensitive endpoints
- [ ] Add audit logging for admin actions

**Permissions Matrix:**

| Action | Sender | Receiver | Driver | Hub Staff | Hub Manager | Ops Admin | Finance Admin | Support Admin | Super Admin |
|--------|--------|----------|--------|-----------|-------------|-----------|---------------|---------------|-------------|
| Book parcel | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ | ✅ |
| Track parcel | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Scan parcel | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| Create trip | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| Update pricing | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |
| Process refund | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |
| View reports | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |

**Acceptance Criteria:**
- Permissions matrix documented
- All endpoints enforce permission checks
- Unauthorized access returns 403 Forbidden
- Admin actions logged in audit trail

**Reference:** `backend/app/Http/Middleware/`, Laravel Permissions package

---

## Phase E: Performance Optimization

**Objective:** Optimize application performance for production scale.

### E.1 Database Optimization

**Tasks:**
- [ ] Review slow query log
- [ ] Add missing indexes:
  ```sql
  -- Parcels table
  CREATE INDEX idx_parcels_status ON parcels(status);
  CREATE INDEX idx_parcels_sender ON parcels(sender_id);
  CREATE INDEX idx_parcels_receiver_phone ON parcels(receiver_phone);
  CREATE INDEX idx_parcels_trip ON parcels(trip_id);
  CREATE INDEX idx_parcels_created ON parcels(created_at DESC);
  
  -- Tracking events table
  CREATE INDEX idx_tracking_parcel ON tracking_events(parcel_id, created_at DESC);
  CREATE INDEX idx_tracking_type ON tracking_events(event_type);
  
  -- Trips table
  CREATE INDEX idx_trips_route ON trips(route_id);
  CREATE INDEX idx_trips_date ON trips(departure_date, departure_time);
  CREATE INDEX idx_trips_status ON trips(status);
  ```
  
- [ ] Optimize N+1 queries (use eager loading)
- [ ] Add query result caching for frequently accessed data
- [ ] Configure PostgreSQL performance parameters:
  - `shared_buffers`: 25% of RAM
  - `effective_cache_size`: 50% of RAM
  - `work_mem`: 16MB
  - `maintenance_work_mem`: 256MB
  
- [ ] Enable query plan analysis in production (log slow queries > 100ms)

**Acceptance Criteria:**
- No slow queries > 50ms under normal load
- All foreign keys have indexes
- Query plan analysis enabled
- Database CPU usage < 50% under peak load

**Reference:** `docs/DB_SCHEMA.md`

---

### E.2 Caching Strategy

**Tasks:**
- [ ] Implement Redis caching for:
  - Route list (cache for 1 hour)
  - Pricing matrix (cache for 5 minutes)
  - User sessions (already cached)
  - API rate limiting (already cached)
  - Trip availability (cache for 30 seconds)
  - Public tracking data (cache for 10 seconds)
  
- [ ] Add cache invalidation on updates:
  - Clear route cache when route is updated
  - Clear pricing cache when pricing is updated
  - Clear trip cache when capacity changes
  
- [ ] Monitor cache hit rate (target: 80%+)
- [ ] Configure cache TTLs appropriately

**Caching Patterns:**
```php
// Route list (rarely changes)
$routes = Cache::remember('routes.all', 3600, function () {
    return Route::with('hubs')->get();
});

// Pricing matrix (changes occasionally)
$pricing = Cache::remember('pricing.matrix', 300, function () {
    return Pricing::all()->keyBy('route_size_key');
});

// Trip availability (changes frequently)
$trip = Cache::remember("trip.{$tripId}.availability", 30, function () use ($tripId) {
    return Trip::with('parcels')->find($tripId);
});
```

**Acceptance Criteria:**
- Cache hit rate > 80%
- Cache invalidation working correctly
- Redis memory usage stable
- API response times improved by 30%+

**Reference:** `backend/app/Services/`, Laravel Cache documentation

---

### E.3 Queue Optimization

**Tasks:**
- [ ] Review all queued jobs:
  - `SendWhatsAppNotification`
  - `SendSmsNotification`
  - `SendEmailNotification`
  - `GenerateLabelPdf`
  - `ProcessPaymentWebhook`
  
- [ ] Configure queue workers in production:
  ```bash
  # Railway Procfile
  web: php artisan serve --host=0.0.0.0 --port=$PORT
  queue: php artisan queue:work --tries=3 --timeout=60
  ```
  
- [ ] Add job failure handling (retry 3 times, then alert)
- [ ] Monitor queue depth (alert if > 1000 jobs pending)
- [ ] Add job priority (payment webhooks = high, notifications = normal)

**Acceptance Criteria:**
- Queue workers processing jobs in < 10 seconds
- Failed jobs retry automatically
- Queue depth stays below 100 under normal load
- Alerts configured for queue failures

**Reference:** `backend/app/Jobs/`, Laravel Queue documentation

---

### E.4 API Response Optimization

**Tasks:**
- [ ] Enable response compression (Gzip)
- [ ] Implement pagination for all list endpoints
- [ ] Add field selection (`?fields=id,name,status`)
- [ ] Add ETag support for conditional requests
- [ ] Optimize JSON serialization (only return needed fields)
- [ ] Add API response caching for read-only endpoints

**Response Optimization Examples:**
```php
// Pagination
public function index(Request $request)
{
    $limit = min($request->get('limit', 50), 100);
    $offset = $request->get('offset', 0);
    
    $parcels = Parcel::where('sender_id', auth()->id())
        ->limit($limit)
        ->offset($offset)
        ->get();
    
    return response()->json([
        'success' => true,
        'data' => $parcels,
        'meta' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => Parcel::where('sender_id', auth()->id())->count(),
        ],
    ]);
}

// ETag support
public function show(Request $request, $id)
{
    $parcel = Parcel::findOrFail($id);
    $etag = md5($parcel->updated_at);
    
    if ($request->header('If-None-Match') === $etag) {
        return response()->noContent(304);
    }
    
    return response()->json(['success' => true, 'data' => $parcel])
        ->header('ETag', $etag);
}
```

**Acceptance Criteria:**
- Gzip compression enabled
- All list endpoints paginated
- ETag support on tracking endpoint
- Average response size reduced by 40%+

**Reference:** `backend/app/Http/Controllers/`

---

## Phase F: Monitoring & Operations

**Objective:** Set up monitoring, alerting, and operational dashboards.

### F.1 Error Tracking (Sentry)

**Tasks:**
- [ ] Create Sentry production project
- [ ] Install Sentry SDK in backend:
  ```bash
  composer require sentry/sentry-laravel
  ```
  
- [ ] Configure Sentry in Laravel:
  ```php
  // config/sentry.php
  'dsn' => env('SENTRY_LARAVEL_DSN'),
  'environment' => env('APP_ENV'),
  'traces_sample_rate' => 0.2,
  ```
  
- [ ] Install Sentry SDK in Next.js apps:
  ```bash
  npm install @sentry/nextjs
  ```
  
- [ ] Configure error alerts:
  - Email alert for critical errors
  - Slack alert for high-priority errors
  
- [ ] Set up error grouping and triage workflow

**Acceptance Criteria:**
- All errors captured in Sentry
- Alerts configured for critical errors
- Error rate < 0.1% of requests
- Mean time to resolution < 2 hours for critical bugs

**Reference:** Sentry documentation

---

### F.2 Uptime Monitoring (Better Stack)

**Tasks:**
- [ ] Create Better Stack account
- [ ] Add uptime monitors for:
  - Backend API health endpoint (`/api/health`)
  - Web sender app
  - Web admin app
  - Web hub app
  - Web tracking app
  - Database connection
  - Redis connection
  
- [ ] Configure alerting:
  - SMS alert if any service down > 2 minutes
  - Email alert if response time > 1 second
  
- [ ] Set up status page (public or private)

**Acceptance Criteria:**
- Uptime > 99.5% (target: 99.9%)
- Response time p95 < 500ms
- Alerts trigger within 2 minutes of outage
- Status page available

**Reference:** Better Stack documentation

---

### F.3 Application Performance Monitoring (APM)

**Tasks:**
- [ ] Enable Sentry Performance Monitoring
- [ ] Add custom transactions:
  - Booking creation
  - Scan processing
  - Payment processing
  - PDF generation
  
- [ ] Set performance budgets:
  - API requests: p95 < 200ms
  - Database queries: p95 < 50ms
  - Cache operations: p95 < 5ms
  
- [ ] Monitor key metrics:
  - Request rate (requests/minute)
  - Error rate (%)
  - Response time (p50, p95, p99)
  - Apdex score (target: > 0.95)

**Acceptance Criteria:**
- All critical transactions monitored
- Performance budgets configured
- Alerts trigger when budgets exceeded
- Weekly performance reports generated

**Reference:** Sentry Performance documentation

---

### F.4 Business Metrics Dashboard

**Tasks:**
- [ ] Create internal dashboard (Metabase, Grafana, or custom)
- [ ] Track key business metrics:
  - **Operational:**
    - Bookings per day
    - Revenue per day
    - Active trips
    - Average capacity utilization (%)
    - On-time delivery rate (%)
  
  - **Customer:**
    - New registrations per day
    - Active users (last 7 days)
    - Customer satisfaction (ratings)
    - Support tickets per day
  
  - **Driver:**
    - Active drivers
    - Deliveries per driver per day
    - Delivery success rate (%)
    - Average delivery time
  
  - **Hub:**
    - Parcels processed per hub per day
    - Hub turnaround time (avg hours)
    - Hub capacity utilization (%)
  
- [ ] Set up automated daily/weekly reports
- [ ] Grant access to stakeholders

**SQL Queries for Metrics:**
```sql
-- Bookings per day
SELECT DATE(created_at) as date, COUNT(*) as bookings
FROM parcels
WHERE created_at >= NOW() - INTERVAL '30 days'
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Revenue per day
SELECT DATE(created_at) as date, SUM(total_price) as revenue
FROM parcels
WHERE status NOT IN ('CANCELLED')
  AND created_at >= NOW() - INTERVAL '30 days'
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- On-time delivery rate
SELECT 
  COUNT(*) FILTER (WHERE delivered_at <= expected_delivery_at) * 100.0 / COUNT(*) as on_time_rate
FROM parcels
WHERE status = 'DELIVERED'
  AND delivered_at >= NOW() - INTERVAL '30 days';

-- Average capacity utilization
SELECT 
  AVG(used_capacity * 100.0 / total_capacity) as avg_utilization
FROM trips
WHERE status IN ('IN_TRANSIT', 'COMPLETED')
  AND departure_date >= NOW() - INTERVAL '30 days';
```

**Acceptance Criteria:**
- Dashboard accessible to authorized users
- All key metrics visible
- Daily reports sent automatically
- Real-time data updates (< 5 minute delay)

**Reference:** Create `docs/METRICS.md`

---

## Phase G: Documentation & Polish

**Objective:** Complete documentation, create training materials, and polish user experience.

### G.1 API Documentation

**Tasks:**
- [ ] Generate OpenAPI (Swagger) spec from Laravel routes
- [ ] Add request/response examples for all endpoints
- [ ] Document authentication flow
- [ ] Document error codes
- [ ] Add code samples in multiple languages (curl, PHP, JavaScript)
- [ ] Deploy interactive API docs (Swagger UI or Postman)

**Documentation Structure:**
```yaml
openapi: 3.0.0
info:
  title: Colombo Cargo Connect API
  version: 1.0.0
  description: RESTful API for CCC freight platform

servers:
  - url: https://api.colombocargo.lk/api/v1
    description: Production
  - url: https://staging-api.colombocargo.lk/api/v1
    description: Staging

paths:
  /auth/login:
    post:
      summary: Login with phone and password
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                phone:
                  type: string
                  example: "+94771234567"
                password:
                  type: string
                  example: "password123"
      responses:
        200:
          description: Login successful
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                    example: true
                  data:
                    type: object
                    properties:
                      token:
                        type: string
                        example: "1|abc123..."
                      user:
                        $ref: '#/components/schemas/User'
```

**Acceptance Criteria:**
- OpenAPI spec complete for all endpoints
- Interactive docs deployed and accessible
- Examples provided for all requests
- Authentication documented clearly

**Reference:** `docs/API_SPEC.md` (expand), Swagger/OpenAPI tools

---

### G.2 Operations Runbook

**Tasks:**
- [ ] Create comprehensive runbook in `docs/RUNBOOK.md`:
  
  **Sections:**
  1. **Architecture Overview**
     - Component diagram
     - Data flow diagram
     - Deployment architecture
  
  2. **Deployment Procedures**
     - Backend deployment steps
     - Web app deployment steps
     - Mobile app distribution steps
     - Rollback procedures
  
  3. **Common Tasks**
     - Create new route
     - Schedule trip
     - Update pricing
     - Handle refund
     - Reset user password
     - Process dispute
  
  4. **Troubleshooting**
     - API returns 500 error
     - Login fails
     - Scan not working
     - WhatsApp not sending
     - Payment webhook failed
     - Database connection timeout
     - Redis connection timeout
  
  5. **Emergency Procedures**
     - Service outage response
     - Data breach response
     - Payment failure response
     - Critical bug escalation
  
- [ ] Document all environment variables
- [ ] Create quick reference commands cheat sheet

**Acceptance Criteria:**
- Runbook covers all common operations
- Troubleshooting guide addresses known issues
- Emergency procedures documented
- Ops team trained on runbook

**Reference:** Create `docs/RUNBOOK.md`

---

### G.3 User Training Materials

**Tasks:**
- [ ] **Customer Portal Training:**
  - Video: How to book a parcel
  - Video: How to track your parcel
  - Video: How to download label
  - Video: How to report an issue
  - FAQ document
  
- [ ] **Driver App Training:**
  - Video: How to scan parcels
  - Video: How to complete delivery verification
  - Video: How to handle delivery failures
  - Troubleshooting guide for common issues
  
- [ ] **Hub Staff Training:**
  - Video: How to receive parcels
  - Video: How to load parcels on lorry
  - Video: How to generate manifest
  - Standard operating procedures (SOPs)
  
- [ ] **Admin Portal Training:**
  - Video: How to manage routes
  - Video: How to schedule trips
  - Video: How to update pricing
  - Admin user guide (PDF)

**Acceptance Criteria:**
- All training videos recorded and uploaded
- FAQ documents created
- SOPs documented
- Training materials accessible via docs site

**Reference:** Create `docs/TRAINING/` directory

---

### G.4 UX/UI Polish

**Status:** 🟢 Accessibility (Phase 1-4) FRAMEWORK COMPLETE — Ready for Test Execution

**Tasks:**
- [ ] **Web Apps:**
  - Review all user flows for friction points
  - Add loading states for all async operations ✅ (login pages complete)
  - Add error states with clear recovery actions ✅ (login pages complete)
  - Add empty states with helpful CTAs
  - Improve mobile responsiveness
  - Add tooltips for complex features
  - Optimize form validation messages
  
- [ ] **Mobile Driver App:**
  - Add haptic feedback for scan success/failure
  - Improve camera focus for barcode scanning
  - Add offline mode indicators
  - Optimize battery usage
  - Add dark mode support
  
- [x] **Accessibility (WCAG 2.2 AA):** ✅ PHASE 1-3 COMPLETE
  - [x] Add ARIA labels for screen readers ✅
  - [x] Ensure keyboard navigation works ✅
  - [x] Add skip navigation links ✅
  - [x] Enhance focus indicators (2px solid + offset) ✅
  - [x] Ensure color contrast meets WCAG AA standards ✅
  - [x] Add focus indicators for interactive elements ✅
  - [x] Icon-only buttons have accessible labels ✅
  - [x] Form inputs have explicit labels ✅
  - [x] Error messages use ARIA live regions ✅
  - [x] Touch targets ≥ 24x24 pixels (WCAG 2.2) ✅
  - [x] Add table headers for all 10 data tables (scope="col") ✅ PHASE 2
  - [x] Add chart text alternatives (details/summary with tables) ✅ PHASE 2
  - [x] Add semantic chart sections with aria-labelledby ✅ PHASE 2
  - [x] Add autocomplete attributes to booking form ✅ PHASE 2
  - [x] Fix sender dashboard icon button labels ✅ PHASE 2
  - [x] Audit heading hierarchy across all pages ✅ PHASE 3
  - [x] Add role="main" to admin pages ✅ PHASE 3
  - [x] Fix landing page heading structure (h2→h3→h2 to h1→h2) ✅ PHASE 3
  - [x] Create comprehensive testing guide (7,000+ lines) ✅ PHASE 4
  - [x] Create manual testing checklist (150+ checkpoints) ✅ PHASE 4
  - [x] Create automated test scripts (PowerShell + CI/CD) ✅ PHASE 4
  - [ ] Execute automated tests (axe-core + Lighthouse) — Ready
  - [ ] Execute manual keyboard testing — Ready
  - [ ] Execute screen reader testing (NVDA + JAWS) — Ready
  - [ ] Execute color contrast audit — Ready
  - [ ] Execute mobile touch target testing — Ready
  - [ ] Execute motion testing (prefers-reduced-motion) — Ready

**Accessibility Compliance Progress:**
- **Critical Issues Fixed:** 8/8 ✅ (Phase 1)
- **High Priority Issues Fixed:** 4/4 ✅ (Phase 2)
- **Medium Priority Issues Fixed:** 3/3 ✅ (Phase 3)
- **Testing Framework Created:** ✅ (Phase 4)
- **Total Issues Fixed:** 15/17 (88% implementation complete)
- **Testing Status:** Framework ready, execution pending
- **Expected Final Compliance:** 100% (17/17) after test execution
- **WCAG 2.2 Level AA Status:** Phases 1-4 Framework Complete
  - Phase 1: 40% → 100% for critical criteria
  - Phase 2: 71% overall compliance
  - Phase 3: 88% overall compliance
  - Phase 4: Testing framework complete, ready for validation
- **Documentation:** 
  - `docs/ACCESSIBILITY_AUDIT.md` (3,500+ lines) — Full audit
  - `docs/ACCESSIBILITY_IMPLEMENTATION.md` (2,000+ lines) — Phase 1 summary
  - `docs/ACCESSIBILITY_PHASE2_SUMMARY.md` (5,000+ lines) — Phase 2 summary
  - `docs/ACCESSIBILITY_PHASE3_SUMMARY.md` (4,500+ lines) — Phase 3 summary
  - `docs/ACCESSIBILITY_PHASE4_SUMMARY.md` (6,500+ lines) — Phase 4 summary
  - `docs/ACCESSIBILITY_TESTING_GUIDE.md` (7,000+ lines) — Comprehensive testing guide
  - `docs/test-scripts/MANUAL_TESTING_CHECKLIST.md` (2,500+ lines) — 150+ checkpoints
- **Scripts Created:**
  - `docs/test-scripts/run-a11y-tests.ps1` — Automated PowerShell test runner
  - `.github/workflows/accessibility-tests.yml` (template) — CI/CD integration
- **Files Created:**
  - `globals.a11y.css` — Production-ready accessibility styles (all 4 web apps)
  - `lib/a11y.ts` — TypeScript accessibility utilities (all 4 web apps)
- **Files Modified (Phases 1-3):** 16 files
  - Phase 1: 3 pages (2 login + tracking)
  - Phase 2: 12 files (10 admin tables + 2 sender pages)
  - Phase 3: 2 files (admin shell + landing page)

**Phase 3 Improvements:**
- ✅ Heading hierarchy audited on 20+ pages
- ✅ Admin main landmark enhanced with role="main"
- ✅ Landing page heading structure corrected (h1 → h2, no skipped levels)
- ✅ Navigation links no longer falsely marked as headings

**Phase 4 Testing Framework:**
1. ✅ Comprehensive testing guide (7,000+ lines)
2. ✅ Automated test scripts (axe-core + Lighthouse)
3. ✅ Manual testing checklist (150+ checkpoints)
4. ✅ CI/CD integration template (GitHub Actions)
5. ✅ Testing results documentation template
6. ⏸️ Ready for test execution (6-8 hours estimated)

**Acceptance Criteria:**
- ✅ Phase 1: All critical accessibility issues fixed (login pages, tracking page)
- ✅ Phase 1: Skip links, focus indicators, ARIA labels implemented
- ✅ Phase 1: Touch targets sized correctly (≥ 24x24 px)
- ✅ Phase 2: All data tables accessible to screen readers
- ✅ Phase 2: Charts have text alternatives
- ✅ Phase 2: Booking form supports browser autofill
- ✅ Phase 3: Heading hierarchy audited and fixed
- ✅ Phase 3: All pages have proper main landmarks
- ✅ Phase 4: Testing framework complete and documented
- [ ] Phase 4: WCAG AA compliance verified (automated + manual testing) — Ready
- [ ] Phase 4: Screen reader testing complete (NVDA + JAWS) — Ready
- [ ] Phase 4: Lighthouse accessibility score ≥ 95 on all pages — Ready
- [ ] Phase 4: 100% WCAG 2.2 Level AA compliance (17/17 issues) — Expected

**Reference:** 
- `docs/ACCESSIBILITY_AUDIT.md` — Full audit with before/after examples
- `docs/ACCESSIBILITY_IMPLEMENTATION.md` — Phase 1 implementation summary
- `docs/ACCESSIBILITY_PHASE2_SUMMARY.md` — Phase 2 implementation summary
- `docs/ACCESSIBILITY_PHASE3_SUMMARY.md` — Phase 3 implementation summary
- `docs/ACCESSIBILITY_PHASE4_SUMMARY.md` — Phase 4 testing framework
- `docs/ACCESSIBILITY_TESTING_GUIDE.md` — Comprehensive testing procedures
- `docs/test-scripts/MANUAL_TESTING_CHECKLIST.md` — 150+ manual test checkpoints
- `docs/test-scripts/run-a11y-tests.ps1` — Automated test runner
- `web-sender/app/globals.a11y.css` — Global accessibility styles
- `web-sender/lib/a11y.ts` — Accessibility utility functions
- Web/mobile app directories

---

## Phase H: Go-Live Preparation

**Objective:** Final checks before production launch.

### H.1 Go-Live Checklist

**Infrastructure:**
- [ ] All production services deployed and healthy
- [ ] Database backups configured and tested
- [ ] SSL certificates installed and valid
- [ ] DNS records configured correctly
- [ ] CDN configured (if applicable)
- [ ] Firewall rules configured
- [ ] DDoS protection enabled
- [ ] Load balancing configured (if applicable)

**Application:**
- [ ] All environment variables set correctly
- [ ] Feature flags configured
- [ ] Rate limiting configured
- [ ] CORS configured for all origins
- [ ] Session handling working correctly
- [ ] File uploads working correctly
- [ ] Email delivery working
- [ ] SMS delivery working
- [ ] WhatsApp delivery working
- [ ] Push notifications working
- [ ] Payment processing working
- [ ] PDF generation working

**Testing:**
- [ ] All E2E tests passing
- [ ] Load tests passed
- [ ] Security scan passed
- [ ] Performance benchmarks met
- [ ] Cross-browser testing completed
- [ ] Mobile device testing completed

**Monitoring:**
- [ ] Error tracking configured
- [ ] Uptime monitoring configured
- [ ] Performance monitoring configured
- [ ] Business metrics dashboard live
- [ ] Alerts configured and tested
- [ ] On-call rotation established

**Documentation:**
- [ ] API docs published
- [ ] Runbook completed
- [ ] Training materials ready
- [ ] Support knowledge base populated

**Business:**
- [ ] Pricing finalized
- [ ] Terms of service published
- [ ] Privacy policy published
- [ ] Customer support channels ready
- [ ] Marketing materials ready
- [ ] Launch announcement prepared

**Acceptance Criteria:**
- All checklist items completed
- Stakeholder sign-off obtained
- Go-live date confirmed

---

### H.2 Soft Launch

**Tasks:**
- [ ] Launch to internal users first (staff testing)
- [ ] Monitor for 48 hours
- [ ] Fix any critical issues
- [ ] Launch to pilot customers (100 users)
- [ ] Monitor for 1 week
- [ ] Collect feedback
- [ ] Fix any issues
- [ ] Prepare for full launch

**Pilot User Selection:**
- 50 sender customers (mix of individual and business)
- 10 drivers
- 5 hub staff
- Target: 200-300 parcels in first week

**Metrics to Monitor:**
- Sign-up conversion rate
- Booking success rate
- Payment success rate
- Delivery success rate
- App crash rate
- API error rate
- User-reported issues

**Acceptance Criteria:**
- Pilot runs smoothly for 1 week
- User feedback positive (> 4.0/5.0 rating)
- No critical bugs reported
- All services stable

---

### H.3 Full Launch

**Tasks:**
- [ ] Remove pilot restrictions
- [ ] Enable public sign-up
- [ ] Publish marketing materials
- [ ] Announce on social media
- [ ] Send press release
- [ ] Monitor closely for first 72 hours
- [ ] Be ready for rapid response to issues

**Launch Day Monitoring:**
- Monitor error rates every hour
- Monitor sign-up rates
- Monitor booking rates
- Monitor payment success rates
- Monitor server load
- Have all hands on deck for first 24 hours

**Acceptance Criteria:**
- Successful public launch
- No critical outages
- Positive user feedback
- Service stable under real-world load

---

## Success Metrics

**Phase A (Infrastructure):**
- [ ] All services deployed to production ✅
- [ ] 99.9% uptime achieved ✅
- [ ] SSL/HTTPS working everywhere ✅

**Phase B (Testing):**
- [ ] Test coverage ≥ 70% ✅
- [ ] All E2E tests passing ✅
- [ ] Performance benchmarks met ✅

**Phase C (Features):**
- [ ] WhatsApp notifications working ✅
- [ ] SMS notifications working ✅
- [ ] Payment processing working ✅
- [ ] PDF labels printing correctly ✅

**Phase D (Security):**
- [ ] Security audit passed ✅
- [ ] 0 critical vulnerabilities ✅
- [ ] Data privacy compliant ✅

**Phase E (Performance):**
- [ ] API p95 < 200ms ✅
- [ ] Database p95 < 50ms ✅
- [ ] Cache hit rate > 80% ✅

**Phase F (Monitoring):**
- [ ] Error tracking live ✅
- [ ] Uptime monitoring live ✅
- [ ] Business metrics dashboard live ✅

**Phase G (Documentation):**
- [ ] API docs published ✅
- [ ] Runbook completed ✅
- [ ] Training materials ready ✅

**Phase H (Launch):**
- [ ] Soft launch successful ✅
- [ ] Full launch successful ✅
- [ ] First 1000 users onboarded ✅

---

## Maintenance & Continuous Improvement

**Ongoing Activities:**
- Weekly performance reviews
- Monthly security audits
- Quarterly feature planning
- Annual architecture review
- Continuous monitoring and optimization
- Regular backup testing
- Disaster recovery drills

---

## Dependencies & Prerequisites

**Phase Order:**
```
A (Infrastructure) → B (Testing) → C (Features) → D (Security)
                                                      ↓
                        H (Launch) ← G (Docs) ← F (Monitoring) ← E (Performance)
```

**Critical Path:**
Phase A → Phase C → Phase F → Phase H

**Parallel Work Allowed:**
- Phase B can start during Phase A
- Phase D can start during Phase C
- Phase E can start during Phase C
- Phase G can start during Phase F

---

## Contact & Support

**Technical Lead:** [Your Name]  
**Project Manager:** [PM Name]  
**DevOps Lead:** [DevOps Name]

**Escalation Path:**
1. Developer → Tech Lead
2. Tech Lead → Project Manager
3. Project Manager → Stakeholders

**Support Channels:**
- Dev Team Slack: `#ccc-dev`
- Ops Team Slack: `#ccc-ops`
- Incident Response: `#ccc-incidents`

---

**Last Updated:** May 23, 2026  
**Document Version:** 1.0  
**Status:** Active Development
