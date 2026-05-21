# 🗺️ Colombo Cargo Connect — Phased Development Roadmap

**Version:** 2.0
**Owner:** Anaz (solo dev + Claude)
**Scope:** Lean MVP — Backend + Sender Web + Driver App + Public Tracking + WhatsApp
**Pilot route:** Colombo ↔ Kandy (CMB↔KDY) only
**Deferred to v1.1+:** Admin Portal, Hub Console, Sender Mobile App, additional routes, card payments (WebxPay)

> **No time estimates.** Each phase ships when its acceptance criteria pass. The next phase does not start until the previous one is `Done`. Use this file as the single source of truth for what gets built when. Pair it with `DEVELOPMENT_TRACKER.md` (legacy sprint outline), `CLAUDE.md` (per-session context), and the live task tracker.

---

## 0. How this roadmap works

Each phase has:
- **Goal** — the one outcome that defines "done"
- **Deliverables** — concrete artifacts (code, endpoints, screens, configs)
- **Acceptance criteria** — observable, testable proof it works
- **Risks & mitigations**
- **Status** — `Not started` / `In progress` / `Done`

**Rules of progression:**
1. Only one phase is `In progress` at a time.
2. A phase moves to `Done` only when **every** acceptance criterion passes — verified, not assumed.
3. The next phase starts only after the previous is `Done`.
4. Cross-cutting workstreams (tests, docs, security, observability) get a slice of every phase — they are never deferred.

---

## 1. Critical-path overview

```
Phase 0 ── Phase 1 ──┬── Phase 2 (Sender Web) ──┐
   Setup    Backend  │                          ├── Phase 5 ── Phase 6
                     └── Phase 3 (Driver App) ──┤  WhatsApp    Pilot
                              └── Phase 4 ──────┘
                                Delivery Proof
```

Phases 2 and 3 may interleave (different stacks), but the backend contract from Phase 1 must be locked before either begins.

---

## 2. Phase summary

| # | Phase | Outcome | Status |
|---|---|---|---|
| 0 | Foundations & accounts | Local env runs; all third-party accounts requested; repos initialized | Done |
| 1 | Backend core + domain | Laravel API with parcels, trips, pricing, scans, tracking | Done |
| 2 | Sender Web + Public Tracking | Customer can book + pay (COD/bank) + see tracking page | Done |
| 3 | Driver App + scanning | Driver scans through full lifecycle; status updates persist | Done |
| 4 | Delivery verification | NIC + signature + photo captured, encrypted at rest | Not started |
| 5 | WhatsApp + notifications | Templated WhatsApp + SMS fire on every status change | Not started |
| 6 | Hardening & pilot launch | E2E tests pass; deployed; first 50 real parcels move | Not started |
| 7+ | Post-MVP (deferred) | Admin portal, hub console, more routes, card payments, sender mobile app | Backlog |

---

## 3. Phase 0 — Foundations & Accounts

**Goal:** Everything needed to start coding Phase 1 is in place, and slow external approvals are kicked off so they don't block Phase 5.

### Deliverables
- Local stack running: PHP 8.3, Composer, Node 20, PostgreSQL 16 + PostGIS, Redis, Flutter 3.x
- Single GitHub mono-repo `ccc/` with `backend/`, `web-sender/`, `web-tracking/`, `mobile-driver/` folders matching `DEVELOPMENT_TRACKER.md`
- `.env.example` files for each app
- Third-party accounts **requested**:
  - **WhatsApp Cloud API** (Meta business verification + display name + phone number) — submit first
  - Supabase project
  - Notify.lk
  - Firebase project (FCM)
  - DigitalOcean account
  - Vercel account
- `docs/adr/0001-tech-stack.md` ADR

### Acceptance criteria
- `php artisan --version` runs
- `psql -c "CREATE EXTENSION postgis;"` succeeds on local DB
- `redis-cli ping` returns `PONG`
- `flutter doctor` is clean (no red items)
- WhatsApp business verification submitted (status visible in Meta Business Suite)
- Supabase project URL + service-role key stored in local `.env` (never committed)

### Risks
- WhatsApp approval can take weeks. **Mitigation:** submit on day one of this phase; design SMS fallback as a first-class channel.

### Status
`Done`

---

## 4. Phase 1 — Backend Core + Domain

