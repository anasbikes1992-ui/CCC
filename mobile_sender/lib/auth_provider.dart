import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

class AuthProvider with ChangeNotifier {
  bool _isAuthenticated = false;
  Map<String, dynamic>? _user;
  bool _isLoading = true;

  bool get isAuthenticated => _isAuthenticated;
  Map<String, dynamic>? get user => _user;
  bool get isLoading => _isLoading;

  AuthProvider() {
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      final userJson = prefs.getString('user_data');

      if (token != null && userJson != null) {
        _isAuthenticated = true;
        _user = {'token': token};
        // Could fetch fresh user data here
      }
    } catch (e) {
      print('Auth check error: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> login(String phone, String password) async {
    try {
      final response = await ApiService.post('/auth/login', {
        'phone': phone,
        'password': password,
      });

      if (response['success'] == true) {
        final token = response['data']['token'];
        final user = response['data']['user'];

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', token);
        await prefs.setString('user_data', user.toString());

        _isAuthenticated = true;
        _user = user;
        notifyListeners();
      }

      return response;
    } catch (e) {
      return {
        'success': false,
        'error': {'message': 'Login failed: ${e.toString()}'},
      };
    }
  }

  Future<Map<String, dynamic>> register(Map<String, dynamic> data) async {
    try {
      final response = await ApiService.post('/auth/register', data);

      if (response['success'] == true) {
        // Auto-login after registration
        final token = response['data']['token'];
        final user = response['data']['user'];

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', token);
        await prefs.setString('user_data', user.toString());

        _isAuthenticated = true;
        _user = user;
        notifyListeners();
      }

      return response;
    } catch (e) {
      return {
        'success': false,
        'error': {'message': 'Registration failed: ${e.toString()}'},
      };
    }
  }

  Future<void> logout() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('auth_token');
      await prefs.remove('user_data');

      _isAuthenticated = false;
      _user = null;
      notifyListeners();
    } catch (e) {
      print('Logout error: $e');
    }
  }
}
