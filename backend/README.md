# Colombo Cargo Connect — Backend (Laravel 11)

RESTful API for the CCC platform. PHP 8.3, PostgreSQL 16 + PostGIS, Redis, Sanctum, Spatie Permission, JWT QR tokens.

> **Status:** Source files for Phase 1 are pre-staged. To bring the project online run the steps under "First-time setup" — every Phase 1 acceptance test should pass green when those finish.

---

## Project layout

```
backend/
├── app/
│   ├── Concerns/HasUuid.php
│   ├── Console/Commands/GenerateTripsCommand.php       # php artisan trips:generate
│   ├── Enums/                                          # ParcelStatus + lifecycle enums (ADR 0002)
│   ├── Exceptions/                                     # Domain exceptions → API error codes
│   ├── Http/
│   │   ├── Controllers/Api/V1/                         # Health, Auth, Customer/Parcel, Driver/Scan, PublicTracking
│   │   ├── Requests/                                   # Form Request validators
│   │   ├── Resources/                                  # JSON shaping (ParcelResource, TrackingResource)
│   │   └── Responses/                                  # Standard envelope + exception renderer
│   ├── Models/                                         # 13 Eloquent models, all UUID PKs
│   ├── Providers/                                      # AppServiceProvider, AuthServiceProvider
│   └── Services/                                       # Pricing, ParcelNumber, QrToken, TripAssignment, Scan, Booking
├── bootstrap/
├── config/                                             # 12 configs incl. permission, sanctum, services
├── database/
│   ├── migrations/                                     # 14 migrations matching docs/DB_SCHEMA.md
│   └── seeders/                                        # Hubs, Routes, Sizes, Lorries, Pricing, DemoUsers, Trips
├── public/
├── routes/
│   ├── api.php                                         # Per docs/API_SPEC.md
│   └── console.php                                     # Daily cron: trips:generate
├── storage/
└── tests/
    ├── Feature/                                        # Health, Auth, Booking, Scan, Tracking, GenerateTrips
    └── Unit/                                           # ParcelStatus matrix, ParcelNumber, QrToken, Pricing
```

---

## First-time setup

Prereqs: PHP 8.3, Composer 2, Docker Desktop running.

```powershell
# From repo root D:\CCC
docker compose up -d                          # start ccc-postgres + ccc-redis

# Inside D:\CCC\backend
copy .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed                    # creates schema + seeds CMB↔KDY data + 14 days of trips
php artisan serve                             # → http://localhost:8000
```

Verify health:

```powershell
curl http://localhost:8000/api/health
# {"success":true,"data":{"ok":true,"version":"0.1.0","db":"up","redis":"up","time":"..."},"error":null,"meta":{}}
```

Run the test suite:

```powershell
# Create the test database first (one-time)
docker exec -it ccc-postgres psql -U ccc -d postgres -c "CREATE DATABASE ccc_test;"
docker exec -it ccc-postgres psql -U ccc -d ccc_test -c "CREATE EXTENSION IF NOT EXISTS postgis;"
docker exec -it ccc-postgres psql -U ccc -d ccc_test -c "CREATE EXTENSION IF NOT EXISTS pgcrypto;"

php artisan test
```

Phase 1 acceptance: every test in `tests/Feature` and `tests/Unit` passes.

---

## Demo credentials (after `--seed`)

| Role | Phone | Password |
|---|---|---|
| Super admin | `+94770000001` | `password123` |
| Driver | `+94770000002` | `password123` |
| Customer | `+94770000003` | `password123` |

---

## See also

- `../ROADMAP.md` — phased plan
- `../CLAUDE.md` — project conventions
- `../ARCHITECTURE.md` — system architecture
- `../docs/adr/` — architecture decision records
- `../docs/DB_SCHEMA.md` — full schema reference
- `../docs/API_SPEC.md` — endpoint contracts
- `../docs/PRICING_RULES.md` — pricing algorithm + worked examples