**Goal:** A Laravel API that can accept a booking, assign it to a trip, generate a labeled parcel with QR, accept lifecycle scans, and serve a public tracking response. No frontend — verified via Pest tests + Postman.

### Deliverables

**Scaffold & auth**
- `composer create-project laravel/laravel backend`
- Sanctum auth installed
- Spatie Permission installed (roles: `customer`, `driver`, `hub_staff`, `admin_super`)
- `HasUuid` trait baseline; UUID primary keys everywhere
- `/api/health` returns `{ ok: true, version, db: 'up', redis: 'up' }`
- Pest test for health endpoint
- Docker Compose for local DB + Redis (recommended, not required)

**Reference domain (static data)**
Migrations + Eloquent models + seeders:
- `users` (with role)
- `hubs` (Colombo Hub, Kandy Hub)
- `routes` (CMB-KDY both directions)
- `lorries` (small / medium / large seeded)
- `drivers` (linked to user)
- `package_sizes` (S/M/L/XL/Bale per `CLAUDE.md`)
- `pricing_matrix` (CMB↔KDY × all sizes; surcharges in JSONB)

**Booking + scanning + tracking (dynamic data)**
Migrations + models:
- `trips` — auto-generated 14 days ahead via `php artisan trips:generate`
- `parcels` — status enum, qr_token, capacity_units, price
- `parcel_events` — full audit trail of every scan
- `delivery_proofs` — table created, fully wired in Phase 4
- `payments` — status, method, amount; COD + bank transfer enums

**Services** (per `CLAUDE.md`)
- `PricingService::quote(routeId, sizeCode, surcharges[]): Money`
- `ParcelNumberService::generate(): string` — `CCC-YYYYMMDD-NNNNNN-X` with check digit
- `QrTokenService::sign(parcelId): jwt` and `verify(jwt): parcelId`
- `TripAssignmentService::nextAvailable(routeId, capacityUnits): Trip`
- `ScanService::record(parcelId, eventType, actor, geo): ParcelEvent` — strictly validates 10-stage transitions

**Endpoints** (per `ARCHITECTURE.md`)
- `POST /api/v1/auth/register`, `/login`, `/logout`
- `POST /api/v1/customer/parcels`
- `GET /api/v1/customer/parcels` and `GET /api/v1/customer/parcels/{id}`
- `GET /api/v1/driver/trips` and `GET /api/v1/driver/trips/{id}/parcels`
- `POST /api/v1/driver/parcels/{id}/scan`
- `POST /api/v1/driver/parcels/{id}/deliver` (stub — full proof in Phase 4)
- `GET /api/v1/public/parcels/{parcel_number}/track` — cached 30 sec

### Acceptance criteria
- Pest test: book a parcel → assigned to a trip → 4 sequential scans (PICKED_UP → RECEIVED_AT_ORIGIN_HUB → LOADED_ON_LORRY → IN_TRANSIT) → tracking endpoint returns full timeline
- Invalid transition (e.g. BOOKED → DELIVERED) returns HTTP 422
- Trip capacity decrements correctly; the booking that would push capacity over 300 units fails with `TRIP_FULL`
- Pricing test covers all 5 sizes × CMB↔KDY × every surcharge combination
- `php artisan trips:generate` creates 14 days of CMB↔KDY trips at 06:00 and 14:00
- All endpoints return the standard JSON envelope (`success`, `data`, `error`, `meta`)
- API spec (`docs/API_SPEC.md`) and DB schema (`docs/DB_SCHEMA.md`) updated to reflect what was built
- ≥ 70% Pest coverage on services

### Risks
- Status-transition matrix has many edges. **Mitigation:** single `ParcelStatus` enum with `canTransitionTo()` method + exhaustive test of every legal and illegal pair.
- UUID + PostGIS + JSONB on Windows. **Mitigation:** keep DB on Linux container or WSL2.

### Status
`Done`

---

## 5. Phase 2 — Sender Web + Public Tracking Page

**Goal:** A customer lands on `sender.cargo.lk`, registers, books a parcel CMB→KDY, pays via COD or bank transfer, downloads a label PDF, and shares a tracking link that anyone can open at `track.cargo.lk/{parcel_number}`.

### Deliverables

**Scaffold & auth**
- Two Next.js 15 apps: `web-sender/` and `web-tracking/`, TypeScript strict + Tailwind + shadcn/ui
- Shared `packages/api-client` (typed fetch wrapper for the Laravel API)
- Auth: phone + password (OTP deferred to v1.1)
- Mobile-first layouts; placeholder brand palette ok

