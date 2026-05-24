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
  double? _estimatedPrice;

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
    try {
      final routesResponse = await ApiService.get('/customer/routes');
      final sizesResponse = await ApiService.get('/customer/package-sizes');

      if (mounted) {
        setState(() {
          _routes = List<Map<String, dynamic>>.from(
            routesResponse['success'] == true
                ? (routesResponse['data'] ?? [])
                : [],
          );
          _sizes = List<Map<String, dynamic>>.from(
            sizesResponse['success'] == true
                ? (sizesResponse['data'] ?? [])
                : [],
          );
          _loadingData = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _loadingData = false);
      }
    }
  }

  Future<void> _calculatePrice() async {
    if (_selectedRoute == null || _selectedSize == null) return;

    try {
      final response = await ApiService.post('/customer/parcels/quote', {
        'route_code': _selectedRoute,
        'package_size_code': _selectedSize,
        'pickup_type': 'doorstep',
        'drop_type': 'doorstep',
        'is_express': false,
        'has_insurance': false,
        'declared_value_lkr':
            double.tryParse(_declaredValueController.text) ?? 0,
        'cod_amount_lkr': 0,
      });

      if (response['success'] == true && mounted) {
        setState(() {
          _estimatedPrice = (response['data']['total_lkr'] as num?)?.toDouble();
        });
      }
    } catch (e) {
      // Ignore calculation errors
    }
  }

  Future<void> _submitBooking() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final response = await ApiService.post('/customer/parcels', {
      'receiver_name': _receiverNameController.text.trim(),
      'receiver_phone': _receiverPhoneController.text.trim(),
      'route_code': _selectedRoute,
      'package_size_code': _selectedSize,
      'weight_kg': double.tryParse(_weightController.text) ?? 1,
      'pickup_type': 'doorstep',
      'drop_type': 'doorstep',
      'pickup_address': _pickupAddressController.text.trim(),
      'drop_address': _dropAddressController.text.trim(),
      'declared_value_lkr': double.tryParse(_declaredValueController.text) ?? 0,
      'cod_amount_lkr': 0,
      'is_express': false,
      'has_insurance': false,
      'payment_method': 'bank_transfer',
    });

    setState(() => _isLoading = false);

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
          content: Text(response['error']?['message'] ?? 'Booking failed'),
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
                value: _selectedRoute,
                decoration: const InputDecoration(
                  labelText: 'Route',
                  border: OutlineInputBorder(),
                ),
                items: _routes.map<DropdownMenuItem<String>>((route) {
                  return DropdownMenuItem<String>(
                    value: route['code'].toString(),
                    child: Text(
                      '${route['code']} - ${route['display_name'] ?? route['name']}',
                    ),
                  );
                }).toList(),
                onChanged: (value) {
                  setState(() => _selectedRoute = value);
                  _calculatePrice();
                },
                validator: (v) => v == null ? 'Please select route' : null,
              ),
              const SizedBox(height: 12),

              DropdownButtonFormField<String>(
                value: _selectedSize,
                decoration: const InputDecoration(
                  labelText: 'Package Size',
                  border: OutlineInputBorder(),
                ),
                items: _sizes.map<DropdownMenuItem<String>>((size) {
                  return DropdownMenuItem<String>(
                    value: size['code'].toString(),
                    child: Text(
                      '${size['code']} - ${size['display_name'] ?? size['name']} (${size['max_weight_kg']}kg)',
                    ),
                  );
                }).toList(),
                onChanged: (value) {
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
                  if (weight == null || weight <= 0)
                    return 'Enter valid weight';
                  return null;
                },
                onChanged: (_) => _calculatePrice(),
              ),
              const SizedBox(height: 12),

              TextFormField(
                controller: _pickupAddressController,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Pickup Address',
                  border: OutlineInputBorder(),
                ),
                validator: (v) => v?.isEmpty == true ? 'Required' : null,
              ),
              const SizedBox(height: 12),

              TextFormField(
                controller: _dropAddressController,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Drop Address',
                  border: OutlineInputBorder(),
                ),
                validator: (v) => v?.isEmpty == true ? 'Required' : null,
              ),
              const SizedBox(height: 12),

              TextFormField(
                controller: _declaredValueController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'Declared Value (LKR)',
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
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Estimated Price:',
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
