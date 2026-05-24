# 🚛 Colombo Cargo Connect (CCC)

**A scheduled hub-to-hub freight platform for Sri Lanka**

Built with **Laravel 11**, **Next.js 15**, **Flutter 3**, **PostgreSQL 16**, and **Supabase**.

---

## 📋 Quick Navigation

### 🏗️ Project Documentation

| Document | Purpose |
|----------|---------|
| **[COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md)** | Complete specification (concept, pricing, routes, tech stack, sprints) |
| **[CLAUDE.md](CLAUDE.md)** | **Read this for every Claude Code session** — Project context, rules, tech stack |
| **[DEVELOPMENT_TRACKER.md](DEVELOPMENT_TRACKER.md)** | Development phases, sprints, milestones, progress tracking |
| **[SETUP_CHECKLIST.md](SETUP_CHECKLIST.md)** | Step-by-step environment setup (Windows) |
| **[ARCHITECTURE.md](ARCHITECTURE.md)** | System design, data flows, security, deployment |
| **[COMMITS.md](COMMITS.md)** | Commit history tracking, commit format, phase breakdown |

### 🎯 Start Here

**New to the project?**

1. Read [CLAUDE.md](CLAUDE.md) (5 min) — Key concepts + tech stack
2. Read [COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md) (15 min) — Full specification
3. Run [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md) (30 min) — Set up your environment
4. Read [ARCHITECTURE.md](ARCHITECTURE.md) (10 min) — System design overview
5. Ready to code! Pick a sprint from [COMMITS.md](COMMITS.md)

---

## 🌍 What's CCC?

### The Problem
Sri Lanka lacks a structured, transparent inter-city parcel delivery service that's affordable and works for SMEs, retailers, and agri-exporters.

### The Solution
**Colombo Cargo Connect** delivers parcels on **fixed scheduled routes** (Colombo ↔ Kandy, Colombo ↔ Galle, etc.) with:
- ✅ **Per-package pricing** (no per-km math)
- ✅ **Consolidation** (many senders/receivers per lorry)
- ✅ **QR/barcode scanning** at every stage (10-stage lifecycle)
- ✅ **Delivery proof** (NIC + digital signature + optional photo)
- ✅ **Real-time tracking** (public, no login)
- ✅ **Automated notifications** (WhatsApp, SMS, push, email)

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **API** | Laravel 11 (PHP 8.3) + Sanctum |
| **Database** | PostgreSQL 16 + PostGIS |
| **Cache/Queue** | Redis |
| **Realtime/Storage** | Supabase |
| **Web Frontend** | Next.js 15 + TypeScript + Tailwind + shadcn/ui |
| **Mobile Sender** | Flutter 3.x |
| **Mobile Driver** | Flutter 3.x |
| **Payments** | WebxPay |
| **Notifications** | WhatsApp Cloud API, Notify.lk, Firebase |
| **Hosting** | DigitalOcean (API), Vercel (Web) |

---

## 🌐 Live Web Endpoints

| Surface | URL |
|--------|-----|
| **Main Landing (root project)** | `https://ccc-admin-jade.vercel.app` |
| **Sender Portal** | `https://web-sender.vercel.app` |
| **Public Tracking** | `https://web-tracking-sigma.vercel.app` |
| **Hub Console** | `https://ccc-hub-seven.vercel.app` |
| **Admin Console** | `https://web-admin-rho-sepia.vercel.app` |

---

## 📁 Project Structure

```
d:\CCC\
├── COLOMBO_CARGO_CONNECT_PLAN.md    ← The spec
├── CLAUDE.md                         ← Read for every session
├── DEVELOPMENT_TRACKER.md            ← Progress tracking
├── SETUP_CHECKLIST.md               ← Environment setup
├── ARCHITECTURE.md                  ← System design
├── COMMITS.md                       ← Commit tracking
├── README.md                        ← This file
│
├── backend/                         ← Laravel 11 API
│   ├── app/Models/
│   ├── app/Services/
│   ├── app/Http/Controllers/
│   ├── routes/api.php
│   ├── database/migrations/
│   ├── database/seeders/
│   └── .env.example
│
├── web-sender/                      ← Sender web portal (Next.js)
├── web-admin/                       ← Admin console (Next.js)
├── web-hub/                         ← Hub staff console (Next.js)
├── web-tracking/                    ← Public tracking page (Next.js)
│
├── mobile-sender/                   ← Sender app (Flutter)
├── mobile-driver/                   ← Driver app (Flutter)
│
└── docs/
    ├── adr/                         ← Architecture Decision Records
    ├── guides/
    ├── api-examples/
    └── db-seeds/
```

---

## 🚀 Getting Started

### Step 1: Environment Setup

Follow [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md) to install:
- PHP 8.3, Node.js 20+, PostgreSQL 16, Redis, Flutter
- Create `ccc_dev` + `ccc_test` databases with PostGIS

### Step 2: Clone & Configure

```bash
cd d:\CCC
git clone <repo-url> .
cp backend/.env.example backend/.env
# Edit backend/.env with your database credentials
```

### Step 3: Start Development

**Terminal 1 — Backend:**
```bash
cd backend
php artisan serve
# API runs at http://localhost:8000
```

**Terminal 2 — Sender Web:**
```bash
cd web-sender
npm run dev
# Sender portal at http://localhost:3000
```

**Terminal 3 — Admin Web:**
```bash
cd web-admin
npm run dev
# Admin console at http://localhost:3001
```

