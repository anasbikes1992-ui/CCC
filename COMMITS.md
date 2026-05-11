# Commit History & Development Log

**Project:** Colombo Cargo Connect (CCC)  
**Repository:** d:\CCC\  
**Start Date:** May 1, 2026  

---

## 📝 Commit Format

All commits follow **Conventional Commits** format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types
- `feat` — New feature
- `fix` — Bug fix
- `docs` — Documentation changes
- `style` — Code style (formatting, missing semicolons, etc.)
- `refactor` — Code refactoring (no functional change)
- `perf` — Performance improvements
- `test` — Test additions/updates
- `chore` — Build, CI, dependencies, etc.
- `ci` — CI/CD configuration

### Scopes
- `backend` — Laravel API
- `web-sender` — Sender web portal
- `web-admin` — Admin console
- `web-hub` — Hub staff console
- `web-tracking` — Public tracking page
- `mobile-sender` — Sender Flutter app
- `mobile-driver` — Driver Flutter app
- `docs` — Documentation
- `infra` — Infrastructure/deployment

### Example Commits

```
feat(backend): add parcel booking endpoint

- Implement POST /api/v1/customer/parcels
- Integrate PricingService for calculations
- Integrate TripAssignmentService for auto-assign
- Generate parcel number + QR token
- Queue WhatsApp notification

Closes #42
```

```
fix(web-sender): handle payment timeout gracefully

- Retry payment status check up to 3x
- Show user-friendly error message
- Store partial payment state for recovery

Fixes #103
```

---

## 🚀 Development Phases & Commits

### Phase 0: Project Initialization (May 1, 2026)

| Commit | Type | Scope | Message |
|--------|------|-------|---------|
| `dd7a9f1` | docs | docs | Add project plan (COLOMBO_CARGO_CONNECT_PLAN.md) |
| `8e2f3c5` | docs | docs | Add development tracker (DEVELOPMENT_TRACKER.md) |
| `b1c8d2a` | docs | docs | Add project context (CLAUDE.md) |
| `f4e6b7c` | docs | docs | Add setup checklist (SETUP_CHECKLIST.md) |
| `a9d1e3f` | docs | docs | Add architecture guide (ARCHITECTURE.md) |
| `c2f5b8e` | docs | docs | Add commit history log (COMMITS.md) |

---

### Phase 1: Backend Foundation (Sprints 1–3)

**Status:** Not started  
**Sprints:** 1, 2, 3  
**Estimated PRs:** 5–8

#### Sprint 1: Backend Scaffolding

- [ ] Scaffold Laravel 11 project
- [ ] Configure database + Redis + environment
- [ ] Implement `/api/health` endpoint
- [ ] Set up UUID primary keys globally
- [ ] Create User model + migrations
- [ ] Configure authentication (Sanctum)

**Expected commits:**
```
chore(backend): scaffold Laravel 11 project
feat(backend): add health check endpoint
feat(backend): configure postgresql + redis
feat(backend): implement UUID primary keys
feat(backend): add User model + authentication
```

#### Sprint 2: Routes & Fleet Domain

- [ ] Create migrations: hubs, lorries, drivers, routes, route_points
- [ ] Create models + relationships
- [ ] Add seeders (Sri Lankan hubs + routes)
- [ ] Create admin endpoints (CRUD)
- [ ] Implement authorization (Spatie roles)

**Expected commits:**
```
feat(backend): add hub + route models + migrations
feat(backend): add lorry + driver domain models
feat(backend): implement route_points + relationships
feat(backend): seed default hubs + routes
feat(backend): add admin endpoints for fleet management
feat(backend): implement role-based authorization
```

#### Sprint 3: Package Sizes & Pricing

- [ ] Create package_sizes table (S, M, L, XL, Bale)
- [ ] Create pricing_matrix table
- [ ] Build PricingService
- [ ] Add admin endpoint for pricing matrix CRUD
- [ ] Integrate WebxPay for payment processing
- [ ] Create Payment + COD models

**Expected commits:**
```
feat(backend): add package sizes + capacity units
feat(backend): add pricing matrix domain
feat(backend): implement PricingService
feat(backend): integrate WebxPay payment gateway
feat(backend): add payment + cod models
feat(backend): add pricing matrix admin endpoints
```

---

### Phase 2: Core Booking Flow (Sprints 4–6)

