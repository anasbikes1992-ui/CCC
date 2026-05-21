# Colombo Cargo Connect — Database Schema (Phase 1)

**Version:** 1.0 (Phase 1 scope)
**Engine:** PostgreSQL 16 + PostGIS 3.4
**Conventions:** UUID v4 primary keys, `snake_case`, UTC timestamps (`timestamptz`), soft deletes via `deleted_at`, JSONB for config-shaped fields.

> This is the **target schema** that Phase 1 migrations must produce. Each table block is the SQL the matching `database/migrations/*.php` file should generate when run. Keep this doc and the migrations in lockstep — when one changes, the other must too.

---

## 0. Extensions and conventions

```sql
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS pgcrypto;   -- for gen_random_uuid()
CREATE EXTENSION IF NOT EXISTS citext;     -- for case-insensitive emails
```

Every table has:
- `id UUID PRIMARY KEY DEFAULT gen_random_uuid()`
- `created_at timestamptz NOT NULL DEFAULT now()`
- `updated_at timestamptz NOT NULL DEFAULT now()`
- `deleted_at timestamptz NULL` (soft delete) — except where explicitly noted as audit-only

Eloquent's `HasUuid` + `SoftDeletes` traits handle these.

---

## 1. `users`

```sql
CREATE TABLE users (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  phone           VARCHAR(20) UNIQUE NOT NULL,           -- E.164, e.g. +94771234567
  email           CITEXT UNIQUE NULL,
  full_name       VARCHAR(150) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,                 -- bcrypt
  role            VARCHAR(40) NOT NULL DEFAULT 'customer',
                  -- customer | driver | hub_staff | hub_manager
                  -- admin_ops | admin_finance | admin_support | admin_super
  preferred_lang  VARCHAR(8) NOT NULL DEFAULT 'en',      -- en | si | ta
  remember_token  VARCHAR(100) NULL,
  created_at      timestamptz NOT NULL DEFAULT now(),
  updated_at      timestamptz NOT NULL DEFAULT now(),
  deleted_at      timestamptz NULL
);
CREATE INDEX users_role_idx ON users(role) WHERE deleted_at IS NULL;
```

Spatie Permission's `model_has_roles` etc. tables are added by its migration; we keep the simple `role` column for fast filtering and policy decisions where one role per user is sufficient.

---

## 2. `hubs`

```sql
CREATE TABLE hubs (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code         VARCHAR(8) UNIQUE NOT NULL,    -- CMB, KDY, GAL, JFN, …
  name         VARCHAR(100) NOT NULL,
  address      TEXT NOT NULL,
  city         VARCHAR(80) NOT NULL,
  district     VARCHAR(80) NOT NULL,
  geo          GEOGRAPHY(POINT, 4326) NOT NULL,
  phone        VARCHAR(20) NULL,
  is_active    BOOLEAN NOT NULL DEFAULT true,
  created_at   timestamptz NOT NULL DEFAULT now(),
  updated_at   timestamptz NOT NULL DEFAULT now(),
  deleted_at   timestamptz NULL
);
CREATE INDEX hubs_geo_gix ON hubs USING GIST(geo);
```

Seed:
- `CMB — Colombo Hub` (6.9271, 79.8612)
- `KDY — Kandy Hub` (7.2906, 80.6337)

---

## 3. `routes`

```sql
CREATE TABLE routes (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code                VARCHAR(16) UNIQUE NOT NULL,   -- CMB-KDY, KDY-CMB
  origin_hub_id       UUID NOT NULL REFERENCES hubs(id) ON DELETE RESTRICT,
  destination_hub_id  UUID NOT NULL REFERENCES hubs(id) ON DELETE RESTRICT,
  display_name        VARCHAR(100) NOT NULL,         -- "Colombo → Kandy"
  estimated_duration_minutes INTEGER NOT NULL,
  is_active           BOOLEAN NOT NULL DEFAULT true,
  created_at          timestamptz NOT NULL DEFAULT now(),
  updated_at          timestamptz NOT NULL DEFAULT now(),
  deleted_at          timestamptz NULL,
  CHECK (origin_hub_id <> destination_hub_id)
);
CREATE INDEX routes_origin_idx ON routes(origin_hub_id) WHERE deleted_at IS NULL;
```

