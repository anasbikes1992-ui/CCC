# Colombo Cargo Connect (CCC) — Project Context

**TL;DR:** Building a scheduled hub-to-hub freight platform for Sri Lanka with fixed routes, per-package pricing, QR/barcode scanning, WhatsApp updates, and delivery proof (NIC + signature + photo).

---

## What We're Building

### The Problem
Sri Lanka lacks a structured, affordable inter-city parcel delivery service that's transparent, traceable, and works for SMEs, retailers, and agri-exporters.

### The Solution: CCC
- **Fixed routes** between hub cities (Colombo ↔ Kandy, Colombo ↔ Galle, etc.)
- **Pre-scheduled trips** at fixed times (6 AM, 2 PM departures)
- **Consolidation model**: Many senders & receivers on one lorry per trip
- **Per-package pricing**: No per-km math—flat rate by route + package size
- **Full transparency**: QR/barcode scan at every stage of 10-stage lifecycle
- **Delivery proof**: Receiver NIC + digital signature + optional photo
- **Real-time updates**: Automated WhatsApp, SMS, email, push notifications
- **Public tracking**: Anyone with link can track parcel in real-time

### Business Model
Primary: per-parcel fee. Secondary: doorstep pickup/delivery surcharge, express surcharge, insurance, COD fees, B2B contracts, subscriptions.

**Target margin:** 30%+ per trip after platform costs.

---

## Key Concepts (CRITICAL RULES)

### 1. Routes, Trips, Lorries

| Term | Definition | Example |
|------|-----------|---------|
| **Route** | Static corridor between two hubs | `CMB-KDY` (Colombo ↔ Kandy) |
| **Trip** | Specific scheduled run of a route on a date/time | `Trip #4451 — CMB→KDY — Mon Nov 3, 6 AM — Lorry LX-1234` |
| **Lorry** | Physical vehicle with capacity limits | `LX-1234 — Medium type — 2000 kg, 300 capacity-units` |
| **Pickup/Drop Point** | Hub or intermediate location along route | `Colombo Hub`, `Kadawatha Branch`, `Doorstep` |

### 2. Package Sizes & Capacity Units

| Size | Max Weight | Max Dims | Capacity Units |
|------|-----------|---------|---------|
| **S** (Small) | 5 kg | 30×25×15 cm | 1 |
| **M** (Medium) | 25 kg | 60×45×40 cm | 4 |
| **L** (Large) | 75 kg | 120×80×80 cm | 10 |
| **XL** (Extra Large) | 200 kg | 200×120×120 cm | 30 |
| **Bale** (Pallet) | 200+ kg | 120×100×150 cm | 50 |

**Capacity metering:** A trip has 300 capacity-units max. When bookings total ≥ 300 units, trip is sold out. This lets us mix sizes on one trip.

### 3. Per-Package Pricing (NO per-km)

Pricing is a **lookup table**: route × size → base price. Operations can update via admin UI without code.

**Example:**

| Route | S | M | L | XL | Bale |
|-------|---|---|---|----|------|
| CMB↔KDY | 350 | 700 | 1500 | 3000 | 5000 |
| CMB↔Galle | 300 | 600 | 1200 | 2500 | 4500 |

**Final price = base + surcharges − discounts**
- Doorstep pickup surcharge: 200 (S), 350 (M), 600 (L), etc.
- Doorstep drop surcharge: same as pickup
- Express surcharge: 300 (S), 500 (M), 800 (L), etc.
- Insurance: 1.5% of declared value
- COD fee: min 100 or 3% of amount
- Promo/loyalty discounts applied last

### 4. Parcel Lifecycle (10 Stages)

```
BOOKED
  ↓
LABEL_PRINTED
  ↓
PICKED_UP (scan at sender's location or drop-off at hub)
  ↓
RECEIVED_AT_ORIGIN_HUB (scan IN at Colombo Hub)
  ↓
LOADED_ON_LORRY (scan when loading)
  ↓
IN_TRANSIT (lorry departs)
  ↓
ARRIVED_AT_DESTINATION_HUB (scan IN at Kandy Hub)
  ↓
OUT_FOR_DELIVERY (scan OUT to delivery driver)
  ↓
DELIVERED (NIC + signature + photo)
  └─ or DELIVERY_FAILED (can retry)
  └─ or CANCELLED (at any stage)
```

**Key rule:** Status transitions are **validated strictly**. Can't jump from BOOKED to DELIVERED. ScanService enforces this.

### 5. QR/Barcode

