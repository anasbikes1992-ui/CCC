# CCC Driver App - Build Instructions

## Prerequisites
- Flutter SDK ^3.11.5
- Android Studio or VS Code with Flutter plugin
- JDK 17 or higher

## Production Configuration

The app is configured to use the production Railway backend:
- API URL: `https://ccc-production-30a5.up.railway.app/api/v1`
- Configuration file: `lib/config.dart`

## Dependencies

```bash
flutter pub get
```

Key dependencies:
- `provider` - State management
- `http` - API calls
- `shared_preferences` - Local storage
- `mobile_scanner` - QR/Barcode scanning
- `signature` - Digital signature capture
- `image_picker` - Photo capture

## Features

### ✅ Implemented
1. **Login** - Driver authentication with phone/password
2. **Dashboard** - View assigned trips
3. **Trip Details** - View parcels in trip
4. **QR/Barcode Scanning** - Scan parcels at each stage
5. **OTP Verification** - Verify receiver OTP before delivery
6. **Delivery Verification** - Capture:
   - Receiver name
   - Receiver NIC
   - Digital signature
   - Optional photo

### 🔄 Event Types
- `LOADED_ON_LORRY` - Scan when loading parcel on vehicle
- `ARRIVED_AT_DESTINATION_HUB` - Scan when reaching destination hub
- `OUT_FOR_DELIVERY` - Triggers OTP verification flow
- `DELIVERED` - Direct delivery (for hub-to-hub, no OTP)

## Building for Production

### Android APK

1. **Update version in `pubspec.yaml`**:
   ```yaml
   version: 1.0.0+1
   ```

2. **Build APK**:
   ```bash
   flutter build apk --release
   ```

3. **Output location**:
   ```
   build/app/outputs/flutter-apk/app-release.apk
   ```

### Android App Bundle (for Play Store)

```bash
flutter build appbundle --release
```

Output: `build/app/outputs/bundle/release/app-release.aab`

## Testing

### Test Credentials
Use test driver account from seeders:
- **Phone**: +94777777003
- **Email**: driver@test.com
- **Password**: password

### Test Flow
1. Login with test driver credentials
2. View trips (should see test trip if seeded)
3. Scan parcel QR code
4. For parcels at ARRIVED_AT_DESTINATION_HUB:
   - Generate OTP
   - Ask receiver for OTP (sent via WhatsApp)
   - Verify OTP
   - Capture signature + photo
   - Complete delivery

### Test Parcel
Parcel #6 from TestDataSeeder is at `ARRIVED_AT_DESTINATION_HUB` status:
- Parcel Number: `CCC-20260523-000006-X` (check actual from seeder output)
- Receiver Phone: +9477XXXXXXX (check actual from seeder)

## Troubleshooting

### Build Errors

**Gradle issues:**
```bash
cd android
./gradlew clean
cd ..
flutter clean
flutter pub get
flutter build apk --release
```

**SDK version errors:**
Update `android/app/build.gradle`:
```gradle
android {
    compileSdkVersion 34
    
    defaultConfig {
        minSdkVersion 21
        targetSdkVersion 34
    }
}
```

### Runtime Issues

**Network errors:**
- Ensure device has internet connection
- Verify Railway backend is accessible
- Check API URL in `lib/config.dart`

**Camera permission denied:**
- Grant camera permission in device settings
- Required for QR scanning and photo capture

**Storage permission denied:**
- Grant storage permission for saving photos
- Required for image picker

## App Permissions

Declared in `android/app/src/main/AndroidManifest.xml`:
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
```

## Installation

### Via APK
1. Transfer `app-release.apk` to Android device
2. Enable "Install from Unknown Sources" in device settings
3. Open APK file and install

### Via ADB
```bash
adb install build/app/outputs/flutter-apk/app-release.apk
```

## Monitoring

### Logs
Enable Flutter logging to monitor API calls:
```bash
flutter run --release
# View logs in console
```

### Debugging
For development builds with debugging:
```bash
flutter run --debug
```

## Security Notes

- All API calls use Bearer token authentication
- NIC data is encrypted at rest in backend
- Signature images are validated for minimum size (5KB)
- QR tokens are JWT-signed to prevent tampering

## Next Steps

- [ ] Build APK
- [ ] Test on physical device
- [ ] Test complete delivery flow with real QR codes
- [ ] Verify OTP generation and WhatsApp notifications
- [ ] Submit to Google Play Store (optional)

## Support

For issues or questions:
- Check Railway logs: `railway logs --deployment`
- Review backend API documentation: `docs/API_SPEC.md`
- Test API endpoints with Postman/Insomnia

---

**Last Updated**: May 23, 2026  
**Version**: 1.0.0  
**Backend**: Railway (https://ccc-production-30a5.up.railway.app)