Seed: `CMB-KDY` and `KDY-CMB`, ~180 minutes each.

---

## 4. `package_sizes`

```sql
CREATE TABLE package_sizes (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code              VARCHAR(8) UNIQUE NOT NULL,    -- S, M, L, XL, BALE
  display_name      VARCHAR(40) NOT NULL,
  max_weight_kg     NUMERIC(8,2) NOT NULL,
  max_length_cm     INTEGER NOT NULL,
  max_width_cm      INTEGER NOT NULL,
  max_height_cm     INTEGER NOT NULL,
  capacity_units    INTEGER NOT NULL,
  sort_order        INTEGER NOT NULL,
  is_active         BOOLEAN NOT NULL DEFAULT true,
  created_at        timestamptz NOT NULL DEFAULT now(),
  updated_at        timestamptz NOT NULL DEFAULT now()
);
```

Seed (matches `CLAUDE.md` §2 table):

| code | display_name | max_weight_kg | max_l × w × h cm | capacity_units |
|---|---|---|---|---|
| S    | Small       | 5     | 30 × 25 × 15      | 1  |
| M    | Medium      | 25    | 60 × 45 × 40      | 4  |
| L    | Large       | 75    | 120 × 80 × 80     | 10 |
| XL   | Extra Large | 200   | 200 × 120 × 120   | 30 |
| BALE | Bale/Pallet | 500   | 120 × 100 × 150   | 50 |

---

## 5. `lorries`

```sql
CREATE TABLE lorries (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  registration_number VARCHAR(20) UNIQUE NOT NULL,    -- LX-1234
  type                VARCHAR(20) NOT NULL,            -- small | medium | large
  max_weight_kg       NUMERIC(8,2) NOT NULL,
  max_capacity_units  INTEGER NOT NULL DEFAULT 300,
  is_active           BOOLEAN NOT NULL DEFAULT true,
  created_at          timestamptz NOT NULL DEFAULT now(),
  updated_at          timestamptz NOT NULL DEFAULT now(),
  deleted_at          timestamptz NULL
);
```

---

## 6. `drivers`

```sql
CREATE TABLE drivers (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id           UUID UNIQUE NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
  license_number    VARCHAR(40) UNIQUE NOT NULL,
  license_expires_at DATE NOT NULL,
  is_active         BOOLEAN NOT NULL DEFAULT true,
  created_at        timestamptz NOT NULL DEFAULT now(),
  updated_at        timestamptz NOT NULL DEFAULT now(),
  deleted_at        timestamptz NULL
);
```

---

## 7. `pricing_matrix`

Lookup table: `(route, package_size) → base price + surcharges`.

```sql
CREATE TABLE pricing_matrix (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  route_id          UUID NOT NULL REFERENCES routes(id) ON DELETE CASCADE,
  package_size_id   UUID NOT NULL REFERENCES package_sizes(id) ON DELETE CASCADE,
  base_price_lkr    NUMERIC(10,2) NOT NULL,
  surcharges        JSONB NOT NULL DEFAULT '{}'::jsonb,
                    -- {
                    --   "doorstep_pickup_lkr": 200,
                    --   "doorstep_drop_lkr": 200,
                    --   "express_lkr": 300,
                    --   "insurance_pct": 1.5,
                    --   "cod_pct": 3.0,
                    --   "cod_min_lkr": 100
                    -- }
  effective_from    DATE NOT NULL DEFAULT CURRENT_DATE,
  effective_until   DATE NULL,
  created_at        timestamptz NOT NULL DEFAULT now(),
  updated_at        timestamptz NOT NULL DEFAULT now(),
  UNIQUE (route_id, package_size_id, effective_from)
);
CREATE INDEX pricing_matrix_lookup_idx
  ON pricing_matrix(route_id, package_size_id)
  WHERE effective_until IS NULL;
```

Seed (CMB↔KDY, both directions identical, per `CLAUDE.md` §3):

