# Architecture — Colombo Cargo Connect

**Version:** 1.0  
**Last Updated:** May 1, 2026  
**Status:** Foundation Phase

---

## System Overview

```mermaid
flowchart LR
    subgraph Clients["🖥️ Client Layer"]
        SW["Sender Web<br/>(Next.js)"]
        SA["Sender App<br/>(Flutter)"]
        DA["Driver App<br/>(Flutter)"]
        HW["Hub Console<br/>(Next.js)"]
        AW["Admin Portal<br/>(Next.js)"]
        TR["Tracking Page<br/>(Next.js)"]
    end

    subgraph Edge["🛡️ Edge Layer"]
        CDN["CDN<br/>(Vercel)"]
        WAF["WAF<br/>Rate Limit"]
    end

    subgraph Backend["🔧 Backend Layer"]
        API["Laravel 11 API<br/>(RESTful)"]
        Q["Queue Workers<br/>(Redis)"]
        CR["Cron Jobs<br/>(Trip Gen)"]
    end

    subgraph Data["💾 Data Layer"]
        PG["PostgreSQL 16<br/>+ PostGIS"]
        RD["Redis<br/>(Cache+Queue)"]
        SB["Supabase Storage<br/>(Files)"]
    end

    subgraph External["🌐 External Services"]
        WA["WhatsApp<br/>Cloud API"]
        SMS["Notify.lk<br/>(SMS)"]
        FCM["Firebase<br/>(Push)"]
        WX["WebxPay<br/>(Payments)"]
        GM["Google Maps<br/>(Display)"]
        OS["OSRM<br/>(Routing)"]
    end

    SW --> CDN
    SA --> CDN
    DA --> CDN
    HW --> CDN
    AW --> CDN
    TR --> CDN
    
    CDN --> WAF
    WAF --> API
    
    API <--> PG
    API <--> RD
    API <--> SB
    
    Q --> WA
    Q --> SMS
    Q --> FCM
    Q --> WX
    
    CR --> Q
    API --> Q
    
    API --> GM
    API --> OS
```

---

## Layers & Responsibilities

### 1. Client Layer

| Component | Role | Users | Key Features |
|-----------|------|-------|-------------|
| **Sender Web** | Customer portal | Senders (shops, SMEs, agri) | Book parcel, track, pay, rate, dispute |
| **Sender App** | Mobile companion | Senders | Lightweight booking, notifications, tracking |
| **Driver App** | Field operations | Drivers | Scan QR, pick up, deliver, capture proof |
| **Hub Console** | Hub operations | Hub staff | Scan IN/OUT, manage inventory, print labels |
| **Admin Portal** | Central control | Ops, Finance, Support, Super | Trip management, pricing, users, disputes |
| **Tracking Page** | Public interface | Anyone with link | Real-time status, no login required |

### 2. Edge Layer (CDN + Security)

- **CDN (Vercel):** Static assets, images, JS bundles cached globally
- **WAF:** DDoS protection, rate limiting (100 req/min per IP for auth endpoints)
- **HTTPS:** All endpoints, HSTS enforced

### 3. Backend API Layer (Laravel 11)

Core business logic runs here.

#### Architecture Pattern: **Layered + Service**

```
Controllers (HTTP)
    ↓
Services (Business Logic)
    ↓
Models + Repositories (Data Access)
    ↓
Database
```

#### Key Services

| Service | Responsibility | Usage |
|---------|---|---|
| **PricingService** | Calculate final price from route + size + surcharges | When booking, generating invoices |
| **ParcelNumberService** | Generate unique CCC-YYYYMMDD-NNNNNN-X | On parcel creation |
| **QrTokenService** | Create/verify signed JWT for QR codes | On booking, on scan |
| **TripAssignmentService** | Auto-assign parcel to next available trip | When customer chooses auto-assign |
| **ScanService** | Validate status transition, log event, notify | On every QR scan |
| **WhatsAppService** | Send templated WhatsApp messages (queued) | On parcel status change |
| **PaymentService** | Create payment intent, verify webhook | When customer checks out |
| **NotificationService** | Dispatch notifications (WhatsApp, SMS, email, push) | From queue workers |

#### Routing Structure

