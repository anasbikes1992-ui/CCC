# Colombo Cargo Connect — API Specification (Phase 1)

**Version:** 1.0 (Phase 1 endpoints only)
**Base URL (local):** `http://localhost:8000/api/v1`
**Base URL (prod):** `https://api.cargo.lk/api/v1`
**Auth:** Laravel Sanctum bearer tokens unless noted as `[public]`.

> Phase 1 acceptance is "every endpoint below works as specified, verified by Pest tests + Postman". Phase 2/3 frontends consume these contracts; if anything here changes after Phase 1 ships, both frontends and the changelog get updated together.

---

## 1. Conventions

### Request
- `Content-Type: application/json` for all POST/PATCH unless multipart.
- `Accept: application/json` always.
- `Authorization: Bearer <token>` for authenticated endpoints.
- Phone numbers: E.164 (`+94771234567`).
- Money: integer LKR cents internally; all monetary fields in API are decimal LKR (`1050.00`).
- Timestamps: ISO-8601 UTC (`2026-06-01T08:30:00Z`).

### Response envelope (always)

Success:
```json
{
  "success": true,
  "data": { ... },
  "error": null,
  "meta": { "page": 1, "per_page": 50, "total": 372 }
}
```

Failure:
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

### Error codes

| HTTP | code | When |
|---|---|---|
| 400 | `BAD_REQUEST` | Generic malformed request |
| 401 | `UNAUTHENTICATED` | Missing / expired token |
| 401 | `INVALID_QR_TOKEN` | QR JWT failed verification (per ADR 0003) |
| 403 | `FORBIDDEN` | Authenticated but role/policy denies |
| 404 | `NOT_FOUND` | Resource doesn't exist |
| 409 | `CONFLICT` | E.g. email already in use |
| 409 | `TRIP_FULL` | Trip capacity insufficient for booking |
| 422 | `VALIDATION_ERROR` | Form Request failed |
| 422 | `ILLEGAL_STATUS_TRANSITION` | Per ADR 0002 |
| 429 | `RATE_LIMITED` | Throttle hit |
| 500 | `SERVER_ERROR` | Unhandled — Sentry captured |

### Pagination
`?limit=50&offset=0` — `limit` capped at 100. `meta.total` returns full count.

### Sorting
`?sort=-created_at` (prefix `-` for DESC).

### Caching
`GET /public/parcels/{number}/track` is cached 30 sec server-side and returns `ETag` + `Cache-Control: public, max-age=30`. All other GETs are no-cache by default.

### Rate limits
- `POST /auth/*` — 5/min per IP
- `GET /public/*` — 100/min per IP
- All other authenticated — 1000/hour per user

---

## 2. Health

### `GET /api/health` `[public]`

Liveness check.

```json
{
  "ok": true,
  "version": "0.1.0",
  "db": "up",
  "redis": "up",
  "time": "2026-06-01T08:30:00Z"
}
```

Returns 200 even if `db`/`redis` are `down` (so the LB knows the app is alive). Use Sentry alerts for dependency failures.

---

## 3. Auth

### `POST /api/v1/auth/register` `[public]`

```json
// Request
{
  "full_name": "Anaz Bikes",
  "phone": "+94771234567",
  "email": "anasbikes1992@gmail.com",
  "password": "secret-12-chars-min",
  "preferred_lang": "en"
}
```

```json
// 201 Response
{
  "success": true,
  "data": {
    "user": {
      "id": "5b3…",
      "full_name": "Anaz Bikes",
      "phone": "+94771234567",
      "email": "anasbikes1992@gmail.com",
      "role": "customer",
      "preferred_lang": "en"
    },
    "token": "1|aBcD…"
  },
  "error": null
}
```

Errors: `409 CONFLICT` (phone or email taken), `422 VALIDATION_ERROR`.

### `POST /api/v1/auth/login` `[public]`

```json
{ "phone": "+94771234567", "password": "secret-12-chars-min" }
```
Response: same `user + token` shape as register. `401 UNAUTHENTICATED` on bad credentials.

### `POST /api/v1/auth/logout`
No body. Revokes the current token. `204 No Content`.

### `GET /api/v1/auth/me`
Returns the current `user` object.

---

## 4. Customer

### `POST /api/v1/customer/parcels`

Books a parcel. Performs (in one transaction): validation → price quote → trip assignment → parcel insert → QR token sign → capacity decrement → notification queue.

```json
// Request
{
  "route_code": "CMB-KDY",
  "package_size_code": "M",
  "weight_kg": 12.5,
  "length_cm": 50, "width_cm": 40, "height_cm": 30,

  "pickup_type": "doorstep",
  "pickup_address": "23/4, Galle Rd, Colombo 4",
  "pickup_geo": { "lat": 6.8895, "lng": 79.8541 },

  "drop_type": "hub",
  "drop_hub_code": "KDY",

  "receiver_name": "Kasun P.",
  "receiver_phone": "+94712223344",

  "is_express": false,
  "has_insurance": false,
  "declared_value_lkr": null,
  "cod_amount_lkr": 4500,

  "preferred_trip_id": null,            // null = auto-assign next available
  "payment_method": "cod"               // cod | bank_transfer
}
```

