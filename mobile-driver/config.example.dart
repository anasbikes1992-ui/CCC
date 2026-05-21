// =============================================================================
// Colombo Cargo Connect — Driver App (Flutter) configuration template
// =============================================================================
// Copy this file to mobile-driver/lib/config.dart and fill in real values.
// lib/config.dart is gitignored. Never commit production secrets.
//
// Flutter doesn't load .env files natively; we use a Dart constants file.

class AppConfig {
  // --- API ---
  static const String apiBaseUrl = 'http://10.0.2.2:8000/api/v1';
  // 10.0.2.2 reaches the host machine from the Android emulator.
  // For iOS simulator use http://localhost:8000/api/v1
  // For a physical device use http://<your-LAN-ip>:8000/api/v1

  // --- Sentry ---
  static const String sentryDsn = '';

  // --- Firebase Cloud Messaging ---
  // FCM is configured via google-services.json (Android) / GoogleService-Info.plist (iOS)
  // — those files are gitignored.

  // --- Build flavor ---
  static const String flavor = 'local'; // local | staging | production

  // --- Offline scan buffer ---
  static const int scanRetryIntervalSeconds = 30;
  static const int scanMaxRetries = 10;
}