**Status:** Not started  
**Sprints:** 4, 5, 6  
**Estimated PRs:** 6–10

#### Sprint 4: Parcel Model & Booking Service

- [ ] Create Parcel model + migrations
- [ ] Implement ParcelNumberService (CCC-YYYYMMDD-NNNNNN-X)
- [ ] Implement QrTokenService (signed JWT)
- [ ] Create BookingService orchestrator
- [ ] Add validation for parcel sizes + weights

**Expected commits:**
```
feat(backend): add Parcel model + migrations
feat(backend): implement ParcelNumberService
feat(backend): implement QrTokenService
feat(backend): create BookingService
feat(backend): add input validation for parcels
```

#### Sprint 5: Trip Assignment & Capacity

- [ ] Implement TripAssignmentService (auto-assign)
- [ ] Add trip manual selection endpoint
- [ ] Implement capacity metering (capacity-units)
- [ ] Add trip status transitions
- [ ] Create Trips admin endpoints

**Expected commits:**
```
feat(backend): implement TripAssignmentService
feat(backend): add trip capacity metering
feat(backend): add trip manual selection endpoint
feat(backend): add trip status transitions
feat(backend): add Trips admin CRUD endpoints
```

#### Sprint 6: Sender Web Portal

- [ ] Create sender web project (Next.js)
- [ ] Implement authentication flow (login, register)
- [ ] Add booking form UI + validation
- [ ] Integrate PricingService API calls
- [ ] Add parcel tracking page
- [ ] Implement payment UI (WebxPay integration)

**Expected commits:**
```
feat(web-sender): scaffold Next.js sender portal
feat(web-sender): implement authentication flow
feat(web-sender): add booking form + validation
feat(web-sender): integrate pricing calculations
feat(web-sender): add tracking page
feat(web-sender): integrate payment UI
```

---

### Phase 3: Scanning & Lifecycle (Sprints 7–9)

**Status:** Not started  
**Sprints:** 7, 8, 9  
**Estimated PRs:** 5–8

#### Sprint 7: Driver App Base

- [ ] Create driver Flutter project
- [ ] Implement authentication (Sanctum tokens)
- [ ] Add QR/barcode scanner (mobile_scanner package)
- [ ] Create trip listing page
- [ ] Add parcel list per trip

**Expected commits:**
```
feat(mobile-driver): scaffold Flutter driver app
feat(mobile-driver): implement authentication
feat(mobile-driver): add QR/barcode scanner
feat(mobile-driver): add trip listing page
feat(mobile-driver): add parcel list per trip
```

#### Sprint 8: Scan Events & Validation

- [ ] Create ParcelEvent model + migrations
- [ ] Implement ScanService (status validation)
- [ ] Add POST /driver/parcels/{id}/scan endpoint
- [ ] Implement event logging + photo capture
- [ ] Add scan result UI to driver app

**Expected commits:**
```
feat(backend): add ParcelEvent model + migrations
feat(backend): implement ScanService
feat(backend): add scan endpoint
feat(backend): implement event logging
feat(mobile-driver): add scan result UI
```

#### Sprint 9: Parcel Timeline API

- [ ] Create timeline endpoint (/api/parcels/{id}/timeline)
- [ ] Format events for public tracking
- [ ] Add event filtering + sorting
- [ ] Implement caching (30 sec TTL)

**Expected commits:**
```
feat(backend): add parcel timeline endpoint
feat(backend): implement event formatting
feat(backend): add timeline caching
```

---

### Phase 4: Delivery & Verification (Sprints 10–11)

**Status:** Not started  
**Sprints:** 10, 11  
**Estimated PRs:** 4–6

#### Sprint 10: Delivery Proof Schema

- [ ] Create DeliveryProof model + migrations
- [ ] Implement NIC encryption (Laravel Crypt)
- [ ] Add signature + photo storage (Supabase)
- [ ] Create POST /driver/parcels/{id}/deliver endpoint
- [ ] Implement validation (NIC format, signature size, photo size)

**Expected commits:**
```
feat(backend): add DeliveryProof model + migrations
feat(backend): implement NIC encryption
feat(backend): integrate Supabase storage
feat(backend): add delivery endpoint + validation
```

#### Sprint 11: Driver Delivery Flow

- [ ] Add NIC input screen (driver app)
- [ ] Add signature pad (signature package)
- [ ] Add camera + photo capture
- [ ] Implement delivery submission flow
- [ ] Add delivery success + error handling