```
/api/v1/
├── /auth/
│   ├── POST /register
│   ├── POST /login
│   ├── POST /logout
│   └── POST /refresh
│
├── /customer/
│   ├── POST /parcels (create booking)
│   ├── GET /parcels (list my parcels)
│   ├── GET /parcels/{id}
│   └── POST /parcels/{id}/rate
│
├── /driver/
│   ├── GET /trips (current + upcoming)
│   ├── GET /trips/{id}/parcels
│   ├── POST /parcels/{id}/scan
│   ├── POST /parcels/{id}/deliver
│   └── GET /dashboard
│
├── /hub/
│   ├── POST /parcels/{id}/scan-in
│   ├── POST /parcels/{id}/scan-out
│   └── GET /manifests
│
├── /admin/
│   ├── /trips (CRUD)
│   ├── /pricing-matrix (CRUD)
│   ├── /users (CRUD, KYC)
│   ├── /hubs (CRUD)
│   ├── /lorries (CRUD)
│   ├── /drivers (CRUD)
│   └── /disputes (list, resolve)
│
└── /public/
    ├── GET /parcels/{parcel_number}/track
    └── GET /hubs (list all hubs)
```

### 4. Data Layer

#### PostgreSQL 16 + PostGIS

**Core Tables:**

| Table | Purpose | Rows/Month (Estimate) |
|-------|---------|-------|
| `users` | Customers, drivers, staff, admins | +500 new users |
| `parcels` | Booking records | +50,000 new parcels |
| `parcel_events` | Scan events (10 per parcel avg) | +500,000 new events |
| `trips` | Scheduled runs (5–10 per day per route) | +150–300 new trips |
| `pricing_matrix` | Route × size lookup | Static (updates rare) |
| `notifications_log` | WhatsApp, SMS, email logs | +150,000 notification sends |
| `payments` | Payment records | +25,000 new payments |
| `delivery_proofs` | NIC + signature + photo | +25,000 (same as delivered parcels) |

**Indexes:** See `COLOMBO_CARGO_CONNECT_PLAN.md` Section 12.

#### Redis (Cache + Queue)

| Use | TTL/Persistence |
|-----|---|
| Session store | 2 weeks (Laravel Sanctum) |
| Cache (pricing, hubs, routes) | 1 hour |
| Job queue (WhatsApp, SMS, payments) | Persistent (FIFO) |
| Rate limiting | 1 minute (sliding window) |
| Real-time trip capacity | 30 seconds |

#### Supabase Storage

| Asset Type | Format | Retention |
|-----------|--------|-----------|
| Parcel labels | PDF (A4, 4×6 labels) | 1 year |
| Delivery proofs (signatures) | PNG (transparent bg, ~300×100) | 1 year |
| Delivery proofs (photos) | JPG/PNG (~500 KB) | 1 year |
| KYC documents | PDF/JPG (~2 MB) | 5 years |
| Lorry photos | JPG (~1 MB) | Indefinite |

---

## Data Flow: Booking to Delivery

### Scenario: Customer books parcel (CMB → KDY, Medium size, doorstep pickup)

```mermaid
sequenceDiagram
    Customer->>SenderWeb: Click "Book Now"
    SenderWeb->>API: POST /api/v1/customer/parcels (route, size, pickup, drop, ...)
    API->>ParcelNumberService: Generate parcel number
    ParcelNumberService->>API: "CCC-20251101-004572-7"
    API->>PricingService: Calculate price (base + doorstep surcharge + ...)
    PricingService->>API: 1050 LKR
    API->>TripAssignmentService: Find next available trip (CMB→KDY, capacity check)
    TripAssignmentService->>DB: Query trips WHERE route='CMB-KDY' AND capacity_remaining >= 4
    DB->>TripAssignmentService: Trip #4451 (capacity remaining: 10 units)
    TripAssignmentService->>API: Assign to trip #4451
    API->>QrTokenService: Create signed JWT for QR
    QrTokenService->>API: Signed token
    API->>DB: INSERT parcel (number, trip_id, status=BOOKED, qr_token, price, ...)
    API->>DB: UPDATE trips.capacity_remaining = 10 - 4 = 6
    DB->>API: success
    API->>RedisCache: Cache trip #4451 (capacity now 6)
    API->>SenderWeb: 200 OK (parcel details + label + tracking link)
    
    SenderWeb->>Customer: Show parcel number + QR + barcode + "Download label"
    Customer->>Browser: Download label PDF
    
    Note over API: Async: Queue WhatsApp notification
    API->>Queue: Job: send_whatsapp_template(parcel_id, template='booking_confirmed')
    Queue->>WhatsAppService: Send "Your parcel CCC-20251101-004572-7 booked..."
    WhatsAppService->>WhatsApp: POST /api/message (Cloud API)
    WhatsApp->>Customer: ✅ Delivery Mon 2 PM [Sent to phone]
```

### Scenario: Driver picks up parcel

