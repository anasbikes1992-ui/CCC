import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';
import '../models/user.dart';
import '../services/api_service.dart';

class AuthProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  User? _user;
  bool _isAuthenticated = false;
  bool _isLoading = false;
  String? _error;
  
  User? get user => _user;
  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;
  String? get error => _error;
  
  Future<void> checkAuthStatus() async {
    _isLoading = true;
    notifyListeners();
    
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString(AppConfig.tokenKey);
      final userJson = prefs.getString(AppConfig.userKey);
      
      if (token != null && userJson != null) {
        _apiService.setToken(token);
        // Try to fetch fresh user data
        try {
          _user = await _apiService.getProfile();
          _isAuthenticated = true;
          await prefs.setString(AppConfig.userKey, userToJson(_user!));
        } catch (e) {
          // If API fails, use cached data
          _user = userFromJson(userJson);
          _isAuthenticated = true;
        }
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
  
  Future<bool> login(String phone) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      await _apiService.login(phone);
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
  
  Future<bool> verifyOtp(String phone, String otp) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      final response = await _apiService.verifyOtp(phone, otp);
      final token = response['data']['token'];
      final userData = response['data']['user'];
      
      _user = User.fromJson(userData);
      _apiService.setToken(token);
      _isAuthenticated = true;
      
      // Save to local storage
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(AppConfig.tokenKey, token);
      await prefs.setString(AppConfig.userKey, userToJson(_user!));
      await prefs.setString(AppConfig.phoneKey, phone);
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
  
  Future<void> logout() async {
    _user = null;
    _isAuthenticated = false;
    _apiService.clearToken();
    
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(AppConfig.tokenKey);
    await prefs.remove(AppConfig.userKey);
    
    notifyListeners();
  }
  
  Future<bool> updateProfile(Map<String, dynamic> profileData) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      _user = await _apiService.updateProfile(profileData);
      
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(AppConfig.userKey, userToJson(_user!));
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
  
  // Helper methods
  String userToJson(User user) {
    return '${user.toJson()}';
  }
  
  User userFromJson(String json) {
    return User.fromJson(json as Map<String, dynamic>);
  }
}
