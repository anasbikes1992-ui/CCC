# ✅ Phase 0 — Foundations & Accounts (Live Checklist)

> Mirror of `ROADMAP.md` §3 acceptance criteria, broken into "done by Claude" vs "you must do". Tick items as they go green. Phase 0 is `Done` only when **every** unchecked item below is checked.

---

## A. Repo scaffolding (done by Claude)

- [x] Mono-repo `.gitignore` covering Laravel + Next.js + Flutter
- [x] `docker-compose.yml` for local PostgreSQL 16 + PostGIS + Redis 7
- [x] `backend/` folder with `.env.example` + `README.md`
- [x] `web-sender/` folder with `.env.example` + `README.md`
- [x] `web-tracking/` folder with `.env.example` + `README.md`
- [x] `mobile-driver/` folder with `config.example.dart` + `README.md`
- [x] `docs/adr/0001-tech-stack.md` written
- [x] This checklist file created

## B. Local toolchain (you must do — on your Windows machine)

Each item: install, verify, paste the version into the box. Anything red blocks Phase 1.

- [ ] **PHP 8.3+** — `php -v` → `_____________________`
- [ ] **Composer 2.x** — `composer -V` → `_____________________`
- [ ] **Node.js 20 LTS** — `node -v` → `_____________________`
- [ ] **npm 10+** — `npm -v` → `_____________________`
- [ ] **Docker Desktop** running — `docker -v` → `_____________________`
- [ ] **Flutter 3.x** — `flutter --version` → `_____________________`
- [ ] **Flutter doctor clean** — `flutter doctor` (no red items)
- [ ] **Git** — `git --version` → `_____________________`
- [ ] **Editor of choice** (VS Code recommended; PHP Intelephense + Dart + Flutter + ESLint extensions)

> **Tip:** if installing PHP + Postgres + Redis natively on Windows is painful, skip the native installs for Postgres + Redis and rely on `docker compose up -d` (those two run inside containers). Native PHP, Node, and Flutter are still needed.

## C. Local infra up (you, after toolchain done)

- [ ] From `D:\CCC` run `docker compose up -d`
- [ ] `docker compose ps` shows both `ccc-postgres` and `ccc-redis` as `healthy`
- [ ] `psql -h 127.0.0.1 -U ccc -d ccc -c "CREATE EXTENSION IF NOT EXISTS postgis;"` succeeds
  - password: `ccc_local_dev_only`
  - if you don't have `psql` locally, use `docker exec -it ccc-postgres psql -U ccc -d ccc`
- [ ] `docker exec -it ccc-redis redis-cli ping` returns `PONG`

## D. Git repo (you)

- [ ] Create a private GitHub repo named `ccc` (or `colombo-cargo-connect`)
- [ ] In `D:\CCC` run:
  ```powershell
  git init
  git add .
  git commit -m "Initial: docs, roadmap, scaffolding (Phase 0)"
  git branch -M main
  git remote add origin git@github.com:<your-user>/ccc.git
  git push -u origin main
  ```
- [ ] Confirm GitHub shows `D:\CCC` contents on `main`

## E. Third-party accounts — REQUEST NOW (you)

These take real time. Submit on Phase 0 day one so they're ready by Phase 5.

- [ ] **WhatsApp Cloud API** — https://business.facebook.com → create Business Account → submit business verification → register a phone number → save IDs into `backend/.env`
  - Status: `submitted` / `verified`
  - Phone Number ID: `_____________________`
  - Business Account ID: `_____________________`
  - Permanent access token generated and stored in password manager
- [ ] **Supabase** — https://supabase.com/dashboard → new project (region: closest available to SL, e.g. Singapore) → save URL + anon key + service-role key into `backend/.env`
  - Project URL: `_____________________`
  - Create two storage buckets: `ccc-labels` (public read), `ccc-proofs` (private)
- [ ] **Notify.lk** — https://notify.lk → register, top up token credits → save User ID + API key into `backend/.env`
- [ ] **Firebase** — https://console.firebase.google.com → new project → add Android app (package id: `lk.cargo.driver`) → download `google-services.json` (do NOT commit)
- [ ] **DigitalOcean** — https://cloud.digitalocean.com → account created (billing live)
- [ ] **Vercel** — https://vercel.com → account created (linked to your GitHub)
- [ ] **Sentry** (free tier) — https://sentry.io → create org + 3 projects: `ccc-backend`, `ccc-web`, `ccc-mobile`
- [ ] **Better Stack** (free tier) — https://betterstack.com → account created

## G. Pre-staged Phase 1 contracts (done by Claude — review at your leisure)

These are the contracts Phase 1 code will be built against. Reading them now means there are zero design questions outstanding when the actual Laravel work starts — every migration, endpoint, and service has a target.

- [x] `docs/adr/0002-parcel-state-machine.md` — the 12 states, full legal-transition matrix, single source of truth, side-effects per transition. **Closes risk #2 in `ROADMAP.md`.**
- [x] `docs/adr/0003-parcel-number-and-qr-token.md` — `CCC-YYYYMMDD-NNNNNN-X` format, Luhn check digit, atomic per-day sequence, JWT QR payload, manual-entry fallback rules
- [x] `docs/DB_SCHEMA.md` — full SQL DDL for all 14 Phase 1 tables, indexes, seed data for hubs/routes/sizes/pricing matrix, migration order
- [x] `docs/API_SPEC.md` — every Phase 1 endpoint with request/response examples, error codes, rate limits, and what's explicitly deferred
- [x] `docs/PRICING_RULES.md` — 8-step algorithm, 6 worked examples (incl. the `1050 LKR` reference example from `ARCHITECTURE.md`), full Phase 1 test matrix

When you read these, push back on anything that looks wrong before Phase 1 starts. Changing a contract during coding is 5x the cost of changing it now.

## F. Acceptance criteria (Phase 0 → Done)

When every box above is ticked AND the four commands below all succeed on your machine, Phase 0 is `Done`. Tell me and I'll mark task #1 complete and move you to Phase 1.

```powershell
php artisan --version              # any Laravel version is fine — proves PHP works
docker exec -it ccc-postgres psql -U ccc -d ccc -c "SELECT PostGIS_Version();"
docker exec -it ccc-redis redis-cli ping
flutter doctor
```

WhatsApp business verification doesn't have to be approved yet — just **submitted** (status visible in Meta Business Suite).