**Terminal 4 — Driver App:**
```bash
cd mobile-driver
flutter run
# Driver app runs on Android emulator
```

---

## 📚 Key Concepts

### Routes & Trips

| Term | Example |
|------|---------|
| **Route** | `CMB-KDY` (Colombo ↔ Kandy, static corridor) |
| **Trip** | `Trip #4451` (specific lorry, date, time: Mon 6 AM, Lorry LX-1234) |

### Package Sizes & Capacity

| Size | Max Wt | Capacity Units |
|------|--------|---|
| Small (S) | 5 kg | 1 |
| Medium (M) | 25 kg | 4 |
| Large (L) | 75 kg | 10 |
| XL | 200 kg | 30 |
| Bale | 200+ kg | 50 |

Lorry capacity: 300 units. When bookings = 300 units, trip is full.

### Pricing (per-package, NOT per-km)

```
final_price = route_size_base
            + (doorstep_pickup ? pickup_surcharge : 0)
            + (doorstep_drop ? drop_surcharge : 0)
            + (express ? express_surcharge : 0)
            + (insurance ? declared_value × 1.5% : 0)
            - discount
```

### Parcel Lifecycle

```
BOOKED
  → LABEL_PRINTED
  → PICKED_UP
  → RECEIVED_AT_ORIGIN_HUB
  → LOADED_ON_LORRY
  → IN_TRANSIT
  → ARRIVED_AT_DESTINATION_HUB
  → OUT_FOR_DELIVERY
  → DELIVERED (with NIC + signature + optional photo)
     OR DELIVERY_FAILED (can retry)
  OR CANCELLED (any stage)
```

### Parcel Number Format

```
CCC-YYYYMMDD-NNNNNN-X
```

Example: `CCC-20251101-004572-7` (Nov 1, 2025, sequence #4572, check digit 7)

---

## 🔑 Development Rules

### DO ✅

- Use UUID primary keys everywhere
- Encrypt sensitive data (NIC) at rest
- Validate all status transitions strictly
- Use Laravel Service classes for business logic
- Queue async jobs (notifications, payments)
- Test everything (target 80%+ coverage)
- Write clear commit messages (Conventional Commits)

### DON'T ❌

- Use auto-increment integer PKs
- Store NIC in plaintext
- Allow arbitrary status transitions
- Put business logic in controllers
- Send notifications synchronously
- Skip input validation

---

## 📝 Common Tasks

### Run Tests

```bash
# Backend
cd backend
php artisan test

# Frontend
cd web-sender
npm test

# Flutter
cd mobile-sender
flutter test
```

### Create Migration

```bash
cd backend
php artisan make:migration create_users_table
php artisan migrate
```

### Add Next.js Page

```bash
cd web-sender
# Create app/page.tsx or app/dashboard/page.tsx
npm run dev
```

### Add Flutter Widget

```bash
cd mobile-sender
# Create lib/screens/my_screen.dart
flutter run
```

---

## 🎯 Sprints

See [COMMITS.md](COMMITS.md) for detailed sprint breakdown.

| Phase | Sprints | Status |
|-------|---------|--------|
| **Foundation** | 1–3 | Not started |
| **Booking Flow** | 4–6 | Not started |
| **Scanning & Lifecycle** | 7–9 | Not started |
| **Delivery & Proof** | 10–11 | Not started |
| **Notifications** | 12–14 | Not started |
| **Admin & Ops** | 15–17 | Not started |
| **Polish & Launch** | 18–20 | Not started |

---

## 🔗 Important Links

- **Laravel Docs:** https://laravel.com/docs/11.x
- **Next.js Docs:** https://nextjs.org/docs
- **Flutter Docs:** https://flutter.dev/docs
- **PostgreSQL Docs:** https://www.postgresql.org/docs/16/
- **Supabase Docs:** https://supabase.com/docs
- **WhatsApp Cloud API:** https://developers.facebook.com/docs/whatsapp/cloud-api

---

## 🤝 Contributing

1. Create a feature branch: `git checkout -b feat/feature-name`
2. Make changes + test locally
3. Commit with message: `feat(scope): description`
4. Push + create PR: `git push origin feat/feature-name`
5. Request review (check diff carefully)
6. Merge when approved

See [COMMITS.md](COMMITS.md) for commit format details.

---

## 📞 Support

- **Questions?** Check [CLAUDE.md](CLAUDE.md) or the relevant spec
- **Stuck on setup?** See [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md) troubleshooting
- **Architecture question?** Read [ARCHITECTURE.md](ARCHITECTURE.md)
- **Full spec?** See [COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md)

---

## 📜 License

[Add your license here]

---

## 🗓️ Timeline

- **Start Date:** May 1, 2026
- **Sprint Duration:** 1 week each
- **Estimated Launch:** Mid-September 2026 (~20 weeks)

---

## ✨ Key Success Criteria

- [x] Project documentation complete + organized
- [ ] Sprint 1 backend scaffolding done
- [ ] Database schema finalized + tested
- [ ] Booking flow operational + tested
- [ ] WhatsApp integration live + messaging working
- [ ] Public tracking page live
- [ ] Admin console operational
- [ ] All 3 apps (web sender, web admin, driver app) functional
- [ ] UAT ready
- [ ] Production deployed

---

**Last Updated:** May 1, 2026  
**Project Status:** Foundation Phase - Ready for Development  
**Next Step:** Read CLAUDE.md + SETUP_CHECKLIST.md → Begin Sprint 1
