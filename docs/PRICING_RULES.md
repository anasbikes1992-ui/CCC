# Colombo Cargo Connect — Pricing Rules

**Version:** 1.0 (Phase 1 / MVP)
**Owners:** PricingService (Laravel)
**Source of truth in DB:** `pricing_matrix` table (see `DB_SCHEMA.md` §7)

> Pricing is **not per-km**. It is `(route, package_size) → base price + surcharges`. This file is the canonical algorithm; the test suite for `PricingService` mirrors every rule below.

---

## 1. Inputs

```
quoteRequest = {
  routeCode: "CMB-KDY",
  packageSizeCode: "S" | "M" | "L" | "XL" | "BALE",
  pickupType: "hub" | "doorstep",
  dropType:   "hub" | "doorstep",
  isExpress: boolean,
  hasInsurance: boolean,
  declaredValueLkr: number | null,        // required if hasInsurance
  codAmountLkr: number | null,            // null if no COD
  promoCode: string | null                // ignored in MVP
}
```

## 2. Outputs

```
quote = {
  base_lkr: number,
  surcharges_lkr: number,    // sum of doorstep_pickup + doorstep_drop + express + insurance
  cod_fee_lkr: number,       // shown separately so customer sees the COD cost
  discount_lkr: number,      // 0 in MVP
  total_lkr: number          // base + surcharges + cod_fee - discount
}
```

All rounded to **2 decimal places, half-up**, after every step.

## 3. Algorithm

### Step 1 — Base price
Lookup `pricing_matrix` row for `(route_id, package_size_id)` where `effective_until IS NULL`. The `base_price_lkr` column.

If no row found → throw `PricingNotConfiguredException` (`500 SERVER_ERROR` to caller).

### Step 2 — Doorstep pickup surcharge
If `pickupType == "doorstep"`: add `surcharges.doorstep_pickup_lkr` from the JSONB. Else 0.

### Step 3 — Doorstep drop surcharge
If `dropType == "doorstep"`: add `surcharges.doorstep_drop_lkr`. Else 0.

### Step 4 — Express surcharge
If `isExpress`: add `surcharges.express_lkr`. Else 0.

### Step 5 — Insurance
If `hasInsurance`:
- Require `declaredValueLkr > 0` (validation; reject earlier with `422`).
- `insurance_lkr = round(declaredValueLkr * surcharges.insurance_pct / 100, 2)`
- Add to surcharges total.

### Step 6 — COD fee (presented separately, not lumped into surcharges)
If `codAmountLkr > 0`:
- `pct_fee = codAmountLkr * surcharges.cod_pct / 100`
- `cod_fee_lkr = max(pct_fee, surcharges.cod_min_lkr)`
- Round half-up to 2dp.

If no COD: `cod_fee_lkr = 0`.

### Step 7 — Discounts
MVP: `discount_lkr = 0`. Loyalty / promo logic deferred to v1.1.

### Step 8 — Total
```
total_lkr = base_lkr + surcharges_lkr + cod_fee_lkr - discount_lkr
```

## 4. Rule order matters

The order above is the contract. Tests assert that flipping it (e.g. computing insurance against post-surcharge subtotal) produces a different number — so any future refactor that breaks the order fails loudly.

## 5. Worked examples

All using the seed CMB↔KDY matrix from `DB_SCHEMA.md` §7.

### Example A — Plain hub-to-hub Small
Inputs: route CMB-KDY, size S, pickup hub, drop hub, no express, no insurance, no COD.

```
base = 350
surcharges = 0
cod_fee = 0
total = 350
```

### Example B — Doorstep both ends Medium
Inputs: route CMB-KDY, size M, pickup doorstep, drop doorstep, no express, no insurance, no COD.

```
base = 700
surcharges = 350 + 350 = 700
cod_fee = 0
total = 1400
```

### Example C — The CCC plan reference example
Inputs: route CMB-KDY, size M, pickup doorstep, drop hub, no express, no insurance, no COD.

```
base = 700
surcharges = 350 (doorstep pickup only)
cod_fee = 0
total = 1050
```

Matches the example in `ARCHITECTURE.md` §"Booking to Delivery" sequence (1050 LKR).

### Example D — Express Large with insurance and COD
Inputs: route CMB-KDY, size L, pickup doorstep, drop doorstep, express, insurance on declared 25,000 LKR, COD 25,000 LKR.

```
base       = 1500
+ pickup   = 600
+ drop     = 600
+ express  = 800
+ insurance= 25000 * 1.5% = 375.00
surcharges = 600 + 600 + 800 + 375 = 2375
cod_fee    = max(25000 * 3%, 100) = max(750, 100) = 750.00
discount   = 0
total      = 1500 + 2375 + 750 - 0 = 4625.00
```

### Example E — COD floor kicks in
Inputs: as A, plus COD 1,000 LKR.

```
base = 350
surcharges = 0
cod_fee = max(1000 * 3%, 100) = max(30, 100) = 100.00
total = 450.00
```

### Example F — Bale, all surcharges, big insurance
Inputs: CMB-KDY, BALE, doorstep both ends, express, insurance on 200,000, COD 100,000.

```
base       = 5000
+ pickup   = 1500
+ drop     = 1500
+ express  = 2000
+ insurance= 200000 * 1.5% = 3000.00
surcharges = 1500 + 1500 + 2000 + 3000 = 8000
cod_fee    = max(100000 * 3%, 100) = max(3000, 100) = 3000.00
total      = 5000 + 8000 + 3000 = 16000.00
```

## 6. Test matrix (Phase 1 acceptance)

`tests/Unit/Services/PricingServiceTest.php` must include, at minimum:

- All 5 sizes × hub-hub baseline → matches matrix base price exactly.
- All 5 sizes × doorstep pickup only → adds correct pickup surcharge.
- All 5 sizes × doorstep drop only → adds correct drop surcharge.
- All 5 sizes × doorstep both → adds both.
- Express toggle adds the express surcharge for each size.
- Insurance with declared value calculates 1.5%.
- Insurance without declared value → throws validation error.
- COD with amount above floor → percentage applies.
- COD with amount below floor → floor applies.
- COD = 0 → cod_fee = 0.
- Total = base + surcharges + cod_fee (idempotent on multiple calls).
- Rounding: feed values that produce 0.005 rounding boundary → asserts half-up.
- Missing pricing matrix row → throws `PricingNotConfiguredException`.

## 7. Future hooks (NOT in MVP)

These slots exist in the algorithm but resolve to no-ops for v1:

- Per-customer discount tier (B2B contracts).
- Volume / subscription credits.
- Time-of-day premiums (peak vs off-peak trips).
- Promo codes.

When v1.1 turns these on, only Step 7 changes; the rest of the algorithm is stable.