Every parcel gets a unique parcel number at booking:
```
CCC-YYYYMMDD-NNNNNN-X
```
Where:
- `CCC` = brand
- `YYYYMMDD` = booking date
- `NNNNNN` = 6-digit sequence per day
- `X` = check digit (Luhn or mod-37)

Example: `CCC-20251101-004572-7`

The QR encodes a **signed JWT token**, not the raw ID. This prevents scanning fakes.

### 6. WhatsApp Cloud API (NOT wa.me)

**Important:** `wa.me/94771234567` is NOT a sending API. It only opens a chat.

For **automated outbound messages**, use **WhatsApp Cloud API** (Meta):
- 1,000 service conversations/month free
- Then ~LKR 2–50 per message depending on category
- Requires business verification + approved templates

We send pre-approved templates like:
- `booking_confirmed` → to sender
- `in_transit` → to receiver
- `delivered` → to sender + receiver
- `delivery_failed` → to sender

API call:
```
POST https://graph.facebook.com/v21.0/{phone_number_id}/messages
```

Never hardcode messages; always use approved templates.

### 7. Delivery Verification

On delivery, driver's app collects:
1. **Receiver's NIC** (9 digits + V/X OR 12 digits)
2. **Digital signature** (receiver signs with finger, PNG ~300×100)
3. **Optional photo** of parcel at delivery point

Rules:
- NIC is **encrypted at rest** in DB
- NIC is **masked in logs** (e.g., `xxxxxxxxxV → ******123V`)
- Signature must be > 5 KB (not blank)
- Photo < 5 MB, JPG/PNG/HEIC
- Proof stored in `delivery_proofs` table

### 8. Payment Methods
- Credit/Debit card (WebxPay)
- Bank transfer (manual verification)
- Cash on Delivery (COD) — driver collects, driver settles separately

### 9. Roles

| Role | Surface | Main Actions |
|------|---------|-------------|
| **Sender (Customer)** | Mobile app + Web | Book, pay, track, rate, dispute |
| **Receiver** | Tracking page + WhatsApp | View status, request reschedule |
| **Driver** | Mobile app | Pick up, scan, deliver, capture proof |
| **Hub Staff** | Hub Web Console | Scan IN/OUT, manage inventory |
| **Hub Manager** | Hub Web Console | All staff + overrides |
| **Ops Admin** | Admin Web | KYC, trips, assignments, disputes |
| **Finance Admin** | Admin Web | Pricing, payouts, refunds |
| **Support Admin** | Admin Web | Tickets, customer comms |
| **Super Admin** | Admin Web | Everything + role management |

---

## Tech Stack

| Layer | Tech | Notes |
|-------|------|-------|
| **Backend API** | Laravel 11 + Sanctum | PHP 8.3, RESTful, UUID PKs |
| **Database** | PostgreSQL 16 + PostGIS | Geolocation, JSON for configs |
| **Cache/Queue** | Redis | Session, cache, job queue |
| **Realtime/Storage** | Supabase | Auth, file storage (PDFs, photos, sigs) |
| **Web Frontend** | Next.js 15 + App Router | TypeScript, Tailwind, shadcn/ui |
| **Web Admin** | Next.js 15 (same) | Same tech as frontend |
| **Hub Console** | Next.js 15 (same) | Same tech as frontend |
| **Tracking Page** | Next.js 15 + ISR | Public, no login, cached 30 sec |
| **Mobile Sender** | Flutter 3.x | Provider for state |
| **Mobile Driver** | Flutter 3.x | Provider for state, camera + scanner |
| **Payments** | WebxPay | Sri Lankan payment gateway |
| **SMS/OTP** | Notify.lk | Sri Lankan SMS provider |
| **WhatsApp** | Meta Cloud API | Automated outbound + inbound webhook |
| **Push Notifications** | Firebase Cloud Messaging | Mobile push alerts |
| **Hosting (API)** | DigitalOcean App Platform | Containerized Laravel |
| **Hosting (Web)** | Vercel | Static next.js deployments |
| **Monitoring** | Sentry + Better Stack | Error tracking + uptime |

---

## Database Conventions

- **Primary Keys:** UUID v4 everywhere, never auto-increment int
- **Naming:** snake_case in DB, camelCase in JSON/TS
- **Timestamps:** ALWAYS UTC. Use `created_at`, `updated_at`, `deleted_at` (soft deletes)
- **Encryption:** NIC values encrypted with Laravel's `Crypt::encryptString()`
- **Geolocation:** Use PostGIS geometry points for lat/lng
- **JSON:** Config tables use JSONB for settings (pricing surcharges, templates, etc.)

---