**Expected commits:**
```
feat(mobile-driver): add NIC input screen
feat(mobile-driver): add signature pad widget
feat(mobile-driver): add camera + photo capture
feat(mobile-driver): implement delivery submission
feat(mobile-driver): add delivery error handling
```

---

### Phase 5: Notifications & Tracking (Sprints 12–14)

**Status:** Not started  
**Sprints:** 12, 13, 14  
**Estimated PRs:** 5–8

#### Sprint 12: WhatsApp Integration

- [ ] Set up WhatsApp Cloud API (Meta account, business verification)
- [ ] Create WhatsAppService (queued, templated)
- [ ] Implement notification templates (booking_confirmed, in_transit, etc.)
- [ ] Add WhatsApp event logging (sent, delivered, read, failed)
- [ ] Create queue worker for WhatsApp sends

**Expected commits:**
```
chore(backend): add WhatsApp Cloud API credentials to .env
feat(backend): implement WhatsAppService
feat(backend): add notification templates
feat(backend): add notification queue worker
feat(backend): add WhatsApp event logging
```

#### Sprint 13: Public Tracking Page

- [ ] Create tracking page project (Next.js, static-rendered)
- [ ] Implement public parcel lookup (parcel_number)
- [ ] Add progress timeline + status indicators
- [ ] Add map display (Google Maps)
- [ ] Implement caching (ISR + 30 sec TTL)
- [ ] Add "Contact Support" button (wa.me link)

**Expected commits:**
```
feat(web-tracking): scaffold tracking page (Next.js)
feat(web-tracking): add parcel lookup by number
feat(web-tracking): add progress timeline
feat(web-tracking): add Google Maps integration
feat(web-tracking): implement ISR caching
feat(web-tracking): add support contact button
```

#### Sprint 14: Notification Orchestration

- [ ] Add SMS notifications (Notify.lk)
- [ ] Add push notifications (Firebase)
- [ ] Add email notifications (Laravel Mail)
- [ ] Create NotificationService dispatcher
- [ ] Add user notification preferences (channel + frequency)

**Expected commits:**
```
feat(backend): integrate Notify.lk SMS service
feat(backend): integrate Firebase Cloud Messaging
feat(backend): create NotificationService dispatcher
feat(backend): add user notification preferences
feat(backend): add multi-channel notification tests
```

---

### Phase 6: Admin & Operations (Sprints 15–17)

**Status:** Not started  
**Sprints:** 15, 16, 17  
**Estimated PRs:** 6–10

#### Sprint 15: Admin Trip Management

- [ ] Create admin trip dashboard
- [ ] Add trip creation + scheduling
- [ ] Add manual parcel-to-trip assignment
- [ ] Add trip manifest generation (PDF export)
- [ ] Add trip status tracking

**Expected commits:**
```
feat(web-admin): scaffold admin console (Next.js)
feat(web-admin): add trip management dashboard
feat(web-admin): add trip creation + scheduling
feat(web-admin): add manual assignment UI
feat(web-admin): add manifest PDF generation
```

#### Sprint 16: Admin Pricing & Users

- [ ] Add pricing matrix editor (admin UI)
- [ ] Add KYC verification flow (document upload)
- [ ] Add user role management
- [ ] Add hub + lorry management UI
- [ ] Add dispute resolution interface

**Expected commits:**
```
feat(web-admin): add pricing matrix editor
feat(web-admin): add KYC verification flow
feat(web-admin): add user role management
feat(web-admin): add hub + lorry management
feat(web-admin): add dispute resolution interface
```

#### Sprint 17: Finance & Support

- [ ] Add payment tracking dashboard
- [ ] Add refund + settlement interface
- [ ] Add support ticket system (admin UI)
- [ ] Add customer communication interface
- [ ] Add reporting + analytics

**Expected commits:**
```
feat(web-admin): add payment tracking dashboard
feat(web-admin): add refund + settlement interface
feat(web-admin): add support ticket system
feat(web-admin): add customer communication
feat(web-admin): add reporting + analytics
```

---

### Phase 7: Polish & Launch (Sprints 18–20)

**Status:** Not started  
**Sprints:** 18, 19, 20  
**Estimated PRs:** 5–8

#### Sprint 18: Testing & Edge Cases

