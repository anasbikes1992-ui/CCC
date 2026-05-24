import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';
import '../models/user.dart';
import '../models/route.dart' as route_model;
import '../models/parcel.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  String? _token;

  Future<void> initialize() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(AppConfig.tokenKey);
  }

  void setToken(String token) {
    _token = token;
  }

  void clearToken() {
    _token = null;
  }

  Map<String, String> get _headers {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (_token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }

    return headers;
  }

  Future<Map<String, dynamic>> _handleResponse(http.Response response) async {
    final body = json.decode(response.body);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    } else {
      throw ApiException(
        message: body['error']?['message'] ?? 'An error occurred',
        statusCode: response.statusCode,
        details: body['error']?['details'],
      );
    }
  }

  // Auth Endpoints
  Future<Map<String, dynamic>> login(String phone) async {
    final response = await http.post(
      Uri.parse('${AppConfig.apiBaseUrl}${AppConfig.loginEndpoint}'),
      headers: _headers,
      body: json.encode({'phone': phone}),
    );

    return _handleResponse(response);
  }

  Future<Map<String, dynamic>> verifyOtp(String phone, String otp) async {
    final response = await http.post(
      Uri.parse('${AppConfig.apiBaseUrl}${AppConfig.otpVerifyEndpoint}'),
      headers: _headers,
      body: json.encode({'phone': phone, 'otp': otp}),
    );

    return _handleResponse(response);
  }

  // Routes Endpoints
  Future<List<route_model.Route>> getRoutes() async {
    final response = await http.get(
      Uri.parse('${AppConfig.apiBaseUrl}${AppConfig.routesEndpoint}'),
      headers: _headers,
    );

    final data = await _handleResponse(response);
    return (data['data'] as List)
        .map((json) => route_model.Route.fromJson(json))
        .toList();
  }

  // Booking Endpoints
  Future<Map<String, dynamic>> createBooking(
    Map<String, dynamic> bookingData,
  ) async {
    final response = await http.post(
      Uri.parse('${AppConfig.apiBaseUrl}${AppConfig.bookingEndpoint}'),
      headers: _headers,
      body: json.encode(bookingData),
    );

    return _handleResponse(response);
  }

  Future<Map<String, dynamic>> calculatePrice(
    Map<String, dynamic> priceData,
  ) async {
    final response = await http.post(
      Uri.parse('${AppConfig.apiBaseUrl}${AppConfig.calculatePriceEndpoint}'),
      headers: _headers,
      body: json.encode(priceData),
    );

    return _handleResponse(response);
  }

  // Parcels Endpoints
  Future<List<Parcel>> getParcels({
    String? status,
    int? limit,
    int? offset,
  }) async {
    var uri = Uri.parse('${AppConfig.apiBaseUrl}${AppConfig.parcelsEndpoint}');

    final queryParams = <String, String>{};
    if (status != null) queryParams['status'] = status;
    if (limit != null) queryParams['limit'] = limit.toString();
    if (offset != null) queryParams['offset'] = offset.toString();

    if (queryParams.isNotEmpty) {
      uri = uri.replace(queryParameters: queryParams);
    }

    final response = await http.get(uri, headers: _headers);
    final data = await _handleResponse(response);

    return (data['data'] as List).map((json) => Parcel.fromJson(json)).toList();
  }

  Future<Parcel> getParcelDetails(String parcelId) async {
    final response = await http.get(
      Uri.parse(
        '${AppConfig.apiBaseUrl}${AppConfig.parcelsEndpoint}/$parcelId',
      ),
      headers: _headers,
    );

    final data = await _handleResponse(response);
    final payload = data['data'];
    final parcelJson = payload is Map<String, dynamic>
        ? (payload['parcel'] ?? payload)
        : payload;
    return Parcel.fromJson(parcelJson as Map<String, dynamic>);
  }

  Future<Parcel> trackParcel(String parcelNumber) async {
    final response = await http.get(
      Uri.parse('${AppConfig.apiBaseUrl}${AppConfig.trackEndpoint}/$parcelNumber/track'),
      headers: _headers,
    );

    final data = await _handleResponse(response);
    final payload = data['data'];
    final parcelJson = payload is Map<String, dynamic>
        ? (payload['parcel'] ?? payload)
        : payload;
    return Parcel.fromJson(parcelJson as Map<String, dynamic>);
  }

  Future<Map<String, dynamic>> cancelParcel(
    String parcelId,
    String reason,
  ) async {
    final response = await http.post(
      Uri.parse(
        '${AppConfig.apiBaseUrl}${AppConfig.parcelsEndpoint}/$parcelId/cancel',
      ),
      headers: _headers,
      body: json.encode({'reason': reason}),
    );

    return _handleResponse(response);
  }

  Future<Map<String, dynamic>> rateDelivery(
    String parcelId,
    int rating,
    String? feedback,
  ) async {
    final response = await http.post(
      Uri.parse(
        '${AppConfig.apiBaseUrl}${AppConfig.parcelsEndpoint}/$parcelId/rate',
      ),
      headers: _headers,
      body: json.encode({'rating': rating, 'feedback': feedback}),
    );

    return _handleResponse(response);
  }

  // Profile Endpoints
  Future<User> getProfile() async {
    final response = await http.get(
      Uri.parse('${AppConfig.apiBaseUrl}${AppConfig.profileEndpoint}'),
      headers: _headers,
    );

    final data = await _handleResponse(response);
    return User.fromJson(data['data']);
  }

  Future<User> updateProfile(Map<String, dynamic> profileData) async {
    final response = await http.put(
      Uri.parse('${AppConfig.apiBaseUrl}${AppConfig.profileEndpoint}'),
      headers: _headers,
      body: json.encode(profileData),
    );

    final data = await _handleResponse(response);
    return User.fromJson(data['data']);
  }
}

class ApiException implements Exception {
  final String message;
  final int statusCode;
  final dynamic details;

  ApiException({required this.message, required this.statusCode, this.details});

  @override
  String toString() => message;
}