| route | size | base_lkr | surcharges (doorstep_pickup / doorstep_drop / express) |
|---|---|---|---|
| CMB-KDY / KDY-CMB | S    | 350  | 200 / 200 / 300 |
| CMB-KDY / KDY-CMB | M    | 700  | 350 / 350 / 500 |
| CMB-KDY / KDY-CMB | L    | 1500 | 600 / 600 / 800 |
| CMB-KDY / KDY-CMB | XL   | 3000 | 1000 / 1000 / 1500 |
| CMB-KDY / KDY-CMB | BALE | 5000 | 1500 / 1500 / 2000 |

Insurance, COD: same JSONB across all rows for MVP (1.5% / 3% / 100 floor).

---

## 8. `trips`

```sql
CREATE TABLE trips (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  trip_code           VARCHAR(20) UNIQUE NOT NULL,   -- TRP-20260601-CMB-KDY-06
  route_id            UUID NOT NULL REFERENCES routes(id),
  lorry_id            UUID NULL REFERENCES lorries(id),
  driver_id           UUID NULL REFERENCES drivers(id),
  scheduled_departure timestamptz NOT NULL,
  scheduled_arrival   timestamptz NOT NULL,
  actual_departure    timestamptz NULL,
  actual_arrival      timestamptz NULL,
  status              VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED',
                      -- SCHEDULED | LOADING | IN_TRANSIT | ARRIVED | UNLOADING
                      -- COMPLETED | CANCELLED
  capacity_units_max       INTEGER NOT NULL DEFAULT 300,
  capacity_units_used      INTEGER NOT NULL DEFAULT 0,
  bookings_close_at        timestamptz NOT NULL,
  created_at          timestamptz NOT NULL DEFAULT now(),
  updated_at          timestamptz NOT NULL DEFAULT now(),
  deleted_at          timestamptz NULL,
  CHECK (capacity_units_used >= 0),
  CHECK (capacity_units_used <= capacity_units_max)
);
CREATE INDEX trips_route_dep_idx
  ON trips(route_id, scheduled_departure)
  WHERE deleted_at IS NULL AND status IN ('SCHEDULED','LOADING');
```

Generation: `php artisan trips:generate` runs daily, creating 14 days of trips at 06:00 and 14:00 local for every active route.

---

## 9. `parcel_number_counters`

Internal sequence counter per day (see ADR 0003).

```sql
CREATE TABLE parcel_number_counters (
  date     DATE PRIMARY KEY,
  last_seq INTEGER NOT NULL DEFAULT 0
);
```

---

## 10. `parcels`