```json
// 201 Response
{
  "success": true,
  "data": {
    "parcel": {
      "id": "f2c…",
      "parcel_number": "CCC-20260601-000042-7",
      "qr_token": "eyJhbGc…",            // for label PDF only; client never reuses
      "status": "BOOKED",
      "trip": {
        "id": "1c4…",
        "trip_code": "TRP-20260601-CMB-KDY-06",
        "scheduled_departure": "2026-06-01T00:30:00Z",
        "scheduled_arrival": "2026-06-01T03:30:00Z"
      },
      "price": {
        "base_lkr": 700,
        "surcharges_lkr": 350,
        "discount_lkr": 0,
        "cod_fee_lkr": 135,
        "total_lkr": 1185
      },
      "label_url": "https://api.cargo.lk/v1/customer/parcels/f2c…/label.pdf",
      "tracking_url": "https://track.cargo.lk/CCC-20260601-000042-7"
    },
    "payment": {
      "id": "3e7…",
      "method": "cod",
      "status": "pending",
      "amount_lkr": 1185
    }
  },
  "error": null
}
```

Errors:
- `422 VALIDATION_ERROR` — bad sizes, missing required fields per pickup/drop type
- `409 TRIP_FULL` — no trip on route has capacity within next 7 days

### `GET /api/v1/customer/parcels`

List the caller's parcels. Query params: `status`, `from`, `to`, pagination, sort.

### `GET /api/v1/customer/parcels/{id}`

Detail view including event timeline.

### `GET /api/v1/customer/parcels/{id}/label.pdf`

Returns `application/pdf`, 4×6 inch label with parcel number (large), QR (encoding `qr_token`), Code-128 barcode, addresses, route, size.

---

## 5. Driver

### `GET /api/v1/driver/trips?date=YYYY-MM-DD`

Trips assigned to the calling driver for the given day (defaults to today, Colombo TZ).

### `GET /api/v1/driver/trips/{id}/parcels`

All parcels on the trip with current status. Used by the driver app's parcel list.

### `POST /api/v1/driver/parcels/{id_or_number}/scan`

Records a status transition (per ADR 0002).

```json
// Request
{
  "qr_token": "eyJhbGc…",                 // OR omit + send X-Scan-Mode: manual + parcel_number in URL
  "event_type": "PICKED_UP",
  "geo": { "lat": 6.9271, "lng": 79.8612 },
  "device_id": "android-XYZ-9001",
  "occurred_at": "2026-06-01T07:42:11Z",   // optional; server uses now() if absent
  "metadata": {}
}
```

Headers: `X-Scan-Mode: qr | barcode | manual` (defaults to `qr`).

```json
// 200 Response
{
  "success": true,
  "data": {
    "parcel": {
      "id": "f2c…",
      "parcel_number": "CCC-20260601-000042-7",
      "status": "PICKED_UP",
      "status_changed_at": "2026-06-01T07:42:11Z"
    },
    "next_action": {
      "label": "Take to Colombo Hub",
      "expected_event": "RECEIVED_AT_ORIGIN_HUB"
    }
  },
  "error": null
}
```

Errors:
- `401 INVALID_QR_TOKEN` — JWT failed verification
- `422 ILLEGAL_STATUS_TRANSITION` — per ADR 0002, includes `details.allowed[]`
- `404 NOT_FOUND` — parcel doesn't exist
- `403 FORBIDDEN` — driver not assigned to this trip

### `POST /api/v1/driver/parcels/{id}/deliver`

Stub in Phase 1 (returns 501 Not Implemented). Fully wired in Phase 4. Spec lives in Phase 4 work.

---

## 6. Public

### `GET /api/v1/public/parcels/{parcel_number}/track` `[public]`

Cached 30 sec. Returns the timeline anyone with the parcel number can see.

```json
{
  "success": true,
  "data": {
    "parcel_number": "CCC-20260601-000042-7",
    "current_status": "IN_TRANSIT",
    "status_changed_at": "2026-06-01T01:05:30Z",
    "route": { "code": "CMB-KDY", "display_name": "Colombo → Kandy" },
    "estimated_arrival": "2026-06-01T03:30:00Z",
    "events": [
      { "event_type": "BOOKED",                     "occurred_at": "2026-05-31T15:21:00Z", "location": "Online" },
      { "event_type": "PICKED_UP",                  "occurred_at": "2026-06-01T00:42:00Z", "location": "Colombo 4" },
      { "event_type": "RECEIVED_AT_ORIGIN_HUB",     "occurred_at": "2026-06-01T00:55:00Z", "location": "Colombo Hub" },
      { "event_type": "LOADED_ON_LORRY",            "occurred_at": "2026-06-01T01:01:00Z", "location": "Colombo Hub" },
      { "event_type": "IN_TRANSIT",                 "occurred_at": "2026-06-01T01:05:30Z", "location": "En route" }
    ]
  },
  "error": null
}
```

Notes:
- Receiver name and phone are NOT returned here (privacy).
- `404 NOT_FOUND` if parcel number doesn't exist OR has bad check digit.

### `GET /api/v1/public/hubs` `[public]`

List of active hubs with code, name, city, geo. Used by the booking wizard.

---

## 7. Postman collection

A `docs/api/ccc-postman.json` is generated during Phase 1 with one request per endpoint above, pre-filled with sample bodies. Acceptance: every request in the collection returns the expected status code against the seeded local DB.

---

## 8. What's NOT in this spec yet

These appear in later phases. Documented when their phase begins:

- Phase 4: full `POST /driver/parcels/{id}/deliver` payload + delivery_proofs response
- Phase 5: WhatsApp inbound webhook `POST /webhooks/whatsapp`
- Phase 5: notification log query endpoints (admin-only)
- Phase 6: rate-limit headers, structured `Server-Timing` headers
- Phase 7+: admin endpoints, hub-staff endpoints, payment webhooks
