class AppSecrets {
  // IMPORTANT: In production, these should come from environment variables
  // or secure configuration management (e.g., Firebase Remote Config)
  
  // JWT Configuration
  static const String jwtSecret = 'REPLACE_WITH_YOUR_JWT_SECRET_FROM_BACKEND';
  static const String jwtAlgorithm = 'HS256';
  static const int jwtTTL = 3600; // 1 hour in seconds
  
  // API Keys (if needed for specific integrations)
  static const String supabaseAnonKey = 'REPLACE_WITH_SUPABASE_ANON_KEY';
  static const String supabaseUrl = 'REPLACE_WITH_SUPABASE_URL';
  
  // Firebase (for push notifications)
  static const String firebaseApiKey = 'REPLACE_WITH_FIREBASE_API_KEY';
  static const String firebaseProjectId = 'REPLACE_WITH_FIREBASE_PROJECT_ID';
  
  // Sentry DSN (for crash reporting)
  static const String sentryDsn = 'REPLACE_WITH_SENTRY_DSN';
  
  // Encryption Key (for local sensitive data)
  static const String localEncryptionKey = 'REPLACE_WITH_32_CHAR_KEY';
  
  // Feature Flags
  static const bool debugMode = false; // Set to false in production
  static const bool verboseLogging = false; // Set to false in production
}
