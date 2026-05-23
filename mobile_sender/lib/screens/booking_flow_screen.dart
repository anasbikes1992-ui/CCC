import 'package:flutter/material.dart';
import '../config/app_theme.dart';
import '../services/api_service.dart';
import '../models/route.dart' as app_route;
import 'package:intl/intl.dart';

class BookingFlowScreen extends StatefulWidget {
  const BookingFlowScreen({super.key});

  @override
  State<BookingFlowScreen> createState() => _BookingFlowScreenState();
}

class _BookingFlowScreenState extends State<BookingFlowScreen> {
  int _currentStep = 0;
  bool _isLoading = false;
  List<app_route.Route> _routes = [];

  // Step 1: Route Selection
  String? _selectedRouteId;

  // Step 2: Package Details
  String _selectedSize = 'M';
  final _weightController = TextEditingController();
  final _lengthController = TextEditingController();
  final _widthController = TextEditingController();
  final _heightController = TextEditingController();
  final _declaredValueController = TextEditingController();

  // Step 3: Pickup & Drop
  String _pickupType = 'hub';
  String _dropType = 'hub';
  final _pickupAddressController = TextEditingController();
  final _dropAddressController = TextEditingController();
  final _receiverNameController = TextEditingController();
  final _receiverPhoneController = TextEditingController();

  // Step 4: Additional Options
  bool _isExpress = false;
  bool _hasInsurance = false;
  bool _hasCOD = false;
  final _codAmountController = TextEditingController();

  // Step 5: Price Summary
  double _basePrice = 0;
  double _surcharges = 0;
  double _totalPrice = 0;

  @override
  void initState() {
    super.initState();
    _loadRoutes();
  }

  @override
  void dispose() {
    _weightController.dispose();
    _lengthController.dispose();
    _widthController.dispose();
    _heightController.dispose();
    _declaredValueController.dispose();
    _pickupAddressController.dispose();
    _dropAddressController.dispose();
    _receiverNameController.dispose();
    _receiverPhoneController.dispose();
    _codAmountController.dispose();
    super.dispose();
  }

  Future<void> _loadRoutes() async {
    setState(() => _isLoading = true);
    try {
      final routes = await ApiService().getRoutes();
      setState(() {
        _routes = routes;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load routes: $e')),
        );
      }
    }
  }

  Future<void> _calculatePrice() async {
    if (_selectedRouteId == null) return;

    setState(() => _isLoading = true);
    try {
      final data = await ApiService().calculatePrice(
        routeId: _selectedRouteId!,
        size: _selectedSize,
        weightKg: double.tryParse(_weightController.text) ?? 0,
        pickupType: _pickupType,
        dropType: _dropType,
        isExpress: _isExpress,
        hasInsurance: _hasInsurance,
        declaredValue: double.tryParse(_declaredValueController.text),
        codAmount: _hasCOD ? double.tryParse(_codAmountController.text) : null,
      );

      setState(() {
        _basePrice = data['base_price_lkr']?.toDouble() ?? 0;
        _surcharges = data['surcharges_lkr']?.toDouble() ?? 0;
        _totalPrice = data['total_price_lkr']?.toDouble() ?? 0;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to calculate price: $e')),
        );
      }
    }
  }

