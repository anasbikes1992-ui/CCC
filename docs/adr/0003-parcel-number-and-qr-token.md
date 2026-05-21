# ADR 0003 — Parcel Number Scheme & QR Token

**Status:** Accepted
**Date:** 2026-05-03
**Deciders:** Anaz
**Related:** ADR 0001 (tech stack), `CLAUDE.md` §5 (QR/Barcode), Phase 1 services `ParcelNumberService` + `QrTokenService`

---

## Context

Every parcel needs:
1. A **human-friendly identifier** customers can read aloud, type into a tracking page, and dictate over the phone.
2. A **machine-friendly identifier** drivers and hub staff scan from a label that can't be forged or guessed.

These two needs conflict if collapsed into one value. A short customer-facing number is easy to enumerate; a long random token is hostile to a customer reading "ABC-123-XYZ-LMN" off a label over a noisy phone line.

We split them: a **parcel number** for humans (printed in big text on the label, in URLs, in WhatsApp messages) and a **QR token** for machines (encoded inside the QR + barcode, never shown as text).

---

## Decision

### 1. Parcel number format

```
CCC-YYYYMMDD-NNNNNN-X
```

- `CCC` — fixed brand prefix.
- `YYYYMMDD` — booking date in `Asia/Colombo` timezone.
- `NNNNNN` — zero-padded 6-digit per-day sequence, restarts at `000001` each midnight Colombo time.
- `X` — single check digit (Luhn mod 10 over the digits of `YYYYMMDD || NNNNNN`).

Examples:
- `CCC-20260601-000001-3`
- `CCC-20260601-000042-7`
- `CCC-20271231-099999-2`

**Properties:**
- Length: 22 chars total. Fits in any QR / barcode at low density.
- Dictation: 14 spoken digits + the brand. Trial readings indicate ~10 sec to read aloud reliably.
- Collision-proof by construction (date + sequence is unique).
- Tampering protection: changing any digit invalidates the check digit; manual entry fallback rejects bad numbers immediately.
- 6-digit sequence supports 999,999 parcels per day. Far above any plausible MVP volume; revisit only if we ever cross ~100k/day.

### 2. Sequence allocation

Atomic: a Postgres sequence per day, created lazily on the first booking of that day. The Laravel implementation uses an `INSERT ... ON CONFLICT DO NOTHING` against a `parcel_number_counters (date, last_seq)` table inside a `SELECT ... FOR UPDATE` so two concurrent bookings can never claim the same `NNNNNN`.

```sql
-- Pseudo-DDL; real migration in Phase 1
CREATE TABLE parcel_number_counters (
  date DATE PRIMARY KEY,
  last_seq INTEGER NOT NULL DEFAULT 0
);
```

Service contract:

```php
ParcelNumberService::generate(): string
// 1. BEGIN; SELECT last_seq FROM parcel_number_counters WHERE date = today FOR UPDATE;
// 2. INSERT ... ON CONFLICT (date) DO UPDATE SET last_seq = parcel_number_counters.last_seq + 1
//    RETURNING last_seq;
// 3. Compute check digit. Return formatted string. COMMIT.
```

### 3. Check digit algorithm

Standard **Luhn mod 10** over the 14 digits of `YYYYMMDDNNNNNN` (concatenation, no separators). Reasons:
- Industry-standard, well-tested, single-page implementation.
- Catches every single-digit error and every adjacent transposition (the two error modes humans actually make).
- Recognised by many barcode validators out of the box.

### 4. QR token format

The QR code on the label does **NOT** encode the parcel number. It encodes a signed JWT:

```
Header:  { "alg": "HS256", "typ": "JWT" }
Payload: {
  "iss": "ccc",
  "sub": "<parcel uuid>",
  "pno": "CCC-20260601-000042-7",
  "iat": 1717180800,
  "exp": 1719772800,        // iat + 30 days
  "ver": 1
}
Signature: HMAC-SHA256( header.payload , QR_TOKEN_SECRET )
```