## Key Services (Business Logic)

These classes live in `app/Services/` and encapsulate business logic:

| Service | Responsibility |
|---------|-----------------|
| **PricingService** | Calculate final price for parcel (base + surcharges − discounts) |
| **ParcelNumberService** | Generate unique parcel number + check digit |
| **QrTokenService** | Create signed JWT for QR, verify on scan |
| **TripAssignmentService** | Auto-assign parcel to next available trip with capacity |
| **ScanService** | Validate status transition, log event, send notification |
| **WhatsAppService** | Send Cloud API messages (queueable, templated) |
| **PaymentService** | Create payment intent, verify callback |

---

## API Conventions

- **Endpoint format:** `/api/v1/{resource}`
- **JSON response:**
  ```json
  {
    "success": true,
    "data": { ... },
    "error": null,
    "meta": { "page": 1, "total": 100 }
  }
  ```
- **Error format:**
  ```json
  {
    "success": false,
    "data": null,
    "error": {
      "code": "VALIDATION_ERROR",
      "message": "Phone is invalid",
      "details": { "phone": ["E.164 format required"] }
    }
  }
  ```
- **Pagination:** `?limit=50&offset=0` (limit capped at 100)
- **Sorting:** `?sort=-created_at` (prefix with `-` for DESC)
- **Caching:** GET endpoints cached 30 sec on read-only (ETag + conditional)

---

## Folder Layout

```
d:\CCC\
├── backend/                    ← Laravel 11
│   ├── app/Services/
│   ├── app/Models/
│   ├── routes/api.php
│   ├── database/migrations/
│   └── tests/
├── web-sender/                 ← Next.js sender portal
├── web-admin/                  ← Next.js ops admin
├── web-hub/                    ← Next.js hub staff console
├── web-tracking/               ← Next.js public tracking page
├── mobile-sender/              ← Flutter sender app
├── mobile-driver/              ← Flutter driver app
└── docs/
```

---

## Development Rules

### DO
- ✅ Use UUID primary keys everywhere
- ✅ Encrypt sensitive data (NIC) at rest
- ✅ Validate all status transitions strictly
- ✅ Use Laravel's Service classes for business logic
- ✅ Queue async jobs (notifications, payments)
- ✅ Write Form Request validation classes
- ✅ Use TypeScript strict mode in Next.js
- ✅ Cache read-only endpoints
- ✅ Test pricing calculations thoroughly

### DON'T
- ❌ Use auto-increment integer PKs
- ❌ Store NIC in plaintext
- ❌ Allow arbitrary status transitions
- ❌ Put business logic in controllers
- ❌ Send notifications synchronously
- ❌ Use any JavaScript without types in Next.js
- ❌ Add dependencies without asking
- ❌ Skip input validation

---

## Common Gotchas

1. **Pricing is NOT per-km.** It's per-package by route + size. Always use the pricing matrix lookup.
2. **Routes are static.** Trips are instances. Don't confuse them.
3. **Capacity-units != kg.** A 30-kg parcel might be 10 units (L size).
4. **WhatsApp isn't wa.me.** Use Cloud API for automated messages. wa.me is only for inbound customer links.
5. **Scans are strict.** Validate every transition. Bad scan data breaks tracking.
6. **NIC is PII.** Encrypt, mask in logs, and comply with Data Protection Act 2022.
7. **Test edge cases:** Duplicate scans, wrong hub, oversized parcels, failed payments, delivery attempts.

---

## First Sprint Checklist

- [ ] Read COLOMBO_CARGO_CONNECT_PLAN.md completely
- [ ] Read this file (CLAUDE.md)
- [ ] Set up local dev environment (PostgreSQL, Redis, PHP 8.3, Node, Flutter)
- [ ] Run Sprint 1 prompt to scaffold backend
- [ ] Confirm `/api/health` endpoint works
- [ ] Commit to Git
- [ ] Move to Sprint 2

---

## Quick Links

- **Full Spec:** `COLOMBO_CARGO_CONNECT_PLAN.md`
- **Development Tracker:** `DEVELOPMENT_TRACKER.md`
- **Setup Checklist:** `SETUP_CHECKLIST.md`
- **Laravel Docs:** https://laravel.com/docs/11.x
- **Next.js Docs:** https://nextjs.org/docs
- **Flutter Docs:** https://flutter.dev/docs
- **WhatsApp Cloud API:** https://developers.facebook.com/docs/whatsapp/cloud-api

---

**Last Updated:** May 1, 2026  
**Status:** Ready for Development  
**Use this file in every Claude Code session**
