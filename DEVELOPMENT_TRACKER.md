# 🚛 Colombo Cargo Connect (CCC) — Development Tracker

**Project Start Date:** May 1, 2026  
**Status:** Phase 6 Complete — Ready for Deployment

---

## 📋 Project Overview

**Colombo Cargo Connect (CCC)** is a scheduled hub-to-hub freight platform for Sri Lanka with fixed routes, per-package pricing, QR/barcode scanning at every stage, WhatsApp status updates via Cloud API, and on-delivery verification with NIC + digital signature + photo.

### Key Differentiators
- **Fixed Routes**: Pre-defined corridors (Colombo↔Kandy, Colombo↔Galle, etc.)
- **Per-Package Pricing**: No distance math—flat rates by route × package size
- **Consolidation Model**: Multi-pickup, multi-drop on same trip
- **Full Tracking**: QR/barcode scanning at 10 lifecycle stages
- **Delivery Proof**: NIC verification + digital signature + optional photo
- **Automated Notifications**: WhatsApp Cloud API (not wa.me), SMS, push, email

---

## 🛠️ Tech Stack Summary

| Component | Technology |
|-----------|------------|
| **Backend** | Laravel 11 (PHP 8.3) + Sanctum |
| **Database** | PostgreSQL 16 + PostGIS |
| **Cache/Queue** | Redis |
| **Realtime/Storage** | Supabase |
| **Web (Sender)** | Next.js 15 + TypeScript + Tailwind + shadcn/ui |
| **Web (Admin)** | Next.js 15 (same stack) |
| **Web (Hub Console)** | Next.js 15 (same stack) |
| **Web (Public Tracking)** | Next.js 15 (ISR/static) |
| **Mobile (Sender)** | Flutter 3.x |
| **Mobile (Driver)** | Flutter 3.x |
| **Payments** | WebxPay (cards), Bank Transfer, COD |
| **WhatsApp** | Meta Cloud API (not wa.me) |
| **SMS/OTP** | Notify.lk |
| **Push** | Firebase Cloud Messaging |
| **Hosting** | DigitalOcean (API), Vercel (Web) |

---

## 📅 Sprint Plan

### Phase 1: Foundation (Sprints 1–3)
- Sprint 1: Backend scaffolding + health endpoint
- Sprint 2: Routes, Hubs, Lorries, Drivers domain
- Sprint 3: Package sizes, Pricing matrix, Payment integration

### Phase 2: Core Booking Flow (Sprints 4–6)
- Sprint 4: Parcel model, Booking service, QR/barcode generation
- Sprint 5: Trip assignment (auto + manual), Capacity metering
- Sprint 6: Sender Web portal (booking UI)

### Phase 3: Scanning & Lifecycle (Sprints 7–9)
- Sprint 7: Driver app base + QR scanner
- Sprint 8: Scan events, status transitions, validation
- Sprint 9: Parcel events table + timeline API

### Phase 4: Delivery & Verification (Sprints 10–11)
- Sprint 10: Delivery proof (NIC + signature + photo)
- Sprint 11: Driver delivery flow + photo upload

### Phase 5: Notifications & Tracking (Sprints 12–14)
- Sprint 12: WhatsApp Cloud API integration
- Sprint 13: Public tracking page
- Sprint 14: Notification templates + queue

### Phase 6: Admin & Operations (Sprints 15–17)
- Sprint 15: Admin trip management
- Sprint 16: Pricing matrix admin
- Sprint 17: Dispute + Support ticketing

### Phase 7: Polish & Launch (Sprints 18–20)
- Sprint 18: Testing, edge cases, error handling
- Sprint 19: Performance tuning + caching
- Sprint 20: Launch checklist, docs, deployment

---

## 🗂️ Project Folder Structure

