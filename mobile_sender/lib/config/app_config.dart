class AppConfig {
  // API Configuration - PRODUCTION
  static const String apiBaseUrl = 'https://ccc-production.up.railway.app';
  static const String apiVersion = 'v1';
  
  // Web URLs
  static const String webSenderUrl = 'https://web-sender.vercel.app';
  static const String webTrackingUrl = 'https://web-tracking.vercel.app';
  static const String webAdminUrl = 'https://web-admin.vercel.app';

  // Endpoints
  static const String loginEndpoint = '/api/$apiVersion/auth/login';
  static const String otpVerifyEndpoint = '/api/$apiVersion/auth/verify-otp';
  static const String routesEndpoint = '/api/$apiVersion/routes';
  static const String bookingEndpoint = '/api/$apiVersion/customer/parcels';
  static const String calculatePriceEndpoint = '/api/$apiVersion/customer/parcels/calculate-price';
  static const String parcelsEndpoint = '/api/$apiVersion/customer/parcels';
  static const String profileEndpoint = '/api/$apiVersion/auth/me';
  static const String trackEndpoint = '/api/$apiVersion/public/parcels';

  // App Configuration
  static const String appName = 'CCC Sender';
  static const String appVersion = '1.0.0';
  static const String supportPhone = '+94771234567';
  static const String supportEmail = 'support@ccc.lk';
  static const String supportWhatsApp = '+94771234567';

  // Storage Keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String phoneKey = 'user_phone';
  static const String fcmTokenKey = 'fcm_token';

  // Timeouts
  static const Duration connectionTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);
  
  // Pagination
  static const int defaultPageSize = 20;
  static const int maxPageSize = 100;
  
  // Cache Duration
  static const Duration cacheRoutesFor = Duration(hours: 24);
  static const Duration cacheParcelsFor = Duration(minutes: 5);
  
  // Features
  static const bool enablePushNotifications = true;
  static const bool enableBiometric = true;
  static const bool enableOfflineMode = true;
  static const bool enableCrashReporting = true;
}