```sql
CREATE TABLE parcels (
  id                    UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  parcel_number         VARCHAR(24) UNIQUE NOT NULL,        -- CCC-YYYYMMDD-NNNNNN-X
  qr_token              TEXT NOT NULL,                       -- signed JWT (ADR 0003)

  customer_id           UUID NOT NULL REFERENCES users(id),
  trip_id               UUID NULL REFERENCES trips(id),

  route_id              UUID NOT NULL REFERENCES routes(id),
  package_size_id       UUID NOT NULL REFERENCES package_sizes(id),

  weight_kg             NUMERIC(8,2) NOT NULL,
  length_cm             INTEGER NULL,
  width_cm              INTEGER NULL,
  height_cm             INTEGER NULL,

  pickup_type           VARCHAR(16) NOT NULL,                -- hub | doorstep
  pickup_address        TEXT NULL,
  pickup_geo            GEOGRAPHY(POINT, 4326) NULL,
  pickup_hub_id         UUID NULL REFERENCES hubs(id),

  drop_type             VARCHAR(16) NOT NULL,                -- hub | doorstep
  drop_address          TEXT NULL,
  drop_geo              GEOGRAPHY(POINT, 4326) NULL,
  drop_hub_id           UUID NULL REFERENCES hubs(id),

  receiver_name         VARCHAR(150) NOT NULL,
  receiver_phone        VARCHAR(20) NOT NULL,

  declared_value_lkr    NUMERIC(12,2) NULL,
  cod_amount_lkr        NUMERIC(12,2) NULL,

  is_express            BOOLEAN NOT NULL DEFAULT false,
  has_insurance         BOOLEAN NOT NULL DEFAULT false,

  base_price_lkr        NUMERIC(10,2) NOT NULL,
  surcharges_lkr        NUMERIC(10,2) NOT NULL DEFAULT 0,
  discount_lkr          NUMERIC(10,2) NOT NULL DEFAULT 0,
  total_price_lkr       NUMERIC(10,2) NOT NULL,
  capacity_units        INTEGER NOT NULL,

  status                VARCHAR(40) NOT NULL DEFAULT 'BOOKED',  -- ADR 0002 enum
  status_changed_at     timestamptz NOT NULL DEFAULT now(),

  notes                 TEXT NULL,

  created_at            timestamptz NOT NULL DEFAULT now(),
  updated_at            timestamptz NOT NULL DEFAULT now(),
  deleted_at            timestamptz NULL,

  CHECK (pickup_type IN ('hub','doorstep')),
  CHECK (drop_type IN ('hub','doorstep')),
  CHECK (total_price_lkr >= 0),
  CHECK (capacity_units > 0)
);

CREATE INDEX parcels_customer_idx ON parcels(customer_id) WHERE deleted_at IS NULL;
CREATE INDEX parcels_trip_idx     ON parcels(trip_id)     WHERE deleted_at IS NULL;
CREATE INDEX parcels_status_idx   ON parcels(status)      WHERE deleted_at IS NULL;
CREATE INDEX parcels_number_lower_idx ON parcels(LOWER(parcel_number));
```

---

## 11. `parcel_events`

Append-only audit log of every status change AND illegal transition attempt.

```sql
CREATE TABLE parcel_events (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  parcel_id           UUID NOT NULL REFERENCES parcels(id) ON DELETE CASCADE,
  event_type          VARCHAR(40) NOT NULL,
                      -- One of the ADR 0002 stages, OR 'ILLEGAL_TRANSITION_ATTEMPT'
  from_status         VARCHAR(40) NULL,
  to_status           VARCHAR(40) NULL,
  actor_user_id       UUID NULL REFERENCES users(id),
  actor_role          VARCHAR(40) NULL,
  hub_id              UUID NULL REFERENCES hubs(id),
  trip_id             UUID NULL REFERENCES trips(id),
  scan_mode           VARCHAR(16) NOT NULL DEFAULT 'qr',   -- qr | barcode | manual | system
  device_id           VARCHAR(80) NULL,
  geo                 GEOGRAPHY(POINT, 4326) NULL,
  metadata            JSONB NOT NULL DEFAULT '{}'::jsonb,
  occurred_at         timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX parcel_events_parcel_idx ON parcel_events(parcel_id, occurred_at DESC);
CREATE INDEX parcel_events_type_idx   ON parcel_events(event_type);
```

No soft delete — this is the audit trail.

---

## 12. `delivery_proofs`

Created in Phase 1 with the schema below; fully wired in Phase 4.

```sql
CREATE TABLE delivery_proofs (
  id                       UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  parcel_id                UUID UNIQUE NOT NULL REFERENCES parcels(id) ON DELETE CASCADE,
  receiver_name_input      VARCHAR(150) NOT NULL,
  receiver_nic_encrypted   TEXT NOT NULL,        -- Crypt::encryptString output
  receiver_nic_last4       VARCHAR(4) NOT NULL,  -- for masked display
  signature_url            TEXT NOT NULL,        -- Supabase signed URL key
  signature_size_bytes     INTEGER NOT NULL,
  photo_url                TEXT NULL,
  photo_size_bytes         INTEGER NULL,
  delivered_at             timestamptz NOT NULL,
  delivered_by_user_id     UUID NOT NULL REFERENCES users(id),
  delivery_geo             GEOGRAPHY(POINT, 4326) NULL,
  device_id                VARCHAR(80) NULL,
  created_at               timestamptz NOT NULL DEFAULT now(),
  updated_at               timestamptz NOT NULL DEFAULT now(),
  CHECK (signature_size_bytes >= 5120)
);
```