```
d:\CCC\
├── COLOMBO_CARGO_CONNECT_PLAN.md      ← Original detailed plan
├── DEVELOPMENT_TRACKER.md             ← This file
├── CLAUDE.md                           ← Project context for Claude Code
├── SETUP_CHECKLIST.md                  ← Environment setup checklist
├── ARCHITECTURE.md                     ← System architecture deep-dive
├── API_SPEC.md                         ← API endpoint documentation
├── DB_SCHEMA.md                        ← Database schema with SQL
├── PRICING_RULES.md                    ← Pricing logic & calculations
│
├── backend/                            ← Laravel 11 API
│   ├── app/
│   ├── database/
│   ├── routes/
│   ├── .env.example
│   └── README.md
│
├── web-sender/                         ← Next.js Sender Portal
│   ├── app/
│   ├── components/
│   ├── .env.example
│   └── README.md
│
├── web-admin/                          ← Next.js Admin Console
│   ├── app/
│   ├── components/
│   ├── .env.example
│   └── README.md
│
├── web-hub/                            ← Next.js Hub Staff Console
│   ├── app/
│   ├── components/
│   ├── .env.example
│   └── README.md
│
├── web-tracking/                       ← Next.js Public Tracking
│   ├── app/
│   ├── components/
│   ├── .env.example
│   └── README.md
│
├── mobile-sender/                      ← Flutter Sender App
│   ├── lib/
│   ├── pubspec.yaml
│   └── README.md
│
├── mobile-driver/                      ← Flutter Driver App
│   ├── lib/
│   ├── pubspec.yaml
│   └── README.md
│
└── docs/
    ├── adr/                            ← Architecture Decision Records
    ├── guides/
    ├── api-examples/
    └── db-seeds/
```

---

## ✅ Pre-Development Checklist

- [ ] **Environment Setup**
  - [ ] Windows 10/11 with PowerShell 5+
  - [ ] Git installed and configured
  - [ ] Node.js 20+ LTS
  - [ ] PHP 8.3+
  - [ ] Composer installed
  - [ ] PostgreSQL 16 running locally
  - [ ] Redis running locally
  - [ ] Flutter SDK installed
  - [ ] Android Studio / iOS toolchain

- [ ] **Accounts & Credentials** (to be set up during relevant sprints)
  - [ ] Supabase project created
  - [ ] WhatsApp Cloud API registered & phone number verified
  - [ ] WebxPay merchant account
  - [ ] Notify.lk account
  - [ ] Firebase project
  - [ ] Google Cloud (Maps & OSRM)
  - [ ] DigitalOcean account
  - [ ] Vercel account
  - [ ] GitHub repo created

- [ ] **Documentation**
  - [x] COLOMBO_CARGO_CONNECT_PLAN.md — comprehensive spec
  - [ ] CLAUDE.md — project context (to be created)
  - [ ] SETUP_CHECKLIST.md — environment step-by-step (to be created)
  - [ ] ARCHITECTURE.md — system design details (to be created)
  - [ ] API_SPEC.md — endpoint reference (to be created as we build)
  - [ ] DB_SCHEMA.md — SQL schema (to be created as we build)

---

## 📝 Development Rules & Conventions

### Database
- UUID primary keys everywhere
- snake_case naming
- Soft deletes on most tables
- UTC timestamps
- Indexes on frequently queried columns
- PostGIS enabled for geolocation

### Backend (Laravel)
- PHP 8.3 strict types
- Eloquent ORM for all models
- Form Request validation classes
- Service classes for business logic
- Event/Observer pattern for triggers
- Sanctum for API auth
- Laravel Pint for code style

### Web (Next.js)
- TypeScript strict mode
- App Router (not Pages Router)
- React Server Components where possible
- shadcn/ui component library
- Tailwind CSS
- ESLint + Prettier

### Mobile (Flutter)
- Dart 3.x
- Provider / Riverpod for state
- Clean architecture layers
- Material Design 3