```mermaid
sequenceDiagram
    Driver->>DriverApp: Open app, tap "Scan QR"
    DriverApp->>Camera: Request camera
    Camera->>Driver: Live preview
    Driver->>Camera: Hold QR in frame
    DriverApp->>Scanner: Decode QR → JWT token
    Scanner->>DriverApp: Token decoded
    DriverApp->>API: POST /api/v1/driver/parcels/scan (token, event_type='PICKED_UP', lat, lng, device)
    API->>QrTokenService: Verify JWT signature
    QrTokenService->>API: ✓ Valid (parcel_id=abc-123)
    API->>ScanService: Validate transition (BOOKED → PICKED_UP allowed? YES)
    ScanService->>DB: INSERT parcel_event (event_type='PICKED_UP', ...)
    ScanService->>DB: UPDATE parcels.status = 'PICKED_UP'
    DB->>API: success
    API->>Queue: Job: send_whatsapp_template(parcel_id, template='parcel_picked_up')
    API->>DriverApp: 200 OK (parcel details, next stop)
    DriverApp->>Driver: ✅ Scanned! Next: CCC-20251101-004573-X
    
    Note over Queue: Background job sends WhatsApp
    Queue->>WhatsAppService: Send "Parcel picked up at..."
    WhatsAppService->>WhatsApp: Cloud API POST
    WhatsApp->>Sender: 📦 Parcel picked up (WhatsApp message)
```

### Scenario: Driver delivers parcel

```mermaid
sequenceDiagram
    Driver->>Receiver: Arrive at delivery address
    Receiver->>DriverApp: Present for delivery
    Driver->>DriverApp: Tap "Mark Delivered"
    DriverApp->>Receiver: Show NIC entry + signature pad
    Receiver->>DriverApp: Enter NIC (old: 9V, new: 12 digits)
    Receiver->>DriverApp: Sign with finger on screen
    Driver->>DriverApp: Optional: Snap photo of parcel
    Driver->>DriverApp: Tap "Complete"
    DriverApp->>Camera: Capture signature PNG + photo
    DriverApp->>API: POST /api/v1/driver/parcels/{id}/deliver (NIC, signature_b64, photo_b64, lat, lng)
    API->>Validation: NIC format OK? Signature > 5 KB? Photo < 5 MB?
    Validation->>API: ✓ All valid
    API->>Crypto: Encrypt NIC with Laravel::Crypt
    Crypto->>API: encrypted_nic_value
    API->>Supabase: Upload signature PNG + photo JPG
    Supabase->>API: signature_url, photo_url
    API->>DB: INSERT delivery_proof (parcel_id, receiver_nic_encrypted, signature_url, photo_url, ...)
    API->>DB: UPDATE parcels.status = 'DELIVERED'
    DB->>API: success
    API->>Queue: Job: send_whatsapp_template(parcel_id, template='delivered')
    API->>DriverApp: 200 OK (receipt, next action)
    DriverApp->>Driver: ✅ Delivered! Receipt #XYZ
    
    Note over Queue: Background: Send WhatsApp to sender + receiver
    Queue->>WhatsAppService: Send "Parcel delivered to Kasun P. at 3:15 PM"
    WhatsAppService->>WhatsApp: Cloud API POST (sender)
    WhatsApp->>Sender: ✅ Parcel delivered (WhatsApp)
    WhatsAppService->>WhatsApp: Cloud API POST (receiver via parcel)
    WhatsApp->>Receiver: ✅ Thank you for using CCC (WhatsApp)
```

---

## Authentication & Authorization

### Auth Flow: Sanctum (Laravel)

```mermaid
sequenceDiagram
    Client->>API: POST /api/v1/auth/login (phone, password)
    API->>DB: SELECT user WHERE phone=? AND deleted_at IS NULL
    DB->>API: user found
    API->>Crypto: Hash::check(password, user.password_hash)?
    Crypto->>API: ✓ Match
    API->>Sanctum: Generate API token
    Sanctum->>API: token (long random string, 80 chars)
    API->>DB: INSERT personal_access_tokens (user_id, token_hash, ...)
    API->>Client: 200 OK { token, user: { id, phone, role, ... } }
    Client->>Storage: Save token (localStorage / Keychain)
    
    Client->>API: GET /api/v1/customer/parcels (headers: Authorization: Bearer {token})
    API->>Middleware: Authenticate request (Sanctum)
    Middleware->>DB: SELECT user WHERE token_hash=? AND revoked=false
    DB->>Middleware: user found (role=customer)
    Middleware->>API: ✓ Authenticated + Authorized
    API->>DB: SELECT parcels WHERE customer_id=?
    API->>Client: 200 OK { parcels: [...] }
```

### Authorization: Spatie Laravel Permission

