import 'package:flutter/material.dart';
import '../api_service.dart';

class BookParcelScreen extends StatefulWidget {
  const BookParcelScreen({super.key});

  @override
  State<BookParcelScreen> createState() => _BookParcelScreenState();
}

class _BookParcelScreenState extends State<BookParcelScreen> {
  final _formKey = GlobalKey<FormState>();
  final _receiverNameController = TextEditingController();
  final _receiverPhoneController = TextEditingController();
  final _weightController = TextEditingController(text: '1');
  final _pickupAddressController = TextEditingController();
  final _dropAddressController = TextEditingController();
  final _declaredValueController = TextEditingController();

  String? _selectedRoute;
  String? _selectedSize;
  List<Map<String, dynamic>> _routes = [];
  List<Map<String, dynamic>> _sizes = [];
  bool _isLoading = false;
  bool _loadingData = true;
  String? _loadError;
  double? _estimatedPrice;
  double? _receiverChargeEstimate;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _receiverNameController.dispose();
    _receiverPhoneController.dispose();
    _weightController.dispose();
    _pickupAddressController.dispose();
    _dropAddressController.dispose();
    _declaredValueController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() {
      _loadingData = true;
      _loadError = null;
    });

    try {
      final routesResponse = await ApiService.get('/customer/routes');
      final sizesResponse = await ApiService.get('/customer/package-sizes');

      final routes = _extractList(routesResponse);
      final sizes = _extractList(sizesResponse);

      String? loadError;
      if (routes.isEmpty || sizes.isEmpty) {
        final routeError = routesResponse['error']?['message']?.toString();
        final sizeError = sizesResponse['error']?['message']?.toString();
        final details = [routeError, sizeError].whereType<String>().where((e) => e.isNotEmpty).join(' | ');
        loadError = details.isNotEmpty
            ? details
            : 'Route/package data not available yet. Please try again.';
      }

      if (mounted) {
        setState(() {
          _routes = routes;
          _sizes = sizes;
          _loadError = loadError;
          _loadingData = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _loadingData = false;
          _loadError = 'Failed to load route/package data. Check connection and try again.';
        });
      }
    }
  }

  List<Map<String, dynamic>> _extractList(Map<String, dynamic>? response) {
    if (response == null) return [];

    final data = response['data'];
    if (data is List) {
      return data.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
    }

    if (data is Map && data['data'] is List) {
      final nested = data['data'] as List;
      return nested.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
    }

    return [];
  }

  Map<String, dynamic>? _selectedRouteData() {
    for (final route in _routes) {
      final routeCode = (route['code'] ?? route['id'] ?? '').toString();
      if (routeCode == _selectedRoute) {
        return route;
      }
    }
    return null;
  }

  String _formatRouteHubSummary() {
    final route = _selectedRouteData();
    final origin = (route?['origin_hub'] ?? 'Colombo Hub').toString();
    final destination = (route?['destination_hub'] ?? 'Destination Hub').toString();
    return '$origin -> $destination';
  }

  String _bookingErrorMessage(Map<String, dynamic> response) {
    final error = response['error'];
    if (error is Map<String, dynamic>) {
      final message = error['message']?.toString();
      final details = error['details'];
      if (details is Map) {
        final values = details.values
            .whereType<List>()
            .expand((item) => item)
            .map((item) => item.toString())
            .where((item) => item.isNotEmpty)
            .toList();
        if (values.isNotEmpty) {
          return values.first;
        }
      }
      if (message != null && message.isNotEmpty) {
        return message;
      }
    }

    return 'Booking failed. Please try again.';
  }

  Future<void> _calculatePrice() async {
    if (_selectedRoute == null || _selectedSize == null) return;

    try {
      final response = await ApiService.post('/customer/parcels/quote', {
        'route_code': _selectedRoute,
        'package_size_code': _selectedSize,
        'pickup_type': 'hub',
        'drop_type': 'hub',
        'is_express': false,
        'has_insurance': false,
        'declared_value_lkr':
            double.tryParse(_declaredValueController.text) ?? 0,
        'cod_amount_lkr': 0,
      });

      if (response['success'] == true && mounted) {
        setState(() {
          _estimatedPrice = (response['data']['total_lkr'] as num?)?.toDouble();
          _receiverChargeEstimate = (response['data']['receiver_charge_lkr'] as num?)?.toDouble();
        });
      }
    } catch (e) {
      // Ignore calculation errors
    }
  }

  Future<void> _submitBooking() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final selectedRoute = _selectedRouteData();

    Map<String, dynamic> response;
    try {
      response = await ApiService.post('/customer/parcels', {
        'receiver_name': _receiverNameController.text.trim(),
        'receiver_phone': _receiverPhoneController.text.trim(),
        'route_code': _selectedRoute,
        'package_size_code': _selectedSize,
        'weight_kg': double.tryParse(_weightController.text) ?? 1,
        'pickup_type': 'hub',
        'pickup_hub_code': selectedRoute?['origin_hub_code'] ?? 'CMB',
        'drop_type': 'hub',
        'drop_hub_code': selectedRoute?['destination_hub_code'] ?? 'KDY',
        'declared_value_lkr': double.tryParse(_declaredValueController.text) ?? 0,
        'cod_amount_lkr': 0,
        'is_express': false,
        'has_insurance': false,
        'payment_method': 'bank_transfer',
      });
    } catch (e) {
      response = {
        'success': false,
        'error': {'message': e.toString()},
      };
    }

    if (mounted) {
      setState(() => _isLoading = false);
    }

    if (!mounted) return;

    if (response['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Parcel booked successfully!'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_bookingErrorMessage(response)),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loadingData) {
      return Scaffold(
        appBar: AppBar(title: const Text('Book Parcel')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Book Parcel')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (_loadError != null) ...[
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.orange.shade50,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.orange.shade200),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Icon(Icons.warning_amber_rounded, color: Colors.orange),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _loadError!,
                          style: TextStyle(color: Colors.orange.shade900),
                        ),
                      ),
                      TextButton(onPressed: _loadData, child: const Text('Retry')),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
              ],

              Text(
                'Receiver Information',
                style: Theme.of(
                  context,
                ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: _receiverNameController,
                decoration: const InputDecoration(
                  labelText: 'Receiver Name',
                  border: OutlineInputBorder(),
                ),
                validator: (v) => v?.isEmpty == true ? 'Required' : null,
              ),
              const SizedBox(height: 12),

              TextFormField(
                controller: _receiverPhoneController,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(
                  labelText: 'Receiver Phone',
                  hintText: '+94771234567',
                  border: OutlineInputBorder(),
                ),
                validator: (v) {
                  if (v?.isEmpty == true) return 'Required';
                  if (!v!.startsWith('+')) return 'Use E.164 format (+94...)';
                  return null;
                },
              ),
              const SizedBox(height: 24),

              Text(
                'Parcel Details',
                style: Theme.of(
                  context,
                ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),

              DropdownButtonFormField<String>(
                initialValue: _selectedRoute,
                decoration: const InputDecoration(
                  labelText: 'Route',
                  border: OutlineInputBorder(),
                ),
                items: _routes.map<DropdownMenuItem<String>>((route) {
                  final routeCode = (route['code'] ?? route['id'] ?? '').toString();
                  return DropdownMenuItem<String>(
                    value: routeCode,
                    child: Text(
                      '$routeCode - ${route['display_name'] ?? route['name'] ?? 'Route'}',
                    ),
                  );
                }).toList(),
                onChanged: _routes.isEmpty
                    ? null
                    : (value) {
                  setState(() => _selectedRoute = value);
                  _calculatePrice();
                },
                validator: (v) => v == null ? 'Please select route' : null,
              ),
              const SizedBox(height: 12),

              DropdownButtonFormField<String>(
                initialValue: _selectedSize,
                decoration: const InputDecoration(
                  labelText: 'Package Size',
                  border: OutlineInputBorder(),
                ),
                items: _sizes.map<DropdownMenuItem<String>>((size) {
                  final sizeCode = (size['code'] ?? size['id'] ?? '').toString();
                  return DropdownMenuItem<String>(
                    value: sizeCode,
                    child: Text(
                      '$sizeCode - ${size['display_name'] ?? size['name'] ?? 'Size'} (${size['max_weight_kg'] ?? '-'}kg)',
                    ),
                  );
                }).toList(),
                onChanged: _sizes.isEmpty
                    ? null
                    : (value) {
                  setState(() => _selectedSize = value);
                  _calculatePrice();
                },
                validator: (v) => v == null ? 'Please select size' : null,
              ),
              const SizedBox(height: 12),

              TextFormField(
                controller: _weightController,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: const InputDecoration(
                  labelText: 'Weight (kg)',
                  border: OutlineInputBorder(),
                ),
                validator: (v) {
                  if (v == null || v.trim().isEmpty) return 'Required';
                  final weight = double.tryParse(v.trim());
                  if (weight == null || weight <= 0) {
                    return 'Enter valid weight';
                  }
                  return null;
                },
                onChanged: (_) => _calculatePrice(),
              ),
              const SizedBox(height: 12),

              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.blue.shade100),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Pilot booking mode',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 6),
                    Text('Route: ${_formatRouteHubSummary()}'),
                    const SizedBox(height: 4),
                    const Text('Sender drops at Colombo hub. Receiver pays freight charge when collecting or receiving the parcel.'),
                  ],
                ),
              ),
              const SizedBox(height: 12),

              TextFormField(
                controller: _declaredValueController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'Declared Goods Value (LKR)',
                  border: OutlineInputBorder(),
                ),
                onChanged: (_) => _calculatePrice(),
              ),
              const SizedBox(height: 24),

              if (_estimatedPrice != null)
                Card(
                  color: Colors.blue.shade50,
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Sender booking fee:',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            Text(
                              'LKR ${_estimatedPrice!.toStringAsFixed(2)}',
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: Colors.blue,
                              ),
                            ),
                          ],
                        ),
                        if (_receiverChargeEstimate != null) ...[
                          const SizedBox(height: 8),
                          Text(
                            'Receiver freight estimate: LKR ${_receiverChargeEstimate!.toStringAsFixed(2)}',
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.blueGrey.shade700,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              const SizedBox(height: 16),

              ElevatedButton(
                onPressed: _isLoading ? null : _submitBooking,
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
                child: _isLoading
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Book Now', style: TextStyle(fontSize: 16)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
