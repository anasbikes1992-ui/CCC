# CCC Public Tracking

Next.js 16 public tracker app for parcel lookup without authentication.

## Current Scope (Phase 2)

- Search page for parcel number
- Dynamic tracking route: `/[parcelNumber]`
- 30-second revalidation for timeline freshness
- Stage stepper and event timeline UI

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

Default local URL: `http://localhost:3001`

## Quality Checks

```bash
npm run lint
npm run build
```
