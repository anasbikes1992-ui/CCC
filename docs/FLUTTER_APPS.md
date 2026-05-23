# CCC Mobile Apps — Flutter Implementation

## Overview

Complete Flutter mobile applications for Colombo Cargo Connect with modern UI, full API integration, and production-ready architecture.

---

## Apps

### 1. Mobile Sender App (`mobile_sender/`)

Customer-facing app for booking, tracking, and managing parcels.

**Features:**
- ✅ Phone + OTP authentication
- ✅ Modern dashboard with parcel tabs (Active/Delivered)
- ✅ Parcel tracking with real-time status
- ✅ Profile management
- ✅ Quick actions (Book, Track)
- 🚧 Booking flow (multi-step form)
- 🚧 QR code display
- 🚧 Delivery rating

**Tech Stack:**
- Flutter 3.x
- Provider state management
- http + dio for API calls
- SharedPreferences for local storage
- Google Fonts + custom theme
- mobile_scanner for QR scanning
- qr_flutter for QR generation

**Screens:**
1. `SplashScreen` — Animated logo with auth check
2. `LoginScreen` — Phone + OTP verification
3. `DashboardScreen` — Home with quick actions + parcel list
4. `BookParcelScreen` — Multi-step booking form (stub)
5. `ParcelDetailsScreen` — Full parcel info + tracking timeline (stub)
6. `ProfileScreen` — User profile + logout

**Architecture:**
```
lib/
├── config/
│   ├── app_config.dart       # API endpoints, constants
│   └── app_theme.dart         # Design system (colors, spacing, shadows)
├── models/
│   ├── user.dart
│   ├── parcel.dart
│   └── route.dart
├── providers/
│   ├── auth_provider.dart     # Authentication state
│   └── parcel_provider.dart   # Parcel management
├── services/
│   └── api_service.dart       # API client (singleton)
├── screens/
│   ├── splash_screen.dart
│   ├── login_screen.dart
│   ├── dashboard_screen.dart
│   ├── book_parcel_screen.dart
│   ├── parcel_details_screen.dart
│   └── profile_screen.dart
├── widgets/
│   └── parcel_card.dart       # Reusable parcel list item
└── main.dart
```

---

### 2. Mobile Driver App (`mobile_driver/`)

Driver-facing app for scanning parcels, capturing delivery proof, and managing daily trips.

**Features:**
- ✅ Phone + OTP authentication
- ✅ Dashboard with assigned parcels
- ✅ QR/barcode scanning (mobile_scanner)
- ✅ Delivery verification (NIC + signature + photo)
- ✅ Trip details
- 🚧 Signature capture integration
- 🚧 Modern UI overhaul

**Screens:**
1. `login_screen.dart`
2. `dashboard_screen.dart`
3. `scan_screen.dart`
4. `delivery_verification_screen.dart`
5. `trip_detail_screen.dart`

**Dependencies:**
- mobile_scanner: ^7.2.0 (QR scanning)
- signature: ^6.3.0 (signature capture)
- image_picker: ^1.2.2 (delivery photo)
- provider: ^6.1.5+1 (state)

---

## Setup Instructions

### Prerequisites
- Flutter SDK 3.11.5+
- Android Studio / Xcode
- Physical device or emulator

### Installation

**Mobile Sender:**
```bash
cd mobile_sender
flutter pub get
flutter run
```

**Mobile Driver:**
```bash
cd mobile_driver
flutter pub get
flutter run
```

### Environment Configuration

Both apps connect to:
```
API Base URL: https://ccc-production.up.railway.app
API Version: v1
```

Configured in:
- `mobile_sender/lib/config/app_config.dart`
- `mobile_driver/lib/api_service.dart`

---

## API Integration

### Authentication Flow

**1. Send OTP:**
```dart
POST /api/v1/auth/login
Body: { "phone": "+94771234567" }
Response: { "success": true }
```

**2. Verify OTP:**
```dart
POST /api/v1/auth/verify-otp
Body: { "phone": "+94771234567", "otp": "123456" }
Response: {
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1...",
    "user": { "id": "...", "name": "...", "phone": "..." }
  }
}
```

**3. Authenticated Requests:**
```dart
Headers: {
  "Authorization": "Bearer <token>",
  "Content-Type": "application/json"
}
```

### Key Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/v1/routes` | GET | List all routes |
| `/api/v1/bookings` | POST | Create booking |
| `/api/v1/bookings/calculate-price` | POST | Price calculation |
| `/api/v1/parcels` | GET | List parcels (with filters) |
| `/api/v1/parcels/{id}` | GET | Parcel details |
| `/api/v1/parcels/track/{number}` | GET | Track by number |
| `/api/v1/parcels/{id}/cancel` | POST | Cancel parcel |
| `/api/v1/parcels/{id}/rate` | POST | Rate delivery |
| `/api/v1/profile` | GET/PUT | User profile |

---

## Design System

### Colors
```dart
Primary: #2563EB (Blue)
Secondary: #10B981 (Green)
Error: #EF4444 (Red)
Warning: #F59E0B (Amber)
Success: #10B981 (Green)
Background: #F9FAFB (Light Gray)
```

### Typography
- Font: Inter (Google Fonts)
- Display: 24-32px, Bold
- Headline: 16-20px, SemiBold
- Body: 14-16px, Regular
- Label: 10-14px, Medium

