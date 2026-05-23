# 📚 CCC Documentation Index

**Project:** Colombo Cargo Connect  
**Status:** Core Complete — Advanced Development Phase  
**Last Updated:** May 23, 2026  

---

## 🎯 Read These Files In This Order

### 1️⃣ **START HERE** — Quick Overview (5 min)
📄 [README.md](README.md)
- What is CCC?
- Quick navigation to all docs
- Tech stack overview
- Getting started guide

### 2️⃣ **BEFORE EVERY CODING SESSION** (10 min)
📄 [CLAUDE.md](CLAUDE.md) or [AGENTS.md](AGENTS.md)
- Key concepts (routes, trips, package sizes, pricing)
- Tech stack details
- Database conventions
- Development rules (DO/DON'T)
- Common gotchas

### 3️⃣ **THE COMPLETE SPECIFICATION** (20 min)
📄 [COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md)
- Business model & revenue streams
- Package sizes & capacity units
- Pricing matrix with examples
- Routes, trips, lorries architecture
- Parcel lifecycle (10 stages)
- WhatsApp Cloud API setup
- Delivery verification (NIC + signature + photo)
- User roles
- Database schema
- Sprint-by-sprint execute prompts

### 4️⃣ **SET UP YOUR ENVIRONMENT** (1 hour)
📄 [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md)
- Phase 1: Chocolatey + core tools
- Phase 2: Flutter & mobile dev
- Phase 3: PostgreSQL & database
- Phase 4: Redis setup
- Phase 5: Git repository
- Phase 6: Laravel backend
- Phase 7: Next.js web projects
- Phase 8: Flutter mobile projects
- Phase 9: Final verification
- Phase 10: Ready to start

### 5️⃣ **UNDERSTAND THE ARCHITECTURE** (15 min)
📄 [ARCHITECTURE.md](ARCHITECTURE.md)
- System overview diagram
- Layer responsibilities (Client, Edge, Backend, Data)
- Data flow: Booking to Delivery (with diagrams)
- Authentication & Authorization
- API conventions
- Scaling considerations
- Security architecture
- Monitoring & observability
- Deployment architecture

### 6️⃣ **TRACK YOUR PROGRESS** (5 min)
📄 [DEVELOPMENT_TRACKER.md](DEVELOPMENT_TRACKER.md)
- Project overview
- Tech stack summary
- Sprint plan (20 sprints, 7 phases)
- Folder structure
- Pre-development checklist
- Risk register

---

## 🚀 Advanced Development Docs (NEW!)

### 📘 Master Roadmap
📄 [advancedev.md](advancedev.md) — **Phase-Based Advanced Development**
- Phase A: Production Infrastructure Setup (Database, API, Web, Mobile, Storage)
- Phase B: Testing & Quality Assurance (Coverage 70%+, E2E, Label PDFs, Performance)
- Phase C: Missing Feature Implementation (WhatsApp, WebxPay, SMS, FCM, QR signing)
- Phase D: Security Hardening (Audit, Data Privacy Act 2022, RBAC)
- Phase E: Performance Optimization (Indexes, Redis caching, queue optimization, API response)
- Phase F: Monitoring & Operations (Sentry, Better Stack, APM, business metrics)
- Phase G: Documentation & Polish (API docs, runbook, training, UX improvements, WCAG AA)
- Phase H: Go-Live Preparation (Checklist, soft launch, full launch)

### 📗 Quick Start Guide
📄 [QUICK_START.md](QUICK_START.md) — **Fast-Track Guide**
- How to start each phase (A-H)
- Quick start commands
- Prerequisites checklist
- Progress tracking table
- Common commands (deploy, rollback, test)
- Troubleshooting tips

### 📙 Operations Manual
📄 [docs/RUNBOOK.md](docs/RUNBOOK.md) — **Complete Ops Guide**
- Architecture diagrams
- Deployment procedures (Railway, Vercel, Mobile)
- Common tasks (create route, schedule trip, update pricing, handle refund)
- Troubleshooting (API 500s, login failures, scan issues, WhatsApp failures, payment webhooks)
- Emergency procedures (outages, data breaches, payment failures)
- Environment variables reference
- Quick reference commands

### 📕 External Services Setup
📄 [docs/EXTERNAL_SERVICES_SETUP.md](docs/EXTERNAL_SERVICES_SETUP.md) — **Service Onboarding**
- 10 external services with step-by-step setup:
  1. Supabase production (database + storage)
  2. Railway production (backend hosting)
  3. Vercel production (4 web apps)
  4. WhatsApp Cloud API (automated notifications)
  5. Notify.lk SMS (Sri Lankan SMS provider)
  6. WebxPay (payment gateway)
  7. Firebase FCM (push notifications)
  8. Sentry (error tracking)
  9. Better Stack (uptime monitoring)
  10. Domain & DNS setup
- Prerequisites checklist
- Cost estimates (~$180-200/month fixed costs)
- Completion checklist

### 📗 Go-Live Checklist
📄 [docs/GO_LIVE_CHECKLIST.md](docs/GO_LIVE_CHECKLIST.md) — **Production Readiness**
- Business readiness (legal, financial, operations, marketing)
- Infrastructure checklist (database, backend, web apps, mobile)
- Testing checklist (unit, E2E, performance, security, cross-browser)
- Monitoring & operations setup
- Documentation completeness
- Launch day procedures
- Rollback plan
- Sign-off approvals

---

## 📋 Documentation Map

```
d:\CCC\
├── 📘 Core Documentation
│   ├── README.md                          ← Quick start + navigation
│   ├── CLAUDE.md / AGENTS.md              ← Read before every session
│   ├── COLOMBO_CARGO_CONNECT_PLAN.md      ← Full specification
│   ├── SETUP_CHECKLIST.md                 ← Environment setup
│   ├── ARCHITECTURE.md                    ← System design
│   ├── DEVELOPMENT_TRACKER.md             ← Progress tracking
│   ├── COMMITS.md                         ← Commit history & format
│   ├── DOCUMENTATION_INDEX.md             ← This file
│   ├── ROADMAP.md                         ← Project roadmap
│   └── PHASE_0_CHECKLIST.md               ← Phase 0 validation
│
├── 📗 Advanced Development (NEW!)
│   ├── advancedev.md                      ← Master phase-based roadmap
│   ├── QUICK_START.md                     ← Fast-track guide per phase
│   └── docs/
│       ├── RUNBOOK.md                     ← Operations manual
│       ├── EXTERNAL_SERVICES_SETUP.md     ← Service onboarding guide
│       ├── GO_LIVE_CHECKLIST.md           ← Production launch checklist
│       ├── ACCESSIBILITY_AUDIT.md         ← WCAG 2.2 AA Audit (NEW!)
│       ├── ACCESSIBILITY_IMPLEMENTATION.md ← Accessibility fixes summary (NEW!)
│       ├── API_SPEC.md                    ← API documentation
│       ├── DB_SCHEMA.md                   ← Database schema
│       ├── PRICING_RULES.md               ← Pricing calculation rules
│       └── whatsapp_templates_meta.md     ← WhatsApp templates
│
└── 📂 Project Files
    ├── backend/                           ← Laravel 11 API
    ├── web-sender/                        ← Customer portal
    ├── web-admin/                         ← Operations console
    ├── web-hub/                           ← Hub staff console
    ├── web-tracking/                      ← Public tracking page
    └── mobile-driver/                     ← Driver app (Flutter)
```

---

## 🎓 Learning Path (By Role)

### 👨‍💻 Backend Developer

**Core Development:**
1. ✅ README.md (quick context)
2. ✅ CLAUDE.md (key concepts + DB rules)
3. ✅ COLOMBO_CARGO_CONNECT_PLAN.md (sections: 1–6, 12, 14, 17)
4. ✅ SETUP_CHECKLIST.md (phases 1, 3, 4, 5, 6)
5. ✅ ARCHITECTURE.md (Backend API layer, Data layer)
6. ✅ COMMITS.md (commit format + backend commits)

**Advanced Development:**
7. 📘 advancedev.md (Phase A, C, E)
8. 📗 QUICK_START.md (Phase A, C, E quick starts)
9. 📙 docs/RUNBOOK.md (Deployment, Troubleshooting)
10. 📕 docs/EXTERNAL_SERVICES_SETUP.md (WhatsApp, SMS, Payment)

**Then:** Start Sprint 1 (scaffold backend) OR Phase A (production infrastructure)

### 🎨 Frontend Developer (Web)

**Core Development:**
1. ✅ README.md (quick context)
2. ✅ CLAUDE.md (key concepts + API conventions)
3. ✅ COLOMBO_CARGO_CONNECT_PLAN.md (sections: 7, 10–11, 17)
4. ✅ SETUP_CHECKLIST.md (phases 1, 5, 7)
5. ✅ ARCHITECTURE.md (Client layer, API conventions)
6. ✅ COMMITS.md (commit format + web commits)

**Advanced Development:**
7. 📘 advancedev.md (Phase A, G)
8. 📗 QUICK_START.md (Phase A, G quick starts)
9. 📙 docs/RUNBOOK.md (Deployment procedures)
10. 📗 docs/ACCESSIBILITY_AUDIT.md (WCAG 2.2 AA compliance)
11. 📙 docs/ACCESSIBILITY_IMPLEMENTATION.md (Accessibility fixes)

**Then:** Start Sprint 6 (sender web portal) OR Phase A (Vercel deployment) OR Phase G.4 (Accessibility)

### 📱 Mobile Developer (Flutter)

**Core Development:**
1. ✅ README.md (quick context)
2. ✅ CLAUDE.md (key concepts + API conventions)
3. ✅ COLOMBO_CARGO_CONNECT_PLAN.md (sections: 7, 9–11, 17)
4. ✅ SETUP_CHECKLIST.md (phases 1, 2, 5, 8)
5. ✅ ARCHITECTURE.md (Client layer, API conventions)
6. ✅ COMMITS.md (commit format + mobile commits)

**Advanced Development:**
7. 📘 advancedev.md (Phase A.4, C.4)
8. 📗 QUICK_START.md (Phase A.4, C.4 quick starts)
9. 📙 docs/RUNBOOK.md (Mobile deployment)
10. 📕 docs/EXTERNAL_SERVICES_SETUP.md (Firebase FCM)

**Then:** Start Sprint 7 (driver app base) OR Phase A.4 (Firebase App Distribution)

### 👔 DevOps / Infrastructure

**Core Development:**
1. ✅ README.md (quick context)
2. ✅ ARCHITECTURE.md (Deployment architecture, Monitoring)
3. ✅ SETUP_CHECKLIST.md (all phases for local dev)
4. ✅ DEVELOPMENT_TRACKER.md (tech stack)

**Advanced Development:**
5. 📘 advancedev.md (Phase A, F)
6. 📗 QUICK_START.md (Phase A, F quick starts)
7. 📙 docs/RUNBOOK.md (Complete operations manual)
8. 📕 docs/EXTERNAL_SERVICES_SETUP.md (All 10 services)
9. 📗 docs/GO_LIVE_CHECKLIST.md (Infrastructure & monitoring sections)

**Then:** Phase A (Set up production infrastructure)

### 🛡️ Security Engineer

**Core Development:**
1. ✅ README.md (quick context)
2. ✅ CLAUDE.md (development rules, NIC encryption)
3. ✅ ARCHITECTURE.md (Security architecture)

**Advanced Development:**
4. 📘 advancedev.md (Phase D)
5. 📗 QUICK_START.md (Phase D quick start)
6. 📙 docs/RUNBOOK.md (Emergency procedures)

**Then:** Phase D (Security hardening)

### 🧪 QA Engineer

**Core Development:**
1. ✅ README.md (quick context)
2. ✅ COLOMBO_CARGO_CONNECT_PLAN.md (user flows)

**Advanced Development:**
3. 📘 advancedev.md (Phase B)
4. 📗 QUICK_START.md (Phase B quick start)
5. 📙 docs/RUNBOOK.md (Common tasks, Troubleshooting)
6. 📗 docs/GO_LIVE_CHECKLIST.md (Testing checklist)

**Then:** Track progress against COMMITS.md timeline

### 📋 Project Manager / Product Owner

**Core Development:**
1. ✅ README.md (overview)
2. ✅ COLOMBO_CARGO_CONNECT_PLAN.md (full spec)
3. ✅ DEVELOPMENT_TRACKER.md (sprints, timeline, risks)
4. ✅ COMMITS.md (phase breakdown, statistics)

**Advanced Development:**
5. 📘 advancedev.md (All phases for roadmap visibility)
6. 📗 docs/GO_LIVE_CHECKLIST.md (Launch readiness)
7. 📕 docs/EXTERNAL_SERVICES_SETUP.md (Cost estimates)

**Then:** Track progress, manage stakeholder communication

---

## ❓ FAQ: Where to Find...?

| Question | Answer |
|----------|--------|
| How do I set up my environment? | [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md) |
| How do I start working on a phase? | [QUICK_START.md](QUICK_START.md) |
| What's the advanced development roadmap? | [advancedev.md](advancedev.md) |
| How do I deploy to production? | [docs/RUNBOOK.md](docs/RUNBOOK.md) → Deployment Procedures |
| How do I set up external services? | [docs/EXTERNAL_SERVICES_SETUP.md](docs/EXTERNAL_SERVICES_SETUP.md) |
| What's the go-live checklist? | [docs/GO_LIVE_CHECKLIST.md](docs/GO_LIVE_CHECKLIST.md) |
| What's the tech stack? | [CLAUDE.md](CLAUDE.md) or [README.md](README.md) |
| How does pricing work? | [COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md) section 4 |
| What are package sizes? | [COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md) section 3 |
| How does delivery verification work? | [COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md) section 9 |
| What's the parcel lifecycle? | [CLAUDE.md](CLAUDE.md) or [COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md) section 7 |
| How does WhatsApp integration work? | [COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md) section 8 |
| What's the system architecture? | [ARCHITECTURE.md](ARCHITECTURE.md) |
| How are commits formatted? | [COMMITS.md](COMMITS.md) section "Commit Format" |
| What's the sprint plan? | [COMMITS.md](COMMITS.md) section "Development Phases & Commits" |
| What's the timeline? | [COMMITS.md](COMMITS.md) section "Timeline" |
| What are development rules? | [CLAUDE.md](CLAUDE.md) section "Development Rules" |
| How do I troubleshoot production issues? | [docs/RUNBOOK.md](docs/RUNBOOK.md) → Troubleshooting |
| What's the cost estimate for production? | [docs/EXTERNAL_SERVICES_SETUP.md](docs/EXTERNAL_SERVICES_SETUP.md) → Total Cost Estimate |
| How many sprints total? | 20 sprints (Phase 1–7) for core, then 8 phases (A-H) for advanced |
| Estimated launch date? | Mid-September 2026 (~20 weeks) |

---

## 🔑 Key Concepts Quick Reference

### Package Sizes & Capacity

| Size | Max Wt | Capacity Units |
|------|--------|---|
| S | 5 kg | 1 |
| M | 25 kg | 4 |
| L | 75 kg | 10 |
| XL | 200 kg | 30 |
| Bale | 200+ kg | 50 |

**Trip capacity:** 300 units max

### Parcel Lifecycle

```
BOOKED → LABEL_PRINTED → PICKED_UP → RECEIVED_AT_ORIGIN_HUB
  → LOADED_ON_LORRY → IN_TRANSIT → ARRIVED_AT_DESTINATION_HUB
  → OUT_FOR_DELIVERY → DELIVERED (with NIC + signature + photo)
                    OR DELIVERY_FAILED OR CANCELLED
```

### Parcel Number Format

```
CCC-YYYYMMDD-NNNNNN-X
│   │        │      └─ Check digit
│   │        └─ Sequence per day (6 digits)
│   └─ Booking date
└─ Brand prefix

Example: CCC-20251101-004572-7
```

### Pricing Formula

```
final_price = base_price
            + (doorstep_pickup ? pickup_surcharge : 0)
            + (doorstep_drop ? drop_surcharge : 0)
            + (express ? express_surcharge : 0)
            + (insurance ? declared_value × 1.5% : 0)
            + (cod ? cod_fee : 0)
            - (discount : 0)
```

### Routes (Static Corridors)

```
CMB-KDY  (Colombo ↔ Kandy)
CMB-GL   (Colombo ↔ Galle)
CMB-KRG  (Colombo ↔ Kurunegala)
CMB-ADP  (Colombo ↔ Anuradhapura)
CMB-JFN  (Colombo ↔ Jaffna)
CMB-BC   (Colombo ↔ Batticaloa)
KDY-GL   (Kandy ↔ Galle)
```

### User Roles

| Role | Surface | Main Action |
|------|---------|------------|
| Sender (Customer) | Web + App | Book, pay, track |
| Receiver | Tracking page + WhatsApp | View status |
| Driver | Mobile app | Scan, deliver, capture proof |
| Hub Staff | Hub Console | Scan IN/OUT |
| Hub Manager | Hub Console | All staff + overrides |
| Ops Admin | Admin Web | Trips, assignments |
| Finance Admin | Admin Web | Pricing, payouts |
| Support Admin | Admin Web | Tickets, comms |
| Super Admin | Admin Web | Everything |

---

## 🚀 Development Phases at a Glance

| Phase | Sprints | Focus | Status |
|-------|---------|-------|--------|
| **Foundation** | 1–3 | Backend scaffolding, database, auth | ✅ Complete |
| **Booking** | 4–6 | Parcel booking, pricing, trip assignment | ✅ Complete |
| **Scanning** | 7–9 | QR/barcode scanning, lifecycle events | ✅ Complete |
| **Delivery** | 10–11 | Delivery proof (NIC + signature + photo) | ✅ Complete |
| **Notifications** | 12–14 | WhatsApp, SMS, email, push | ✅ Complete |
| **Admin** | 15–17 | Trip mgmt, pricing, users, disputes | ✅ Complete |
| **Polish** | 18–20 | Testing, performance, launch | ✅ Complete |

### Advanced Development (NEW!)

| Phase | Focus | Status |
|-------|-------|--------|
| **A: Infrastructure** | Production setup (Database, API, Web, Mobile, Storage) | ⏳ Not Started |
| **B: Testing & QA** | Coverage 70%+, E2E, Label PDFs, Performance | ⏳ Not Started |
| **C: Feature Implementation** | WhatsApp, WebxPay, SMS, FCM, QR signing | ⏳ Not Started |
| **D: Security Hardening** | Audit, Data Privacy Act 2022, RBAC | ⏳ Not Started |
| **E: Performance Optimization** | Indexes, Redis caching, queue optimization | ⏳ Not Started |
| **F: Monitoring & Operations** | Sentry, Better Stack, APM, business metrics | ⏳ Not Started |
| **G: Documentation & Polish** | API docs, runbook, training, UX improvements, WCAG AA | 🔄 In Progress |
| **H: Go-Live Preparation** | Checklist, soft launch, full launch | ⏳ Not Started |

**Legend:** ✅ Complete | 🔄 In Progress | ⏳ Not Started | ⚠️ Blocked

---

## 📞 Getting Help

- **Confused about concepts?** → Read [CLAUDE.md](CLAUDE.md) or relevant section in [COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md)
- **Environment issues?** → Check [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md) troubleshooting
- **Architecture questions?** → Read [ARCHITECTURE.md](ARCHITECTURE.md)
- **Commit format?** → See [COMMITS.md](COMMITS.md)
- **Sprint info?** → Check [DEVELOPMENT_TRACKER.md](DEVELOPMENT_TRACKER.md) or [COMMITS.md](COMMITS.md)
- **Production deployment?** → Read [docs/RUNBOOK.md](docs/RUNBOOK.md)
- **External service setup?** → Read [docs/EXTERNAL_SERVICES_SETUP.md](docs/EXTERNAL_SERVICES_SETUP.md)
- **Quick start for a phase?** → Read [QUICK_START.md](QUICK_START.md)

---

## ✅ Checklist for First Session

- [ ] Read README.md
- [ ] Read CLAUDE.md or AGENTS.md
- [ ] Skim COLOMBO_CARGO_CONNECT_PLAN.md (focus on sections 1, 4, 7)
- [ ] Run SETUP_CHECKLIST.md (or bookmark for later)
- [ ] Understand key concepts (routes, trips, sizes, pricing, lifecycle)
- [ ] Know development rules (UUID, encryption, validation, services)
- [ ] Know commit format (Conventional Commits)
- [ ] Ready for advanced development? Read [advancedev.md](advancedev.md) and [QUICK_START.md](QUICK_START.md)

---

## 📚 Reference Links

- **Project Root:** `d:\CCC\`
- **Git Repo:** (Configured)
- **Issue Tracker:** (Link TBA)
- **Staging Deployment:** Railway (Backend), Vercel (Web Apps)
- **Production Deployment:** See [docs/EXTERNAL_SERVICES_SETUP.md](docs/EXTERNAL_SERVICES_SETUP.md)

---

## 🎯 Success Checklist

By project completion, we should have:

- ✅ Comprehensive documentation (YOU'RE READING IT!)
- ✅ Environment setup guide (SETUP_CHECKLIST.md)
- ✅ System architecture documented (ARCHITECTURE.md)
- ✅ Commit history tracked (COMMITS.md)
- ✅ Development roadmap clear (DEVELOPMENT_TRACKER.md)
- ✅ Advanced development roadmap (advancedev.md)
- ✅ Operations runbook (docs/RUNBOOK.md)
- ✅ External service setup guide (docs/EXTERNAL_SERVICES_SETUP.md)
- ✅ Go-live checklist (docs/GO_LIVE_CHECKLIST.md)
- ✅ Quick start guide (QUICK_START.md)
- ✅ Sprint 1-20: All core features implemented
- ⬜ Phase A-H: Advanced development (in progress)
- ⬜ UAT ready (TBD)
- ⬜ Production deployment (TBD)
- ⬜ Soft launch (100 users)
- ⬜ Full launch

---

**Last Updated:** May 23, 2026  
**Project Status:** Core Complete — Advanced Development Phase  
**Current Phase:** Phase G (Documentation) — Other phases pending  
**Next Step:** Follow [QUICK_START.md](QUICK_START.md) for your chosen phase! 🚀
