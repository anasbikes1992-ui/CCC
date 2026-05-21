# ADR 0001 — Tech Stack

**Status:** Accepted
**Date:** 2026-05-03
**Deciders:** Anaz (project owner)

---

## Context

Colombo Cargo Connect needs a backend API, two web frontends (sender portal + public tracking page), and a mobile driver app. The team is a single developer working in Cowork mode with Claude. The product targets Sri Lankan SMEs, retailers, and agri-exporters with a scheduled hub-to-hub freight model.

Constraints that shaped the decision:

- **Solo developer.** Picking unfamiliar stacks costs more than it saves. Maturity, community size, and AI-assist quality matter more than peak performance.
- **PII handling.** Receiver NIC numbers are encrypted at rest. The framework must make encrypted columns and field-level masking trivial.
- **Background jobs.** WhatsApp + SMS sends must be queued; status webhooks must be ack'd within seconds. A first-class queue is non-negotiable.
- **Geo data.** Routes, hubs, and scan lat/lng require spatial queries.
- **Sri Lanka payments + comms.** WebxPay (cards), Notify.lk (SMS), and Meta WhatsApp Cloud API are the de-facto local choices.
- **Lean MVP.** First release ships on a single route with no admin UI; ops is run from the database. The stack must scale up later without a rewrite.

---

## Decision

| Layer | Choice | Why |
|---|---|---|
| Backend | **Laravel 11** (PHP 8.3) | Mature; first-class queues, encryption, validation, testing (Pest); Sanctum auth; Spatie Permission for roles; large package ecosystem |
| Database | **PostgreSQL 16 + PostGIS** | Best-in-class spatial queries; JSONB for pricing/surcharge configs; UUID PKs; managed hosting cheap |
| Cache + Queue | **Redis 7** | Single dependency for cache, sessions, queues, and rate limiting |
| File storage | **Supabase Storage** (S3-compatible) | Generous free tier; signed URLs for private files; predictable pricing as PII volume grows |
| Auth | **Laravel Sanctum** (token + SPA modes) | Same backend serves SPA and mobile; no separate identity service needed at MVP scale |
| Web frontends | **Next.js 15** (App Router) + TypeScript strict + Tailwind + shadcn/ui | Same stack for sender + tracking; Server Components reduce client JS; Vercel deploy is one-click |
| Mobile (driver) | **Flutter 3** + Provider | Single codebase, native performance, mature camera/scanner libraries (`mobile_scanner`), trusted signature widget (`signature_pad`) |
| WhatsApp | **Meta Cloud API** (`v21.0`) | Only sanctioned automated outbound channel; `wa.me` is link-only, not an API |
| SMS | **Notify.lk** | Sri Lanka local provider, transactional pricing |
| Push | **Firebase Cloud Messaging** | Standard for Flutter; free at our volume |
| Payments | **WebxPay** for cards (deferred to v1.1); **COD + bank transfer** for MVP | Reduces v1 integration risk; lets us ship the lifecycle and scanning work first |
| Hosting (API) | **DigitalOcean App Platform** | Containerized Laravel, managed Postgres, low ops |
| Hosting (web) | **Vercel** | Tight Next.js integration, global CDN |
| Monitoring | **Sentry** + **Better Stack** | Sentry for error tracking across all three runtimes; Better Stack for uptime |

---

## Alternatives considered

- **Node/NestJS backend.** More familiar to many devs, but Laravel's queues, encryption, and Pest testing are stronger out of the box for this PII-heavy use case, and the solo-dev velocity is higher.
- **MySQL.** Loses PostGIS and JSONB ergonomics. Not worth it.
- **React Native instead of Flutter.** Comparable, but Flutter's `mobile_scanner` and `signature_pad` widgets and consistent rendering across cheap Android devices (the bulk of driver hardware in SL) tip the balance.
- **Firebase as the backend.** Locks data and pricing into Google; PostGIS-style queries are awkward; PII handling under Sri Lanka's Data Protection Act 2022 is harder to audit.
- **Supabase for auth + DB.** Tempting, but Laravel + managed Postgres gives more control over the parcel state machine, and Supabase Storage alone covers the file-storage need without lock-in to its auth.

---

## Consequences

### Positive
- One language per layer (PHP, TypeScript, Dart). No polyglot context-switching tax.
- Encryption, queues, validation, and testing are framework-default — less custom code, fewer security mistakes.
- Easy to hire later: every layer is a mainstream choice.

### Negative
- PHP scaling beyond a few hundred req/s requires careful tuning; revisit when MAU > 50k.
- Flutter app distribution outside the Play Store needs APK side-loading instructions for early drivers.
- Vercel + DigitalOcean = two deployment targets. Acceptable cost for the simplicity each gives.

### Follow-ups
- ADR 0002: parcel state machine + transition matrix (write during Phase 1).
- ADR 0003: NIC encryption + masking strategy (write during Phase 4).
- ADR 0004: WhatsApp template catalogue + SMS fallback policy (write during Phase 5).