  Future<void> _submitBooking() async {
    setState(() => _isLoading = true);
    try {
      final booking = await ApiService().createBooking(
        routeId: _selectedRouteId!,
        size: _selectedSize,
        weightKg: double.parse(_weightController.text),
        pickupType: _pickupType,
        dropType: _dropType,
        receiverName: _receiverNameController.text,
        receiverPhone: _receiverPhoneController.text,
        pickupAddress: _pickupType == 'doorstep' ? _pickupAddressController.text : null,
        dropAddress: _dropType == 'doorstep' ? _dropAddressController.text : null,
        isExpress: _isExpress,
        hasInsurance: _hasInsurance,
        declaredValue: double.tryParse(_declaredValueController.text),
        codAmount: _hasCOD ? double.tryParse(_codAmountController.text) : null,
        lengthCm: int.tryParse(_lengthController.text),
        widthCm: int.tryParse(_widthController.text),
        heightCm: int.tryParse(_heightController.text),
      );

      setState(() => _isLoading = false);

      if (mounted) {
        Navigator.of(context).pushReplacementNamed(
          '/parcel-details',
          arguments: booking['id'],
        );
      }
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Booking failed: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Book New Parcel'),
        elevation: 0,
      ),
      body: _isLoading && _routes.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : Stepper(
              currentStep: _currentStep,
              onStepContinue: _onStepContinue,
              onStepCancel: _onStepCancel,
              controlsBuilder: _buildControls,
              steps: [
                _buildRouteStep(),
                _buildPackageStep(),
                _buildPickupDropStep(),
                _buildOptionsStep(),
                _buildSummaryStep(),
              ],
            ),
    );
  }

  Step _buildRouteStep() {
    return Step(
      title: const Text('Select Route'),
      content: Column(
        children: _routes.map((route) {
          final isSelected = _selectedRouteId == route.id;
          return Card(
            elevation: isSelected ? 2 : 0,
            color: isSelected ? AppTheme.primary.withValues(alpha: 0.1) : null,
            child: ListTile(
              leading: const Icon(Icons.route),
              title: Text('${route.originHub} → ${route.destinationHub}'),
              subtitle: Text(route.code),
              trailing: isSelected ? const Icon(Icons.check_circle, color: AppTheme.primary) : null,
              onTap: () => setState(() => _selectedRouteId = route.id),
            ),
          );
        }).toList(),
      ),
      isActive: _currentStep >= 0,
      state: _currentStep > 0 ? StepState.complete : StepState.indexed,
    );
  }

  Step _buildPackageStep() {
    return Step(
      title: const Text('Package Details'),
      content: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Size:', style: TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: ['S', 'M', 'L', 'XL', 'Bale'].map((size) {
              final isSelected = _selectedSize == size;
              return ChoiceChip(
                label: Text(size),
                selected: isSelected,
                onSelected: (_) => setState(() => _selectedSize = size),
              );
            }).toList(),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _weightController,
            decoration: const InputDecoration(
              labelText: 'Weight (kg)*',
              border: OutlineInputBorder(),
            ),
            keyboardType: TextInputType.number,
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _lengthController,
                  decoration: const InputDecoration(
                    labelText: 'Length (cm)',
                    border: OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.number,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: TextField(
                  controller: _widthController,
                  decoration: const InputDecoration(
                    labelText: 'Width (cm)',
                    border: OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.number,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: TextField(
                  controller: _heightController,
                  decoration: const InputDecoration(
                    labelText: 'Height (cm)',
                    border: OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.number,
                ),
              ),
            ],
          ),
        ],
      ),
      isActive: _currentStep >= 1,
      state: _currentStep > 1 ? StepState.complete : StepState.indexed,
    );
  }

  Step _buildPickupDropStep() {
    return Step(
      title: const Text('Pickup & Delivery'),
      content: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Receiver details first
          TextField(
            controller: _receiverNameController,
            decoration: const InputDecoration(
              labelText: 'Receiver Name*',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _receiverPhoneController,
            decoration: const InputDecoration(
              labelText: 'Receiver Phone*',
              border: OutlineInputBorder(),
              prefix: Text('+94'),
            ),
            keyboardType: TextInputType.phone,
          ),
          const SizedBox(height: 16),
          const Divider(),
          const SizedBox(height: 16),
          const Text('Pickup Type:', style: TextStyle(fontWeight: FontWeight.w600)),
          RadioListTile<String>(
            title: const Text('Hub Pickup'),
            value: 'hub',
            groupValue: _pickupType,
            onChanged: (val) => setState(() => _pickupType = val!),
          ),
          RadioListTile<String>(
            title: const Text('Doorstep Pickup (+surcharge)'),
            value: 'doorstep',
            groupValue: _pickupType,
            onChanged: (val) => setState(() => _pickupType = val!),
          ),
          if (_pickupType == 'doorstep') ...[
            const SizedBox(height: 8),
            TextField(
              controller: _pickupAddressController,
              decoration: const InputDecoration(
                labelText: 'Pickup Address*',
                border: OutlineInputBorder(),
              ),
              maxLines: 2,
            ),
          ],
          const SizedBox(height: 16),
          const Text('Delivery Type:', style: TextStyle(fontWeight: FontWeight.w600)),
          RadioListTile<String>(
            title: const Text('Hub Pickup'),
            value: 'hub',
            groupValue: _dropType,
            onChanged: (val) => setState(() => _dropType = val!),
          ),
          RadioListTile<String>(
            title: const Text('Doorstep Delivery (+surcharge)'),
            value: 'doorstep',
            groupValue: _dropType,
            onChanged: (val) => setState(() => _dropType = val!),
          ),
          if (_dropType == 'doorstep') ...[
            const SizedBox(height: 8),
            TextField(
              controller: _dropAddressController,
              decoration: const InputDecoration(
                labelText: 'Delivery Address*',
                border: OutlineInputBorder(),
              ),
              maxLines: 2,
            ),
          ],
        ],
      ),
      isActive: _currentStep >= 2,
      state: _currentStep > 2 ? StepState.complete : StepState.indexed,
    );
  }

  Step _buildOptionsStep() {
    return Step(
      title: const Text('Additional Options'),
      content: Column(
        children: [
          SwitchListTile(
            title: const Text('Express Delivery'),
            subtitle: const Text('Priority handling (+surcharge)'),
            value: _isExpress,
            onChanged: (val) => setState(() => _isExpress = val),
          ),
          SwitchListTile(
            title: const Text('Insurance'),
            subtitle: const Text('1.5% of declared value'),
            value: _hasInsurance,
            onChanged: (val) => setState(() => _hasInsurance = val),
          ),
          if (_hasInsurance) ...[
            const SizedBox(height: 8),
            TextField(
              controller: _declaredValueController,
              decoration: const InputDecoration(
                labelText: 'Declared Value (LKR)',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.number,
            ),
          ],
          SwitchListTile(
            title: const Text('Cash on Delivery (COD)'),
            subtitle: const Text('Collect payment on delivery'),
            value: _hasCOD,
            onChanged: (val) => setState(() => _hasCOD = val),
          ),
          if (_hasCOD) ...[
            const SizedBox(height: 8),
            TextField(
              controller: _codAmountController,
              decoration: const InputDecoration(
                labelText: 'COD Amount (LKR)',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.number,
            ),
          ],
        ],
      ),
      isActive: _currentStep >= 3,
      state: _currentStep > 3 ? StepState.complete : StepState.indexed,
    );
  }

  Step _buildSummaryStep() {
    return Step(
      title: const Text('Review & Pay'),
      content: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (_totalPrice > 0) ...[
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Price Breakdown',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 12),
                    _PriceRow(label: 'Base Price', amount: _basePrice),
                    _PriceRow(label: 'Surcharges', amount: _surcharges),
                    const Divider(),
                    _PriceRow(
                      label: 'Total',
                      amount: _totalPrice,
                      isTotal: true,
                    ),
                  ],
                ),
              ),
            ),
          ] else ...[
            ElevatedButton(
              onPressed: _calculatePrice,
              child: const Text('Calculate Price'),
            ),
          ],
          const SizedBox(height: 16),
          const Text(
            'By booking, you agree to our terms and conditions.',
            style: TextStyle(fontSize: 12, color: AppTheme.textSecondary),
          ),
        ],
      ),
      isActive: _currentStep >= 4,
      state: _currentStep > 4 ? StepState.complete : StepState.indexed,
    );
  }

  Widget _buildControls(BuildContext context, ControlsDetails details) {
    return Padding(
      padding: const EdgeInsets.only(top: 16.0),
      child: Row(
        children: [
          if (details.stepIndex < 4)
            ElevatedButton(
              onPressed: details.onStepContinue,
              child: const Text('Continue'),
            ),
          if (details.stepIndex == 4)
            ElevatedButton(
              onPressed: _totalPrice > 0 ? _submitBooking : _calculatePrice,
              child: Text(_totalPrice > 0 ? 'Book Now' : 'Calculate Price'),
            ),
          const SizedBox(width: 12),
          if (details.stepIndex > 0)
            TextButton(
              onPressed: details.onStepCancel,
              child: const Text('Back'),
            ),
        ],
      ),
    );
  }

  void _onStepContinue() {
    if (_currentStep == 0 && _selectedRouteId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a route')),
      );
      return;
    }

    if (_currentStep == 1 && _weightController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter weight')),
      );
      return;
    }

    if (_currentStep == 2) {
      if (_receiverNameController.text.isEmpty ||
          _receiverPhoneController.text.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please enter receiver details')),
        );
        return;
      }
    }

    if (_currentStep < 4) {
      setState(() => _currentStep += 1);
      if (_currentStep == 4) {
        _calculatePrice();
      }
    }
  }

  void _onStepCancel() {
    if (_currentStep > 0) {
      setState(() => _currentStep -= 1);
    }
  }
}

class _PriceRow extends StatelessWidget {
  final String label;
  final double amount;
  final bool isTotal;

  const _PriceRow({
    required this.label,
    required this.amount,
    this.isTotal = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: isTotal ? 16 : 14,
              fontWeight: isTotal ? FontWeight.w600 : FontWeight.normal,
            ),
          ),
          Text(
            'LKR ${NumberFormat('#,##0.00').format(amount)}',
            style: TextStyle(
              fontSize: isTotal ? 16 : 14,
              fontWeight: isTotal ? FontWeight.w600 : FontWeight.normal,
              color: isTotal ? AppTheme.primary : null,
            ),
          ),
        ],
      ),
    );
  }
}