**Booking wizard (4 steps)**
1. Route + departure date/time (only CMB↔KDY shown)
2. Package size + dims + weight (live capacity-unit hint)
3. Pickup point (hub vs doorstep) + drop point (hub vs doorstep) + receiver phone
4. Review + price breakdown + payment method (COD or Bank Transfer)

On submit: `POST /api/v1/customer/parcels` → success page with parcel number, QR (rendered client-side from `qr_token`), barcode (Code-128), and "Download Label" button (server-rendered PDF).

**Public tracking page**
- Route: `/[parcel_number]` on `web-tracking`
- ISR with 30-sec revalidation (matches API cache TTL)
- Renders current status, stepper UI for the 10-stage lifecycle, event log, ETA hint
- No login. `noindex` so customer URLs don't leak to search.

### Acceptance criteria
- New visitor can register, book a CMB→KDY medium parcel with doorstep pickup, see correct price (matches Phase 1 `PricingService` output), receive a parcel number, and download a 4×6 label PDF with QR + barcode
- Public tracking page loads under 1.5 s FCP on throttled 3G
- Lighthouse accessibility ≥ 90 on the booking wizard
- All API errors render as user-friendly messages (no raw stack traces)

### Risks
- Label PDF rendering. **Mitigation:** server-side via `barryvdh/laravel-dompdf`; simpler for the driver to re-print.

### Status
`Done`

---

## 6. Phase 3 — Driver App + Scanning

**Goal:** Driver opens the Flutter app, sees today's trip, scans parcel QRs through pickup → hub-in → load → in-transit → hub-out → out-for-delivery. Status flows back to the public tracking page.

### Deliverables

**Scaffold & auth**
- `flutter create mobile_driver` with Provider state, `dio` for HTTP, `mobile_scanner` for QR
- Login screen (phone + password, same Sanctum tokens)
- Token persisted in `flutter_secure_storage`
- Home screen: today's trip card (route, departure, lorry, parcel count) → tap → parcels list

**Scan flow with offline buffer**
- Camera scan screen with `mobile_scanner`
- Decoded JWT → `POST /api/v1/driver/parcels/{id}/scan` with `event_type`, lat/lng, device id
- Failed POSTs queue in local SQLite, retry on reconnect
- Manual entry fallback (type parcel number) for damaged QR
- Toast: "Scanned. Next: <next parcel>"

### Acceptance criteria
- Driver scans 10 parcels at the hub → all 10 transition to `RECEIVED_AT_ORIGIN_HUB` server-side
- Plane-mode test: scan 3 parcels offline → reconnect → all 3 sync within 30 sec
- Invalid scan (e.g. already-DELIVERED parcel) shows a clear error; client state is not corrupted
- Public tracking page reflects each scan within 30 sec

### Risks
- Android 14+ camera permissions. **Mitigation:** test on real device early; use `permission_handler`.
- Battery drain. **Mitigation:** scanner pauses after each scan; explicit "Scan next" button.

### Status
`Done`

---

## 7. Phase 4 — Delivery Verification

**Goal:** Driver completes the DELIVERED step end-to-end with NIC, signature, and optional photo. NIC encrypted at rest. Signature + photo uploaded to Supabase Storage.

### Deliverables
- Driver app: delivery screen with NIC text field (validates 9V/9X or 12 digits), `signature_pad` widget, camera capture button
- Driver app: submit to `POST /api/v1/driver/parcels/{id}/deliver` with multipart payload
- Backend: `DeliveryProofController` validates payload, encrypts NIC via `Crypt::encryptString()`, uploads files to Supabase Storage, writes `delivery_proofs` row, transitions parcel to `DELIVERED`
- Logging: NIC masked everywhere (`xxxxxxxxV` → `******123V`)
- `docs/privacy.md` covering NIC retention + Data Protection Act 2022 alignment

### Acceptance criteria
- Pest test: deliver endpoint with valid payload → DB row created with encrypted NIC, files uploaded to Supabase, parcel status DELIVERED
- Validation rejects: blank signature (< 5 KB), photo > 5 MB, malformed NIC
- DB inspection: `SELECT receiver_nic_encrypted FROM delivery_proofs LIMIT 1` returns ciphertext, not plaintext
- API response and tracking page show "Delivered to <masked NIC> at <time>" — NIC never returned in plaintext over the wire
- No NIC values appear unmasked in `storage/logs/laravel.log`