### API Conventions
- RESTful endpoints
- camelCase in JSON responses
- Consistent error format
- Pagination with limit/offset
- 30-second cache TTL on read-only endpoints

### Security
- All NIC numbers encrypted at rest
- Mask sensitive data in logs
- HTTPS enforced
- CORS properly configured
- Rate limiting on public endpoints
- Input validation everywhere

---

## 🚀 Next Steps (Immediate)

1. **Create CLAUDE.md** — Project context file for Claude Code sessions
2. **Create SETUP_CHECKLIST.md** — Step-by-step local environment setup
3. **Create ARCHITECTURE.md** — System architecture diagrams and flow
4. **Create DB_SCHEMA.md** — Full SQL schema with comments
5. **Initialize Git repo** — Set up GitHub and link to local
6. **Sprint 1 Kickoff** — Run Sprint 1 prompt in Claude Code

---

## 📊 Progress Tracking

### By Phase
- **Phase 1 (Foundation)**: 100% — Done
- **Phase 2 (Booking)**: 100% — Done
- **Phase 3 (Scanning)**: 100% — Done
- **Phase 4 (Delivery)**: 100% — Done
- **Phase 5 (Notifications)**: 100% — Done
- **Phase 6 (Admin)**: 100% — Done
- **Phase 7 (Polish)**: 0% — Not started

### Key Milestones
- [x] Development environment fully set up
- [x] Backend scaffolded & tests passing
- [x] Database schema finalized
- [x] Booking workflow operational
- [x] Sender web app scaffolded and connected to booking/list APIs
- [x] Public tracking app scaffolded with parcel lookup route
- [x] Driver app scanning functional
- [x] Delivery proof (NIC + Signature + Photo) operational
- [x] WhatsApp integration live
- [x] Public tracking page live
- [x] Admin console operational (trips, parcels, pricing, users, drivers, hubs, routes, lorries)
- [x] Hub staff console built (web-hub — scan, inbound, outbound, inventory, manifest)
- [x] Dispute & Support ticketing (backend + admin UI + customer API)
- [ ] Deployed to Supabase (DB) + Railway (API) + Vercel (web apps)
- [ ] UAT ready
- [ ] Production deployed

---

## 📚 Important Documents to Read/Reference

- **COLOMBO_CARGO_CONNECT_PLAN.md** — The complete specification
- **CLAUDE.md** (to be created) — For every Claude Code session
- **Tech Stack Details**:
  - Laravel: https://laravel.com/docs/11.x
  - Next.js: https://nextjs.org/docs
  - Flutter: https://flutter.dev/docs
  - Supabase: https://supabase.com/docs
  - WhatsApp Cloud API: https://developers.facebook.com/docs/whatsapp/cloud-api

---

## ⚠️ Risk Register

| Risk | Impact | Mitigation |
|------|--------|-----------|
| WhatsApp approval delays | High | Apply early, use templates, have SMS fallback ready |
| PostgreSQL + PostGIS complexity | Medium | Use managed Supabase to reduce ops burden |
| Multi-platform (web + 2 mobile apps) | High | Prioritize API first, share logic via services |
| Real-time geolocation tracking | Medium | Use polling + WebSocket, implement efficient indexing |
| High data volume (parcels, events, scans) | Medium | Implement aggressive caching, archive old events |
| Payment gateway integration delays | Medium | Build COD & bank transfer flow first, add cards later |

---

## 💬 Questions for First Session

1. Which platform should launch first: web sender or driver app?
2. Do we start with Colombo↔Kandy only, or all routes from day 1?
3. Is KYC verification required before booking, or only on first trip?
4. Do we need real-time ETAs via OSRM/Google, or pre-calculated estimates?
5. Should the first version support only WhatsApp, or multi-channel (SMS + email)?

---

**Last Updated:** May 20, 2026  
**Author:** Project Initialization  
**Status:** Phase 6 Complete — next step: deploy (Supabase + Railway + Vercel)
