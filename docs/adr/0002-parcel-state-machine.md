# ADR 0002 — Parcel State Machine

**Status:** Accepted
**Date:** 2026-05-03
**Deciders:** Anaz
**Supersedes:** —
**Related:** ADR 0001 (tech stack), `CLAUDE.md` §4 (Parcel Lifecycle), `ROADMAP.md` §13 risk #2

---

## Context

A parcel moves through 10 lifecycle stages from booking to delivery. The roadmap risk register lists "status-transition bugs corrupt parcel state" as risk #2. Without a single, exhaustively-tested source of truth for which transitions are legal, the bugs that cause "parcel stuck", "parcel skipped a stage", or "parcel delivered before it was loaded" are silent and only surface in production from customer complaints.

This ADR locks down:
1. The exact set of states.
2. The legal transitions between them.
3. Where transition validation lives (one place, not scattered).
4. What happens on an illegal transition.
5. What auxiliary side-effects fire on each transition.

Implementation lives in Phase 1.

---

## Decision

### 1. The 10 states

| # | State | Meaning | Terminal? |
|---|---|---|---|
| 1 | `BOOKED` | Customer completed booking; payment may still be pending | no |
| 2 | `LABEL_PRINTED` | Label PDF generated and downloaded or printed | no |
| 3 | `PICKED_UP` | Driver scanned at sender's location OR sender dropped at hub | no |
| 4 | `RECEIVED_AT_ORIGIN_HUB` | Hub staff scanned IN at origin hub | no |
| 5 | `LOADED_ON_LORRY` | Hub staff scanned during loading | no |
| 6 | `IN_TRANSIT` | Lorry departed origin hub | no |
| 7 | `ARRIVED_AT_DESTINATION_HUB` | Hub staff scanned IN at destination hub | no |
| 8 | `OUT_FOR_DELIVERY` | Hub staff scanned OUT to delivery driver | no |
| 9 | `DELIVERED` | Receiver signed; NIC + signature + photo captured | **yes** |
| — | `DELIVERY_FAILED` | Delivery attempt failed (recipient absent, refused, address wrong) | retryable |
| — | `CANCELLED` | Cancelled at any point before delivery | **yes** |
| — | `RETURNED_TO_ORIGIN` | After repeat delivery failure, sent back to origin hub | **yes** |

Three out-of-band states (`DELIVERY_FAILED`, `CANCELLED`, `RETURNED_TO_ORIGIN`) sit outside the linear path.

### 2. Legal transition matrix

`current → allowed_next[]`. Anything not listed is **illegal** and must be rejected.

| From | Legal next states |
|---|---|
| `BOOKED` | `LABEL_PRINTED`, `PICKED_UP`, `CANCELLED` |
| `LABEL_PRINTED` | `PICKED_UP`, `CANCELLED` |
| `PICKED_UP` | `RECEIVED_AT_ORIGIN_HUB`, `CANCELLED` |
| `RECEIVED_AT_ORIGIN_HUB` | `LOADED_ON_LORRY`, `CANCELLED` |
| `LOADED_ON_LORRY` | `IN_TRANSIT`, `RECEIVED_AT_ORIGIN_HUB` *(unload before depart)* |
| `IN_TRANSIT` | `ARRIVED_AT_DESTINATION_HUB` |
| `ARRIVED_AT_DESTINATION_HUB` | `OUT_FOR_DELIVERY` |
| `OUT_FOR_DELIVERY` | `DELIVERED`, `DELIVERY_FAILED` |
| `DELIVERY_FAILED` | `OUT_FOR_DELIVERY` *(retry)*, `RETURNED_TO_ORIGIN` |
| `DELIVERED` | *(none — terminal)* |
| `CANCELLED` | *(none — terminal)* |
| `RETURNED_TO_ORIGIN` | *(none — terminal)* |

Notes:
- `LABEL_PRINTED` is optional. Going `BOOKED → PICKED_UP` directly is legal (label printed at hub at pickup time).
- `LOADED_ON_LORRY → RECEIVED_AT_ORIGIN_HUB` covers the unload-before-depart edge case (manifest correction).
- `DELIVERY_FAILED → OUT_FOR_DELIVERY` allows retries. After N failed attempts (config, default 3), ops must move it to `RETURNED_TO_ORIGIN` manually — no automatic terminal transition.
- `CANCELLED` is reachable from any pre-pickup state. Once at the hub or beyond, cancellation requires ops override (creates a `CANCELLED` event with `actor.role = admin_super` only).

### 3. Single source of truth

Implementation contract (Phase 1):