### Risks
- Slow rural networks. **Mitigation:** queue file uploads locally; mark parcel "Delivered (sync pending)" until ack.

### Status
`Not started`

---

## 8. Phase 5 — WhatsApp + Notifications

**Goal:** Every parcel status change fires a templated WhatsApp message to sender and/or receiver, with SMS fallback via Notify.lk if WhatsApp delivery fails or the template is not yet approved.

### Deliverables
- Meta WhatsApp Cloud API configured (templates submitted in Phase 0 should be approved by now)
- Approved templates: `booking_confirmed`, `parcel_picked_up`, `in_transit`, `arrived_destination`, `out_for_delivery`, `delivered`, `delivery_failed`
- `WhatsAppService` (queueable Laravel job) wraps `POST https://graph.facebook.com/v21.0/{phone_number_id}/messages`
- `NotificationService` picks channel (WhatsApp first, SMS fallback), logs every send to `notifications_log`
- Inbound webhook endpoint for delivery receipts + customer replies (parked for v1.1; just acknowledges 200 OK for now)
- Hooked into `ScanService` — every status transition dispatches a notification job

### Acceptance criteria
- E2E: book a parcel → sender's WhatsApp receives `booking_confirmed` within 30 sec
- WhatsApp send failure → SMS fallback fires automatically; both attempts visible in `notifications_log`
- All template content matches Meta-approved variants (no free-text sends)
- Webhook returns 200 to Meta delivery receipts within 5 sec

### Risks
- **Biggest external dependency.** Template rejection or approval delay. **Mitigation:** Phase 0 submission; SMS fallback is first-class so the product still ships if WhatsApp slips.

### Status
`Not started`

---

## 9. Phase 6 — Hardening & Pilot Launch

**Goal:** Real parcels move on a real lorry between Colombo and Kandy. Target: 50 successful end-to-end parcels.

### Deliverables

**Hardening**
- E2E test scenarios: happy path, oversize, capacity-full, payment-failed, delivery-failed-then-retry, offline driver, duplicate scan
- Sentry wired in backend + Next.js + Flutter
- Better Stack uptime monitor on `/api/health` and the tracking page
- Rate limiting verified (5/min auth, 100/min public, 1000/hr customer)
- DB dump audit: every `Crypt`-protected field is non-plaintext
- Secrets audit: nothing sensitive in repo history

**Deploy**
- Backend → DigitalOcean App Platform (containerized Laravel + Redis + managed Postgres)
- Sender web + tracking → Vercel
- Driver app → internal APK distribution (or Play Store closed track if dev account exists)
- DNS: `api.cargo.lk`, `sender.cargo.lk`, `track.cargo.lk`
- Production seed: CMB↔KDY only, 1 lorry, 1 driver

**Pilot**
- Onboard 5 friendly senders
- Run 2 trips/day for 5 days
- Daily self-review of Sentry + tracking metrics
- Capture: # bookings, # successful scans, # failed deliveries, # WhatsApp send failures, customer feedback

### Acceptance criteria — "MVP done"
- 50+ parcels successfully booked, scanned through every stage, and delivered
- < 1 critical Sentry error per 100 parcels
- ≥ 95% WhatsApp delivery success
- Public tracking page p95 < 1 s
- API p95 < 500 ms
- Zero NIC leaks in logs or API responses (re-verified post-deploy)

### Status
`Not started`

---

## 10. Phase 7+ — Post-MVP (deferred, planned)

Once the pilot is stable, plan a v1.1 cycle in this rough order. Each becomes its own mini-roadmap when picked up:

| Order | Item | Why this order |
|---|---|---|
| 1 | **Admin Portal (web-admin)** | Stop running ops via DB/seeders; let non-devs manage trips, pricing, disputes |
| 2 | **More routes** (CMB↔Galle, CMB↔Negombo) | Revenue scale; backend already supports it, just data + ops |
| 3 | **Card payments (WebxPay)** | Reduce COD risk and reconciliation overhead |
| 4 | **Hub Console (web-hub)** | When parcel volume per hub exceeds what driver app + admin can handle |
| 5 | **Sender Mobile App (Flutter)** | When sender web is mostly mobile traffic and PWA isn't enough |
| 6 | **OTP-only auth + KYC for high-value parcels** | Reduce fraud as volume grows |
| 7 | **B2B contracts + branded tracking** | Recurring revenue from shop accounts |
| 8 | **Real-time GPS on lorry** | Better ETAs; needs hardware decision |

