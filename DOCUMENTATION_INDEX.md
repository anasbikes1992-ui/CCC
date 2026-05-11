# 📚 CCC Documentation Index

**Project:** Colombo Cargo Connect  
**Status:** Foundation Phase - Ready for Development  
**Last Updated:** May 1, 2026  

---

## 🎯 Read These Files In This Order

### 1️⃣ **START HERE** — Quick Overview (5 min)
📄 [README.md](README.md)
- What is CCC?
- Quick navigation to all docs
- Tech stack overview
- Getting started guide

### 2️⃣ **BEFORE EVERY CODING SESSION** (10 min)
📄 [CLAUDE.md](CLAUDE.md)
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
- Questions for first session

### 7️⃣ **UNDERSTAND COMMIT PROCESS** (5 min)
📄 [COMMITS.md](COMMITS.md)
- Commit format (Conventional Commits)
- Type, scope, subject, body, footer
- Development phases & expected commits
- Statistics by phase
- Timeline estimate
- Commit review checklist

---

## 📋 Documentation Map

```
d:\CCC\
├── README.md                          ← Quick start + navigation
├── CLAUDE.md                          ← Read before every session
├── COLOMBO_CARGO_CONNECT_PLAN.md      ← Full specification
├── SETUP_CHECKLIST.md                 ← Environment setup
├── ARCHITECTURE.md                    ← System design
├── DEVELOPMENT_TRACKER.md             ← Progress tracking
├── COMMITS.md                         ← Commit history & format
└── DOCUMENTATION_INDEX.md             ← This file
```

---

## 🎓 Learning Path (By Role)

### 👨‍💻 Backend Developer

1. ✅ README.md (quick context)
2. ✅ CLAUDE.md (key concepts + DB rules)
3. ✅ COLOMBO_CARGO_CONNECT_PLAN.md (sections: 1–6, 12, 14, 17)
4. ✅ SETUP_CHECKLIST.md (phases 1, 3, 4, 5, 6)
5. ✅ ARCHITECTURE.md (Backend API layer, Data layer)
6. ✅ COMMITS.md (commit format + backend commits)

**Then:** Start Sprint 1 (scaffold backend)

### 🎨 Frontend Developer (Web)

1. ✅ README.md (quick context)
2. ✅ CLAUDE.md (key concepts + API conventions)
3. ✅ COLOMBO_CARGO_CONNECT_PLAN.md (sections: 7, 10–11, 17)
4. ✅ SETUP_CHECKLIST.md (phases 1, 5, 7)
5. ✅ ARCHITECTURE.md (Client layer, API conventions)
6. ✅ COMMITS.md (commit format + web commits)

**Then:** Start Sprint 6 (sender web portal)

### 📱 Mobile Developer (Flutter)

1. ✅ README.md (quick context)
2. ✅ CLAUDE.md (key concepts + API conventions)
3. ✅ COLOMBO_CARGO_CONNECT_PLAN.md (sections: 7, 9–11, 17)
4. ✅ SETUP_CHECKLIST.md (phases 1, 2, 5, 8)
5. ✅ ARCHITECTURE.md (Client layer, API conventions)
6. ✅ COMMITS.md (commit format + mobile commits)

**Then:** Start Sprint 7 (driver app base)

### 👔 DevOps / Infrastructure

1. ✅ README.md (quick context)
2. ✅ ARCHITECTURE.md (Deployment architecture, Monitoring)
3. ✅ SETUP_CHECKLIST.md (all phases for local dev)
4. ✅ DEVELOPMENT_TRACKER.md (tech stack)

**Then:** Set up DigitalOcean + Vercel + monitoring

### 📋 Project Manager / Product Owner

1. ✅ README.md (overview)
2. ✅ COLOMBO_CARGO_CONNECT_PLAN.md (full spec)
3. ✅ DEVELOPMENT_TRACKER.md (sprints, timeline, risks)
4. ✅ COMMITS.md (phase breakdown, statistics)

**Then:** Track progress against COMMITS.md timeline

---

## ❓ FAQ: Where to Find...?

| Question | Answer |
|----------|--------|
| How do I set up my environment? | [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md) |
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
| How many sprints total? | 20 sprints (Phase 1–7) |
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
| **Foundation** | 1–3 | Backend scaffolding, database, auth | Not started |
| **Booking** | 4–6 | Parcel booking, pricing, trip assignment | Not started |
| **Scanning** | 7–9 | QR/barcode scanning, lifecycle events | Not started |
| **Delivery** | 10–11 | Delivery proof (NIC + signature + photo) | Not started |
| **Notifications** | 12–14 | WhatsApp, SMS, email, push | Not started |
| **Admin** | 15–17 | Trip mgmt, pricing, users, disputes | Not started |
| **Polish** | 18–20 | Testing, performance, launch | Not started |

---

## 📞 Getting Help

- **Confused about concepts?** → Read [CLAUDE.md](CLAUDE.md) or relevant section in [COLOMBO_CARGO_CONNECT_PLAN.md](COLOMBO_CARGO_CONNECT_PLAN.md)
- **Environment issues?** → Check [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md) troubleshooting
- **Architecture questions?** → Read [ARCHITECTURE.md](ARCHITECTURE.md)
- **Commit format?** → See [COMMITS.md](COMMITS.md)
- **Sprint info?** → Check [DEVELOPMENT_TRACKER.md](DEVELOPMENT_TRACKER.md) or [COMMITS.md](COMMITS.md)

---

## ✅ Checklist for First Session

- [ ] Read README.md
- [ ] Read CLAUDE.md
- [ ] Skim COLOMBO_CARGO_CONNECT_PLAN.md (focus on sections 1, 4, 7)
- [ ] Run SETUP_CHECKLIST.md (or bookmark for later)
- [ ] Understand key concepts (routes, trips, sizes, pricing, lifecycle)
- [ ] Know development rules (UUID, encryption, validation, services)
- [ ] Know commit format (Conventional Commits)
- [ ] Ready to start Sprint 1? Go to [COMMITS.md](COMMITS.md) for Sprint 1 prompt

---

## 📚 Reference Links

- **Project Root:** `d:\CCC\`
- **Git Repo:** (Link TBA)
- **Issue Tracker:** (Link TBA)
- **Deployment:** DigitalOcean (API), Vercel (Web)

---

## 🎯 Success Checklist

By project completion, we should have:

- ✅ Comprehensive documentation (YOU'RE READING IT!)
- ✅ Environment setup guide (SETUP_CHECKLIST.md)
- ✅ System architecture documented (ARCHITECTURE.md)
- ✅ Commit history tracked (COMMITS.md)
- ✅ Development roadmap clear (DEVELOPMENT_TRACKER.md)
- ⬜ Sprint 1: Backend scaffolding (TBD)
- ⬜ Sprint 2–20: Implement features (TBD)
- ⬜ UAT ready (TBD)
- ⬜ Production deployment (TBD)

---

**Last Updated:** May 1, 2026  
**Project Status:** Documentation Complete - Ready for Sprint 1  
**Next Step:** Follow the learning path for your role, then START CODING! 🚀