NIC handling rules (enforced in Phase 4):
- `receiver_nic_encrypted` only ever holds Laravel `Crypt::encryptString()` output.
- `receiver_nic_last4` is for display masking; never used for matching.
- API responses return only `***123V` style strings.
- Logs mask via `LoggingMiddleware`.

---

## 13. `payments`

```sql
CREATE TABLE payments (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  parcel_id       UUID NOT NULL REFERENCES parcels(id) ON DELETE CASCADE,
  method          VARCHAR(20) NOT NULL,       -- cod | bank_transfer | card
  status          VARCHAR(20) NOT NULL DEFAULT 'pending',
                  -- pending | paid | failed | refunded
  amount_lkr      NUMERIC(10,2) NOT NULL,
  reference       VARCHAR(100) NULL,
  paid_at         timestamptz NULL,
  metadata        JSONB NOT NULL DEFAULT '{}'::jsonb,
  created_at      timestamptz NOT NULL DEFAULT now(),
  updated_at      timestamptz NOT NULL DEFAULT now(),
  CHECK (method IN ('cod','bank_transfer','card')),
  CHECK (status IN ('pending','paid','failed','refunded')),
  CHECK (amount_lkr >= 0)
);
CREATE INDEX payments_parcel_idx ON payments(parcel_id);
CREATE INDEX payments_status_idx ON payments(status) WHERE status = 'pending';
```

For MVP, `card` is allowed in the enum but never used; WebxPay wiring is v1.1.

---

## 14. `notifications_log`

Created in Phase 1; populated starting Phase 5.

```sql
CREATE TABLE notifications_log (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  parcel_id       UUID NULL REFERENCES parcels(id) ON DELETE SET NULL,
  user_id         UUID NULL REFERENCES users(id),
  channel         VARCHAR(20) NOT NULL,    -- whatsapp | sms | email | push
  template        VARCHAR(80) NULL,
  recipient       VARCHAR(120) NOT NULL,   -- phone or email
  status          VARCHAR(20) NOT NULL,    -- queued | sent | delivered | failed | fallback
  provider_msg_id VARCHAR(120) NULL,
  error_code      VARCHAR(40) NULL,
  error_message   TEXT NULL,
  payload         JSONB NOT NULL DEFAULT '{}'::jsonb,
  sent_at         timestamptz NULL,
  created_at      timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX notifications_log_parcel_idx ON notifications_log(parcel_id);
CREATE INDEX notifications_log_status_idx ON notifications_log(status, created_at);
```

---

## 15. Migration order (Phase 1 sprint 1.2 → 1.3)

Run in this exact order — later migrations depend on earlier FKs.

```
1.  create_extensions
2.  create_users_table                        (Laravel default + extra cols)
3.  create_personal_access_tokens_table       (Sanctum)
4.  create_permission_tables                  (Spatie)
5.  create_hubs_table
6.  create_routes_table
7.  create_package_sizes_table
8.  create_lorries_table
9.  create_drivers_table
10. create_pricing_matrix_table
11. create_trips_table
12. create_parcel_number_counters_table
13. create_parcels_table
14. create_parcel_events_table
15. create_delivery_proofs_table
16. create_payments_table
17. create_notifications_log_table
```

Seeders run after all migrations, in this order: hubs → routes → package_sizes → lorries → pricing_matrix → demo users (1 admin + 1 driver + 1 customer) → 14 days of trips.

---

## 16. Index review

For Phase 1 acceptance the following queries must return < 100 ms at seed scale (≤ 5,000 parcels):

| Query | Index used |
|---|---|
| List my parcels (customer dashboard) | `parcels_customer_idx` |
| List parcels for today's trip (driver) | `parcels_trip_idx` |
| Find parcel by parcel_number (manual entry) | `parcels_number_lower_idx` |
| Open trips on route, sorted by departure | `trips_route_dep_idx` |
| Latest events for parcel (tracking page) | `parcel_events_parcel_idx` |
| Pending payments (admin) | `payments_status_idx` |

Add no other indexes during Phase 1 — let real queries dictate further indexes from Phase 6 hardening onward.