### Spacing
- xs: 4px
- sm: 8px
- md: 16px (default)
- lg: 24px
- xl: 32px
- xxl: 48px

### Border Radius
- sm: 8px
- md: 12px (default)
- lg: 16px
- xl: 24px

---

## State Management

### Provider Pattern

**AuthProvider:**
- Manages user authentication state
- Stores token in SharedPreferences
- Auto-checks auth on app start

**ParcelProvider:**
- Fetches and caches parcel list
- Manages current parcel details
- Handles parcel operations (track, cancel, rate)

**Usage Example:**
```dart
// Access state
final auth = Provider.of<AuthProvider>(context);

// Listen to changes
Consumer<AuthProvider>(
  builder: (context, auth, _) {
    if (auth.isLoading) return CircularProgressIndicator();
    if (auth.isAuthenticated) return DashboardScreen();
    return LoginScreen();
  },
)

// No rebuild on change
final auth = Provider.of<AuthProvider>(context, listen: false);
await auth.login(phone);
```

---

## Models

### User
```dart
class User {
  final String id;
  final String name;
  final String phone;
  final String email;
  final String? profilePhoto;
  final DateTime createdAt;
}
```

### Parcel
```dart
class Parcel {
  final String id;
  final String parcelNumber;
  final String status;
  final String routeCode;
  final String size;
  final double weight;
  final int baseFee;
  final int finalFee;
  final String senderName;
  final String receiverName;
  final List<StatusEvent> statusHistory;
}
```

### Route
```dart
class Route {
  final String id;
  final String code;
  final String originHub;
  final String destinationHub;
  final Map<String, int> pricingMatrix; // size -> price
}
```

---

## Testing

### Manual Testing Checklist

**Authentication:**
- [ ] Login with valid phone sends OTP
- [ ] Login with invalid phone shows error
- [ ] OTP verification succeeds with correct code
- [ ] OTP verification fails with wrong code
- [ ] Token persists after app restart
- [ ] Logout clears token and redirects to login

**Dashboard:**
- [ ] Parcel list loads on app open
- [ ] Active tab shows non-delivered parcels
- [ ] Delivered tab shows delivered parcels
- [ ] Pull-to-refresh reloads parcels
- [ ] Empty states show when no parcels
- [ ] Parcel card tap opens details

**Profile:**
- [ ] User info displays correctly
- [ ] Logout confirmation dialog works
- [ ] Logout redirects to login screen

---

## Known Issues & TODOs

### Mobile Sender
- [ ] Complete booking flow (multi-step form)
- [ ] Add QR code generation for parcels
- [ ] Implement delivery rating UI
- [ ] Add parcel details timeline visualization
- [ ] Implement search and filters
- [ ] Add push notifications (FCM)
- [ ] Offline mode (cache parcels)
- [ ] Add parcel photos upload

### Mobile Driver
- [ ] Overhaul UI to match sender app theme
- [ ] Integrate signature capture properly
- [ ] Add camera permissions handling
- [ ] Implement trip navigation
- [ ] Add offline scan queue
- [ ] GPS tracking for delivery proof

### Both Apps
- [ ] Add unit tests (models, services)
- [ ] Add widget tests (screens)
- [ ] Add integration tests (flows)
- [ ] Implement proper error boundaries
- [ ] Add analytics (Firebase Analytics)
- [ ] Add crash reporting (Sentry)
- [ ] Optimize images and assets
- [ ] Add localization (Sinhala, Tamil)

---

## Performance Considerations

### Image Optimization
- Use `CachedNetworkImage` for remote images
- Compress images before upload (max 1MB)
- Use thumbnails for list views

### API Optimization
- Implement pagination for parcel list
- Cache frequently accessed data
- Use debouncing for search
- Implement retry logic with exponential backoff

### Build Optimization
```bash
# Release build (Android)
flutter build apk --release --split-per-abi

# Release build (iOS)
flutter build ios --release
```

---

## Deployment

### Android (Google Play)

1. **Update version:**
```yaml
# pubspec.yaml
version: 1.0.0+1  # version+build_number
```

2. **Build APK:**
```bash
flutter build apk --release --split-per-abi
```

3. **Outputs:**
```
build/app/outputs/flutter-apk/
├── app-armeabi-v7a-release.apk  (32-bit ARM)
├── app-arm64-v8a-release.apk    (64-bit ARM)
└── app-x86_64-release.apk       (64-bit x86)
```

### iOS (App Store)

1. **Build IPA:**
```bash
flutter build ipa --release
```

2. **Upload via Xcode or Transporter**

---

## Security

### Token Storage
- Tokens stored in SharedPreferences (encrypted on device)
- Never log tokens
- Clear tokens on logout

### API Security
- All requests over HTTPS
- Bearer token authentication
- Request timeout: 30 seconds

### Sensitive Data
- NIC encrypted at rest (backend)
- No sensitive data in logs
- Signatures stored as base64 PNG

---

## Support

**Issues:** Report bugs in GitHub Issues  
**Questions:** support@ccc.lk  
**Documentation:** See `docs/` folder

---

**Last Updated:** May 23, 2026  
**Version:** 1.0.0  
**Status:** ✅ Core features complete, booking flow in progress