### Status
`Backlog`

---

## 11. Cross-cutting workstreams (every phase)

These are not standalone phases — they get a slice of every phase.

| Workstream | What it means in practice |
|---|---|
| **Tests** | Pest for backend (≥ 70% coverage on services), Playwright smoke for sender web booking flow, Flutter widget tests for scan screen |
| **Docs** | `API_SPEC.md` and `DB_SCHEMA.md` updated whenever endpoints or tables change; ADR for any non-obvious decision |
| **Security** | Validate every input via Form Request; no `dd()` left in code; secrets only in `.env`; encrypted PII; signed QR tokens |
| **Observability** | Log every external call (WhatsApp, Notify.lk, WebxPay, Supabase) with correlation IDs; track `parcel_id` across all logs |
| **Cost watch** | WhatsApp messages, SMS, Supabase storage, DigitalOcean compute — log monthly to spot leaks early |

---

## 12. Decisions deferred (explicitly)

To keep MVP lean, these are **on purpose** not in the plan above. Don't let them creep in:

- ❌ Card payments (WebxPay) — COD + bank transfer only for pilot
- ❌ Admin web portal — admin via Laravel Tinker / seeders / direct DB during pilot
- ❌ Hub console — single hub of staff doubles as drivers during pilot
- ❌ Sender mobile app — responsive web is enough
- ❌ Multiple routes — only CMB↔KDY; even CMB↔Galle waits for v1.1
- ❌ Real-time GPS lorry tracking — only scan-based status for now
- ❌ Inbound WhatsApp conversations — webhook acknowledges but does not act on customer replies
- ❌ KYC document upload — phone + name only at registration
- ❌ Insurance / declared value flow — fixed liability cap in T&Cs

---

## 13. Risk register (top 5; full list in `DEVELOPMENT_TRACKER.md`)

| # | Risk | Phase | Mitigation |
|---|---|---|---|
| 1 | WhatsApp business verification delay | 0 / 5 | Submit on Phase 0 day one; SMS fallback first-class |
| 2 | Status-transition bugs corrupt parcel state | 1 | Single source of truth in `ParcelStatus` enum + exhaustive Pest tests |
| 3 | NIC encryption / PII leak in logs | 4 | Audit logs in Phase 6; `LoggingMiddleware` mask helper |
| 4 | Driver offline scans lost | 3 | Local SQLite buffer + retry; tested in plane mode in acceptance |
| 5 | Pilot lorry breaks down → no fallback | 6 | Backup driver/lorry contact; simple manifest reprint flow |

---

## 14. Live progress tracking

A task is created for each phase in the Cowork task tracker. The mapping:

| Phase | Tracker task |
|---|---|
| 0 | Phase 0 — Foundations & Accounts |
| 1 | Phase 1 — Backend Core + Domain |
| 2 | Phase 2 — Sender Web + Public Tracking |
| 3 | Phase 3 — Driver App + Scanning |
| 4 | Phase 4 — Delivery Verification |
| 5 | Phase 5 — WhatsApp + Notifications |
| 6 | Phase 6 — Hardening & Pilot Launch |
| 7 | Phase 7+ — Post-MVP backlog |

Update the Status column in this file at the same time you transition the task. Don't let them drift.

---

## 15. Start here

1. **Apply for WhatsApp Cloud API access** at business.facebook.com. This is the long-pole.
2. **Create the GitHub repo** (`ccc/` mono-repo) and push the existing docs from `D:\CCC` to `main`.
3. **Stand up the local stack:** PHP 8.3, Composer, Node 20, PostgreSQL 16 + PostGIS, Redis. Use Docker Compose if Windows-native is painful.
4. **Confirm `php artisan --version`** in a fresh Laravel project.
5. **Open Phase 0 in this file**, set its status to `In progress`, mark the matching task `in_progress`, and work through its acceptance criteria.

When ready to start Phase 1, ask for the **Phase 1 kickoff prompt** and I'll generate a focused Claude Code prompt that scaffolds the backend exactly to spec.
