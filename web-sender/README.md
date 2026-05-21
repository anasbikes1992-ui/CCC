# CCC Sender Portal

Next.js 16 sender-facing app for booking parcels against the Laravel API.

## Current Scope (Phase 2)

- Token-based customer booking form (`POST /api/v1/customer/parcels`)
- Recent parcel list (`GET /api/v1/customer/parcels`)
- Quick links to label PDF + public tracking URL

## Environment

Copy `.env.example` to `.env.local` and set:

```bash
NEXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/v1
```

## Run

```bash
npm install
npm run dev
```

Default local URL: `http://localhost:3000`

## Quality Checks

```bash
npm run lint
npm run build
```
