class AppConfig {
  static const String apiBaseUrl = 'https://ccc-production-30a5.up.railway.app/api/v1';
  static const bool isProduction = true;
  static const String appName = 'CCC Sender';
  static const String appVersion = '1.0.0';
  
  // Payment Gateway (WebxPay)
  static const String paymentGatewayUrl = 'https://webxpay.com/checkout';
  
  // App Configuration
  static const int apiTimeoutSeconds = 30;
  static const int maxImageSizeMB = 5;
}