**Roles:**
- `customer`
- `driver`
- `hub_staff`
- `hub_manager`
- `admin_ops`
- `admin_finance`
- `admin_support`
- `admin_super`

**Usage in controller:**
```php
// Only ops admins can create trips
$this->authorize('create', Trip::class);  // Uses Gate + Policy

// OR middleware
Route::post('/admin/trips', [TripsController::class, 'store'])
    ->middleware('role:admin_ops|admin_super');
```

---

## Scaling Considerations (Future)

### Phase 1 (MVP, <10k parcels/month)
- Single Laravel instance (DigitalOcean App Platform)
- Single PostgreSQL (managed, automated backups)
- Redis (managed, single instance)
- Supabase (managed)

### Phase 2 (Growth, 50k–100k parcels/month)
- Laravel load balanced (2–3 instances)
- PostgreSQL read replicas + connection pooling
- Redis sentinel (HA)
- Dedicated queue workers (separate containers)
- API caching layer (Varnish / Nginx)

### Phase 3 (Scale, 500k+ parcels/month)
- Regional Laravel instances (Colombo + Kandy hubs)
- PostgreSQL sharding by region
- Multi-region Redis
- Event sourcing for audit trail
- Message bus (RabbitMQ / Kafka) for event streaming

---

## Security Architecture

### Data Protection

1. **Encryption at Rest**
   - NIC: `Crypt::encryptString()` in Laravel (AES-256)
   - Passwords: bcrypt (Laravel default)
   - Sensitive config: `.env` + never commit

2. **Encryption in Transit**
   - HTTPS/TLS 1.3 everywhere
   - HSTS header (1 year)
   - Subresource Integrity (SRI) for CDN assets

3. **Access Control**
   - Sanctum API tokens (bearer auth)
   - Role-based authorization (Spatie)
   - Policy classes for resource-level checks

### API Security

1. **Rate Limiting**
   - Auth endpoints: 5 req/min per IP
   - Public endpoints: 100 req/min per IP
   - Customer endpoints: 1000 req/hour per user
   - Implemented via Redis + middleware

2. **Input Validation**
   - All POST/PATCH via Form Request classes
   - Phone: E.164 format
   - NIC: 9+V or 12 digits
   - Price: positive decimal
   - Never trust user input

3. **CORS**
   - Sender web: `https://sender.cargo.lk`
   - Admin web: `https://admin.cargo.lk`
   - Tracking page: `*` (public)
   - API: All requests must have Origin header

---

## Monitoring & Observability

### Logging
- **Application logs:** `/storage/logs/laravel.log`
- **Access logs:** Nginx/Apache access logs
- **Job queue:** Failed jobs logged to DB + Sentry

### Metrics (Better Stack)
- Uptime monitoring (ping API every 60 sec)
- Error rate alert (> 5% in 5 min)
- Database query time alert (> 1 sec)
- Redis memory alert (> 80%)

### Error Tracking (Sentry)
- All unhandled exceptions logged
- JavaScript errors from web + mobile
- Custom breadcrumbs for user actions
- Alerts on new issue types

### Performance
- Lighthouse score: target >85
- API p95 response time: < 500 ms
- Database query p95: < 100 ms
- FCP (First Contentful Paint): < 1.5 sec

---

## Deployment Architecture

### API (DigitalOcean App Platform)

```
Repo: GitHub ccc/backend
Branch: main
CI/CD: GitHub Actions
  → Run tests (Laravel Pest)
  → Build Docker image
  → Push to DO Container Registry
  → Deploy to App Platform

Live URL: api.cargo.lk
Health: /api/health (200 OK)
```

### Web (Vercel)

```
Repo: GitHub ccc/web-*
Branch: main
CI/CD: Vercel auto-deploy
  → Run build (next build)
  → Run tests
  → Deploy to global CDN

Live URLs:
  - sender.cargo.lk
  - admin.cargo.lk
  - hub.cargo.lk
  - track.cargo.lk
```

### Mobile (App Stores)

```
Repo: GitHub ccc/mobile-*
Branch: main (release tag)
CI/CD: GitHub Actions
  → Run Flutter tests
  → Build APK + AAB (Android)
  → Build IPA (iOS)
  → Push to Google Play + App Store
```

---

## Development Environment

**Recommended Setup:**
- Windows 10/11 with WSL2 (optional, for Linux CLI tools)
- VS Code + Remote Containers (optional)
- Or: Native Windows PHP + PostgreSQL + Redis

**Project structure:** See DEVELOPMENT_TRACKER.md

---

**Last Updated:** May 1, 2026  
**Status:** Foundation Phase  
**Next Review:** After Sprint 3
