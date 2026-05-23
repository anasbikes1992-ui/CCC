import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';

class DriverApiService {
  static const String _tokenKey = 'driver_token';
  
  Future<String?> _getAuthToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<Map<String, String>> _getHeaders() async {
    final token = await _getAuthToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  /// Verify pickup OTP provided by receiver
  Future<Map<String, dynamic>> verifyPickupOTP({
    required String parcelId,
    required String otp,
  }) async {
    final url = Uri.parse('${AppConfig.apiBaseUrl}/api/v1/driver/delivery/verify-otp');
    final headers = await _getHeaders();
    
    final response = await http.post(
      url,
      headers: headers,
      body: jsonEncode({
        'parcel_id': parcelId,
        'otp': otp,
      }),
    );

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  /// Regenerate OTP if expired or max attempts reached
  Future<Map<String, dynamic>> regeneratePickupOTP({
    required String parcelId,
  }) async {
    final url = Uri.parse('${AppConfig.apiBaseUrl}/api/v1/driver/delivery/regenerate-otp');
    final headers = await _getHeaders();
    
    final response = await http.post(
      url,
      headers: headers,
      body: jsonEncode({
        'parcel_id': parcelId,
      }),
    );

    if (response.statusCode != 200) {
      final error = jsonDecode(response.body);
      throw Exception(error['error']['message'] ?? 'Failed to regenerate OTP');
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  /// Complete delivery with OTP verification + signature + photo
  Future<Map<String, dynamic>> completeDelivery({
    required String parcelId,
    required String otp,
    required String receiverNic,
    required String signatureBase64,
    String? photoBase64,
    String? deliveryNotes,
    double? latitude,
    double? longitude,
    String? deviceId,
  }) async {
    final url = Uri.parse('${AppConfig.apiBaseUrl}/api/v1/driver/delivery/complete');
    final headers = await _getHeaders();
    
    if (deviceId != null) {
      headers['X-Device-ID'] = deviceId;
    }
    
    final response = await http.post(
      url,
      headers: headers,
      body: jsonEncode({
        'parcel_id': parcelId,
        'otp': otp,
        'receiver_nic': receiverNic,
        'signature_base64': signatureBase64,
        if (photoBase64 != null) 'photo_base64': photoBase64,
        if (deliveryNotes != null) 'delivery_notes': deliveryNotes,
        if (latitude != null) 'latitude': latitude,
        if (longitude != null) 'longitude': longitude,
      }),
    );

    if (response.statusCode != 200) {
      final error = jsonDecode(response.body);
      throw Exception(error['error']['message'] ?? 'Failed to complete delivery');
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  /// Get driver's trips for today
  Future<List<Map<String, dynamic>>> getMyTrips() async {
    final url = Uri.parse('${AppConfig.apiBaseUrl}/api/v1/driver/trips');
    final headers = await _getHeaders();
    
    final response = await http.get(url, headers: headers);

    if (response.statusCode != 200) {
      throw Exception('Failed to load trips');
    }

    final data = jsonDecode(response.body) as Map<String, dynamic>;
    return List<Map<String, dynamic>>.from(data['data'] ?? []);
  }

  /// Get parcels for a specific trip
  Future<List<Map<String, dynamic>>> getTripParcels(String tripId) async {
    final url = Uri.parse('${AppConfig.apiBaseUrl}/api/v1/driver/trips/$tripId/parcels');
    final headers = await _getHeaders();
    
    final response = await http.get(url, headers: headers);

    if (response.statusCode != 200) {
      throw Exception('Failed to load trip parcels');
    }

    final data = jsonDecode(response.body) as Map<String, dynamic>;
    return List<Map<String, dynamic>>.from(data['data'] ?? []);
  }

  /// Scan parcel QR code
  Future<Map<String, dynamic>> scanParcel({
    required String parcelNumber,
    required String scanType, // 'pickup', 'hub_receive', 'load', 'hub_arrive', 'out_for_delivery'
    double? latitude,
    double? longitude,
    String? notes,
  }) async {
    final url = Uri.parse('${AppConfig.apiBaseUrl}/api/v1/driver/parcels/$parcelNumber/scan');
    final headers = await _getHeaders();
    
    final response = await http.post(
      url,
      headers: headers,
      body: jsonEncode({
        'scan_type': scanType,
        if (latitude != null) 'latitude': latitude,
        if (longitude != null) 'longitude': longitude,
        if (notes != null) 'notes': notes,
      }),
    );

    if (response.statusCode != 200) {
      final error = jsonDecode(response.body);
      throw Exception(error['error']['message'] ?? 'Scan failed');
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }
}
