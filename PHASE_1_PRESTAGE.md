# Phase 1 — Pre-stage Inventory

> Phase 1 source code is **written but not yet verified**. Verification (running `composer install`, migrations, and the test suite) requires PHP + Composer on your Windows machine, which Phase 0 unblocks. Until then this is the inventory of what's in `D:\CCC\backend\`.

## Status

- Phase 0 task: `in_progress` (waiting on you for toolchain + accounts)
- Phase 1 task: `pending` (blocked by Phase 0)
- Phase 1 code: **pre-staged in `backend/`** — flips to `in_progress` the moment you finish Phase 0 and confirm tests run.

## What's in the box

### Project skeleton
- `composer.json` — Laravel 11, Sanctum 4, Spatie Permission 6, firebase/php-jwt 6, dompdf 3, Flysystem S3 (Supabase), Pest 3, Pint
- `artisan`, `bootstrap/app.php`, `bootstrap/providers.php`, `public/index.php`, `public/.htaccess`
- `phpunit.xml`, `pint.json`, `.editorconfig`, `.gitignore`

### Configuration (`config/`)
12 files: `app`, `auth`, `cache`, `cors`, `database`, `filesystems`, `logging`, `permission`, `queue`, `sanctum`, `services`, `session`

### Domain types (`app/Enums/`)
- `ParcelStatus` — 12 cases + `canTransitionTo()` matrix per ADR 0002
- `ParcelEventType` — same 12 + `ILLEGAL_TRANSITION_ATTEMPT`
- `TripStatus`, `PaymentMethod`, `PaymentStatus`

### Domain exceptions (`app/Exceptions/`)
- `IllegalStatusTransitionException` → `422 ILLEGAL_STATUS_TRANSITION`
- `QrTokenInvalidException` → `401 INVALID_QR_TOKEN`
- `TripFullException` → `409 TRIP_FULL`
- `PricingNotConfiguredException` → `500 SERVER_ERROR`

### Standard envelope (`app/Http/Responses/`)
- `ApiResponse` — `{success, data, error, meta}` per `docs/API_SPEC.md` §1
- `ApiExceptionRenderer` — translates every framework + domain exception into the envelope

### Models (`app/Models/`)
13 models, all UUID PKs via `HasUuid` trait:
`User`, `Hub`, `Route`, `PackageSize`, `Lorry`, `Driver`, `PricingMatrix`, `Trip`, `Parcel`, `ParcelEvent`, `DeliveryProof`, `Payment`, `NotificationLog`

### Services (`app/Services/`)
- `PricingService` — 8-step algorithm per `docs/PRICING_RULES.md`
- `ParcelNumberService` — `CCC-YYYYMMDD-NNNNNN-X` with Luhn check digit + atomic per-day sequence
- `QrTokenService` — JWT HS256, 30-day TTL, `iss/sub/pno/iat/exp/ver` payload
- `TripAssignmentService` — `lockForUpdate` + capacity check, throws `TripFullException` after 7 days
- `ScanService` — single source of truth for `parcel.status` mutations; logs `ILLEGAL_TRANSITION_ATTEMPT` audit events
- `BookingService` — orchestrates the full booking transaction: validate → quote → assign trip → insert parcel → sign QR → create payment → write BOOKED event

### Migrations (`database/migrations/`)
17 files in dependency order, including PostgreSQL extensions, Laravel framework tables (cache/jobs/sessions/PATs), Spatie permission tables, and all 14 domain tables. PostGIS `geography(POINT, 4326)` columns added via raw SQL because Laravel's schema builder doesn't natively support them.

### Seeders (`database/seeders/`)
`HubSeeder` (CMB + KDY with real coords), `RouteSeeder` (both directions, 180 min ETA), `PackageSizeSeeder` (S/M/L/XL/BALE), `LorrySeeder` (3 lorries), `PricingMatrixSeeder` (full CMB↔KDY × 5 sizes), `DemoUserSeeder` (admin + driver + customer), `TripSeeder` (delegates to `trips:generate`).

### Routes & commands
- `routes/api.php` — every Phase 1 endpoint per `docs/API_SPEC.md`
- `routes/console.php` — daily `trips:generate` cron at 02:00 Asia/Colombo
- `app/Console/Commands/GenerateTripsCommand.php` — idempotent, configurable via `--days` and `--from`

### Tests (`tests/`)
- `Unit/Enums/ParcelStatusTest.php` — exhaustive transition matrix (every legal cell allowed, every other cell rejected)
- `Unit/Services/ParcelNumberServiceTest.php` — format, sequencing, day reset, check-digit validation
- `Unit/Services/QrTokenServiceTest.php` — round-trip, tampered signature, wrong secret, expiry
- `Unit/Services/PricingServiceTest.php` — all 6 worked examples from `docs/PRICING_RULES.md`
- `Feature/HealthTest.php`
- `Feature/AuthTest.php` — register, login, validation
- `Feature/BookingFlowTest.php` — full POST `/customer/parcels` happy path returning `1050 LKR` total + `TRIP_FULL` case
- `Feature/ScanTransitionTest.php` — 4 sequential scans → tracking; illegal transition → 422 + audit row
- `Feature/TrackingTest.php` — 404 for missing/bad-checkdigit numbers, `Cache-Control` header
- `Feature/GenerateTripsCommandTest.php` — count assertions + idempotency

## What's NOT pre-staged (intentional, by Phase 1 scope)

- Label PDF rendering — wired in Phase 2 (sender web has the buy-flow context)
- Driver `/deliver` endpoint full payload — Phase 4
- WhatsApp + SMS dispatch — Phase 5 (jobs are stubs in Phase 1 services)
- Sentry wiring — Phase 6 hardening
- Admin endpoints — deferred to v1.1

## Verification path (when you start Phase 1)

1. Mark task #2 `in_progress`
2. From `D:\CCC` run `docker compose up -d`
3. Inside `D:\CCC\backend`:
   ```
   copy .env.example .env
   composer install
   php artisan key:generate
   php artisan migrate --seed
   ```
4. Create test DB:
   ```
   docker exec -it ccc-postgres psql -U ccc -d postgres -c "CREATE DATABASE ccc_test;"
   docker exec -it ccc-postgres psql -U ccc -d ccc_test -c "CREATE EXTENSION IF NOT EXISTS postgis;"
   docker exec -it ccc-postgres psql -U ccc -d ccc_test -c "CREATE EXTENSION IF NOT EXISTS pgcrypto;"
   ```
5. `php artisan test` — every test should pass.
6. Hit `http://localhost:8000/api/health` — expect 200 with `db: up, redis: up`.
7. If anything fails, paste the error here and I'll fix. We don't move to Phase 2 until **everything green**.

## Likely first-run gotchas

- `composer install` may fail on Windows without `ext-pgsql` enabled in `php.ini`. Fix: uncomment `extension=pdo_pgsql` and `extension=pgsql` in your `C:\php\php.ini` (or wherever PHP lives).
- If `php artisan migrate` complains about missing PostGIS, the extension SQL will fail silently because it's wrapped in `IF NOT EXISTS`. Make sure you used the `postgis/postgis:16-3.4` Docker image (the `docker-compose.yml` already pins this).
- If Pest tests fail to discover, run `composer dump-autoload` once.
- Sanctum tests require the `personal_access_tokens` table — ensured by migration `0001_01_01_000004`.
