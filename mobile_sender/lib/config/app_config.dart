class AppConfig {
  // API Configuration
  static const String apiBaseUrl = 'https://ccc-production.up.railway.app';
  static const String apiVersion = 'v1';
  
  // Endpoints
  static const String loginEndpoint = '/api/$apiVersion/auth/login';
  static const String otpVerifyEndpoint = '/api/$apiVersion/auth/verify-otp';
  static const String routesEndpoint = '/api/$apiVersion/routes';
  static const String bookingEndpoint = '/api/$apiVersion/bookings';
  static const String parcelsEndpoint = '/api/$apiVersion/parcels';
  static const String profileEndpoint = '/api/$apiVersion/profile';
  
  // App Configuration
  static const String appName = 'CCC Sender';
  static const String supportPhone = '+94771234567';
  static const String supportEmail = 'support@ccc.lk';
  
  // Storage Keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String phoneKey = 'user_phone';
  
  // Timeouts
  static const Duration connectionTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);
}
