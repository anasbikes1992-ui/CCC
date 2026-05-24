import 'package:flutter/material.dart';
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
    _loadToken();
  }

  Future<void> _loadToken() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    
    if (token != null) {
      try {
        final response = await ApiService.get('/auth/me');
        if (response['success'] == true) {
          _isAuthenticated = true;
          _user = response['data']['user'];
        } else {
          await logout();
        }
      } catch (e) {
        // Token might be invalid or network error
        // Let's assume unauthenticated for now
        _isAuthenticated = false;
      }
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    try {
      final response = await ApiService.post('/auth/login', {
        'phone': email,
        'password': password,
      });

      if (response['success'] == true) {
        final token = response['data']['token'];
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', token);
        
        _isAuthenticated = true;
        _user = response['data']['user'];
        notifyListeners();
        return true;
      }
    } catch (e) {
      debugPrint('Login error: $e');
    }
    return false;
  }

  Future<void> logout() async {
    try {
      if (_isAuthenticated) {
        await ApiService.post('/auth/logout', {});
      }
    } catch (e) {
      debugPrint('Logout error: $e');
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    _isAuthenticated = false;
    _user = null;
    notifyListeners();
  }
}
