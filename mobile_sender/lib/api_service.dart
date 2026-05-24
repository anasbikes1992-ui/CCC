import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'config.dart';

class ApiService {
  static String get baseUrl => AppConfig.apiBaseUrl;

  static Future<Map<String, dynamic>> post(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl$endpoint'),
        headers: headers,
        body: jsonEncode(data),
      ).timeout(Duration(seconds: AppConfig.apiTimeoutSeconds));

      return _processResponse(response);
    } catch (e) {
      return {
        'success': false,
        'error': {'message': 'Network error: ${e.toString()}'},
      };
    }
  }

  static Future<Map<String, dynamic>> get(String endpoint) async {
    try {
      final headers = await _getHeaders();
      final response = await http.get(
        Uri.parse('$baseUrl$endpoint'),
        headers: headers,
      ).timeout(Duration(seconds: AppConfig.apiTimeoutSeconds));

      return _processResponse(response);
    } catch (e) {
      return {
        'success': false,
        'error': {'message': 'Network error: ${e.toString()}'},
      };
    }
  }

  static Future<Map<String, String>> _getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');

    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  static Map<String, dynamic> _processResponse(http.Response response) {
    try {
      final data = jsonDecode(response.body);
      if (response.statusCode >= 200 && response.statusCode < 300) {
        return data;
      } else {
        return {
          'success': false,
          'error': data['error'] ?? {'message': 'Unknown error'},
        };
      }
    } catch (e) {
      return {
        'success': false,
        'error': {'message': 'Failed to parse response: ${e.toString()}'},
      };
    }
  }

  // Multipart upload for images
  static Future<Map<String, dynamic>> uploadImage(
    String endpoint,
    String fieldName,
    String filePath,
  ) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl$endpoint'),
      );

      request.headers.addAll({
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      });

      request.files.add(await http.MultipartFile.fromPath(fieldName, filePath));

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      return _processResponse(response);
    } catch (e) {
      return {
        'success': false,
        'error': {'message': 'Upload failed: ${e.toString()}'},
      };
    }
  }
}