- Signed with `QR_TOKEN_SECRET` (separate from `APP_KEY` so QR tokens can be rotated independently).
- TTL: 30 days from issue (`QR_TOKEN_TTL_DAYS`). Long enough for slow-moving parcels; short enough that a leaked old label is not forever valid.
- `ver: 1` lets us evolve the payload without revoking all live tokens.

**Why JWT, not raw UUID:**
- Prevents enumeration: scanning a forged label with a guessed UUID returns 401 instantly.
- Tamper-evident: changing any field invalidates the signature.
- Self-contained: the scanner verifies offline once it has the public key (HS256 means shared secret, fine for our trust model since only our backend issues + verifies).
- Carries `pno` so a corrupt scan can fall back to manual parcel-number entry without an extra DB lookup.

**Why HS256, not RS256:**
- Only our backend issues and verifies. No third-party verifier needs to trust without seeing the secret.
- Smaller signature = denser QR (~150 bytes vs ~350 bytes).
- Faster CPU on lorry tablets.

If a future ops or partner integration needs to verify tokens without holding the secret, switch to RS256 then; payload is unchanged.

### 5. Service contract

```php
QrTokenService::sign(string $parcelUuid, string $parcelNumber): string;
QrTokenService::verify(string $jwt): array {
    // returns ['parcel_uuid' => ..., 'parcel_number' => ...]
    // throws QrTokenInvalidException on bad signature, expiry, malformed payload
}
```

Verification failures map to HTTP 401 with `error.code = 'INVALID_QR_TOKEN'` and `details.reason ∈ {expired, signature_mismatch, malformed, revoked}`.

### 6. Manual entry fallback

When the QR is damaged, the driver app accepts a typed parcel number. The flow:

1. Driver types `CCC-20260601-000042-7`.
2. App validates check digit locally → instant rejection of typos.
3. App POSTs `parcel_number` (NOT a fake JWT) to `/api/v1/driver/parcels/{parcel_number}/scan` with header `X-Scan-Mode: manual`.
4. Server validates check digit again, looks up parcel by number, proceeds as normal.
5. The `parcel_event` row records `scan_mode: 'manual'` for audit.

Manual scans are rate-limited (10/min/driver) to prevent enumeration brute-force.

---

## Alternatives considered

- **UUID v4 only.** Too long to dictate; no tamper detection on labels.
- **Short random tokens (e.g. 8 chars base32).** Collision-prone at scale; no human structure.
- **Sequential integer IDs.** Easy to enumerate, leaks daily volume to competitors.
- **QR encodes the parcel number directly.** Cheaper, but means a printed label is a bearer token forever. Rejected.
- **Encrypt parcel UUID instead of JWT.** Loses claims (pno, exp, ver); JWT gives all three for the same code complexity.

---

## Consequences

### Positive
- Two identifiers, two audiences. Each is optimised for its consumer.
- Forged or guessed labels fail fast at the API layer.
- Manual entry is genuinely manual-friendly (typo-rejecting).
- Tokens have a finite life; lost-then-found old labels are not exploitable indefinitely.

### Negative
- Two values to manage on the label (number printed in big text + QR encoding the JWT) — handled by the PDF template.
- `QR_TOKEN_SECRET` rotation requires a re-sign migration for in-flight parcels. Documented in the runbook.
- Per-day sequence counter is a single hot row at midnight Colombo time. Acceptable at MVP volume; partition by hour if it ever shows up in profiling.

### Follow-ups
- Phase 1: implement both services + 100% unit test coverage of `ParcelNumberService::generate` and `QrTokenService::sign/verify`.
- Phase 1: pricing tests assert that the parcel-number sequence resets across a date boundary (use `Carbon::setTestNow`).
- Phase 6: include `QR_TOKEN_SECRET` in the secrets-rotation runbook.