```php
// app/Enums/ParcelStatus.php
enum ParcelStatus: string {
    case BOOKED = 'BOOKED';
    case LABEL_PRINTED = 'LABEL_PRINTED';
    case PICKED_UP = 'PICKED_UP';
    case RECEIVED_AT_ORIGIN_HUB = 'RECEIVED_AT_ORIGIN_HUB';
    case LOADED_ON_LORRY = 'LOADED_ON_LORRY';
    case IN_TRANSIT = 'IN_TRANSIT';
    case ARRIVED_AT_DESTINATION_HUB = 'ARRIVED_AT_DESTINATION_HUB';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case DELIVERED = 'DELIVERED';
    case DELIVERY_FAILED = 'DELIVERY_FAILED';
    case CANCELLED = 'CANCELLED';
    case RETURNED_TO_ORIGIN = 'RETURNED_TO_ORIGIN';

    /** Single authoritative transition matrix. */
    public function canTransitionTo(self $next): bool { /* matches §2 above */ }

    public function isTerminal(): bool {
        return in_array($this, [self::DELIVERED, self::CANCELLED, self::RETURNED_TO_ORIGIN]);
    }
}
```

Every status change in the system MUST go through `ScanService::record(...)`. Direct `$parcel->update(['status' => ...])` is forbidden — enforced by:
- A `Parcel::saving` Eloquent event that throws if `status` is dirty and the change isn't accompanied by a `ParcelEvent` insert in the same DB transaction.
- A code-review checklist item (and later, a static-analysis rule).

### 4. Behaviour on illegal transitions

`ScanService::record(...)` returns HTTP 422 with:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "ILLEGAL_STATUS_TRANSITION",
    "message": "Cannot transition parcel from BOOKED to DELIVERED",
    "details": {
      "parcel_id": "…",
      "from": "BOOKED",
      "to": "DELIVERED",
      "allowed": ["LABEL_PRINTED", "PICKED_UP", "CANCELLED"]
    }
  }
}
```

The illegal transition attempt IS logged as a `parcel_event` row with `event_type = 'ILLEGAL_TRANSITION_ATTEMPT'` (separate enum from the legal stages) so we can spot patterns of abuse or driver app bugs.

### 5. Side-effects per transition

Triggered by `ScanService::record(...)` via the queue:

| Transition into | Notification | Other side-effects |
|---|---|---|
| `BOOKED` | WhatsApp `booking_confirmed` to sender | Decrement trip capacity; QR token issued |
| `LABEL_PRINTED` | none | none (UI-only) |
| `PICKED_UP` | WhatsApp `parcel_picked_up` to sender | none |
| `RECEIVED_AT_ORIGIN_HUB` | none | Hub manifest counter +1 |
| `LOADED_ON_LORRY` | none | Lorry manifest counter +1 |
| `IN_TRANSIT` | WhatsApp `in_transit` to receiver | Trip status → `IN_TRANSIT` if all parcels loaded |
| `ARRIVED_AT_DESTINATION_HUB` | WhatsApp `arrived_destination` to receiver | Trip status → `ARRIVED` if all parcels arrived |
| `OUT_FOR_DELIVERY` | WhatsApp `out_for_delivery` to receiver | none |
| `DELIVERED` | WhatsApp `delivered` to sender + receiver | Increment driver completed count; release reserved capacity |
| `DELIVERY_FAILED` | WhatsApp `delivery_failed` to sender | Increment retry counter |
| `CANCELLED` | WhatsApp `cancelled` to sender | Refund hold released; trip capacity returned |
| `RETURNED_TO_ORIGIN` | WhatsApp `returned` to sender | Routed onto next return trip |

WhatsApp templates are wired in Phase 5 — until then, the queue jobs are no-ops that just log.

---

## Alternatives considered

- **Workflow engine (Symfony Workflow component, state-machine package).** Overkill for 12 states; adds a dependency for ergonomics we get from a plain enum + matrix.
- **Allowing arbitrary transitions with audit only.** Rejected — risk #2 in the roadmap is exactly this. Must be enforced at write time.
- **Storing the transition matrix in a DB table.** Tempting for ops flexibility, but the matrix encodes business invariants — changing it should be a code review, not a SQL UPDATE.

---

## Consequences

### Positive
- One file to read to know every legal parcel transition.
- Pest table-driven test asserts every (from, to) cell — gives confidence equal to a formal model checker.
- Illegal transitions create observable signal (`ILLEGAL_TRANSITION_ATTEMPT` events) instead of silent corruption.

### Negative
- Adding a new state requires touching the enum, the matrix, the side-effects table, the test, and any UI stepper. By design.
- Hub-staff edge cases (e.g. unloading after loading) need ops override flows we don't have UIs for in MVP — handled via Tinker.

### Follow-ups
- Phase 1: implement `ParcelStatus` enum, `ScanService`, the saving-event guard, and the table-driven Pest test.
- Phase 1: write `parcel_events` migration with both legal stage enum and `ILLEGAL_TRANSITION_ATTEMPT` audit type.
- Phase 5: wire side-effect notification jobs to real WhatsApp templates.
