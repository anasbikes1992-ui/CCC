import 'package:flutter/material.dart';
import '../models/parcel.dart';
import '../services/api_service.dart';

class ParcelProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  List<Parcel> _parcels = [];
  Parcel? _currentParcel;
  bool _isLoading = false;
  String? _error;
  
  List<Parcel> get parcels => _parcels;
  Parcel? get currentParcel => _currentParcel;
  bool get isLoading => _isLoading;
  String? get error => _error;
  
  List<Parcel> get activeParcels => _parcels.where((p) => !p.isDelivered).toList();
  List<Parcel> get deliveredParcels => _parcels.where((p) => p.isDelivered).toList();
  
  Future<void> fetchParcels({String? status}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      _parcels = await _apiService.getParcels(status: status, limit: 100);
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }
  
  Future<bool> fetchParcelDetails(String parcelId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      _currentParcel = await _apiService.getParcelDetails(parcelId);
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
  
  Future<bool> trackParcel(String parcelNumber) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      _currentParcel = await _apiService.trackParcel(parcelNumber);
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
  
  Future<bool> cancelParcel(String parcelId, String reason) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      await _apiService.cancelParcel(parcelId, reason);
      await fetchParcels(); // Refresh list
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
  
  Future<bool> rateDelivery(String parcelId, int rating, String? feedback) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      await _apiService.rateDelivery(parcelId, rating, feedback);
      await fetchParcelDetails(parcelId); // Refresh details
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
  
  void clearCurrentParcel() {
    _currentParcel = null;
    notifyListeners();
  }
}