- [ ] Add comprehensive test suite (Laravel Pest)
- [ ] Add frontend tests (Vitest / Jest)
- [ ] Add E2E tests (Playwright)
- [ ] Test payment scenarios + edge cases
- [ ] Test duplicate scans + network failures

**Expected commits:**
```
test(backend): add comprehensive test suite
test(web-*): add frontend unit + integration tests
test(e2e): add E2E test scenarios
test(backend): add payment edge case tests
test(backend): add scan reliability tests
```

#### Sprint 19: Performance & Caching

- [ ] Optimize database queries (index audit)
- [ ] Add response caching (30 sec TTL)
- [ ] Optimize bundle sizes (code splitting)
- [ ] Add performance monitoring (Sentry, Better Stack)
- [ ] Load testing + scaling verification

**Expected commits:**
```
perf(backend): optimize database queries
perf(backend): add response caching
perf(web-*): optimize bundle sizes + code splitting
perf(backend): add performance monitoring
perf(backend): add load testing
```

#### Sprint 20: Launch Checklist

- [ ] Set up production database + backups
- [ ] Deploy API to DigitalOcean
- [ ] Deploy web to Vercel
- [ ] Configure DNS + SSL
- [ ] Set up monitoring + alerting
- [ ] Create user documentation + FAQs
- [ ] Run smoke tests in production

**Expected commits:**
```
chore(infra): set up production database
chore(infra): configure DigitalOcean + Vercel deployments
chore(infra): set up DNS + SSL
chore(infra): configure monitoring + alerting
docs: add user documentation + FAQs
chore: launch checklist completed
```

---

## 📊 Statistics

### By Phase

| Phase | Sprints | Est. PRs | Est. Commits |
|-------|---------|----------|---|
| Initialization | – | – | 6 |
| 1: Foundation | 1–3 | 5–8 | 15–25 |
| 2: Booking | 4–6 | 6–10 | 25–35 |
| 3: Scanning | 7–9 | 5–8 | 20–30 |
| 4: Delivery | 10–11 | 4–6 | 15–20 |
| 5: Notifications | 12–14 | 5–8 | 20–30 |
| 6: Admin | 15–17 | 6–10 | 25–35 |
| 7: Polish | 18–20 | 5–8 | 20–30 |
| **TOTAL** | **20** | **36–58** | **146–205** |

---

## 📅 Timeline

Assuming:
- 1 sprint = 1 week
- 1 PR per sprint (plus minor commits)
- Average 4–5 commits per PR

**Estimated timeline:**

| Phase | Start | End | Duration |
|-------|-------|-----|----------|
| Initialization | May 1 | May 1 | 1 day |
| Phase 1 | May 5 | May 24 | 3 weeks |
| Phase 2 | May 26 | Jun 14 | 3 weeks |
| Phase 3 | Jun 16 | Jul 5 | 3 weeks |
| Phase 4 | Jul 7 | Jul 21 | 2 weeks |
| Phase 5 | Jul 23 | Aug 11 | 3 weeks |
| Phase 6 | Aug 13 | Sep 1 | 3 weeks |
| Phase 7 | Sep 3 | Sep 22 | 3 weeks |
| **Total** | **May 1** | **Sep 22** | **~20 weeks** |

---

## 🔍 Commit Review Checklist

Before committing, verify:

- [ ] Code follows project conventions (PSR-12 for PHP, ESLint for TS)
- [ ] All tests pass (locally)
- [ ] No console.log / var_dump / dd() left in code
- [ ] No secrets in code (.env not committed)
- [ ] Commit message follows Conventional Commits
- [ ] Commit message has scope + subject + body
- [ ] Related issue # referenced in footer (if applicable)
- [ ] Code formatted (Prettier + Pint)
- [ ] Linting passed (ESLint + Pest + PHPStan)

---

## 🚨 Important Notes

1. **Never force-push to main** — Always create a branch + PR
2. **PR review before merge** — At minimum, self-review the diff
3. **Test before commit** — Run tests locally first
4. **One feature per PR** — No mega-PRs combining unrelated work
5. **Revert commits format** — `revert: <original-subject>` with reason
6. **Hotfix commits** — Same as feature, but merge to main + main is tagged

---

**Last Updated:** May 1, 2026  
**Status:** Foundation Phase  
**Next Review:** After Sprint 1 Completion
