import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../api_service.dart';
import 'delivery_verification_screen.dart';

class ScanScreen extends StatefulWidget {
  final String tripId;
  const ScanScreen({super.key, required this.tripId});

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  final MobileScannerController _scannerController = MobileScannerController();
  bool _isProcessing = false;
  String _selectedEvent = 'LOADED_ON_LORRY';

  final List<String> _eventTypes = [
    'LOADED_ON_LORRY',
    'ARRIVED_AT_DESTINATION_HUB',
    'OUT_FOR_DELIVERY',
    'DELIVERED',
  ];

  @override
  void dispose() {
    _scannerController.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) async {
    if (_isProcessing) return;
    
    final List<Barcode> barcodes = capture.barcodes;
    if (barcodes.isNotEmpty && barcodes.first.rawValue != null) {
      final code = barcodes.first.rawValue!;
      setState(() {
        _isProcessing = true;
      });

      // Stop scanner while processing
      _scannerController.stop();

      try {
        if (_selectedEvent == 'DELIVERED') {
          // Navigate to delivery verification screen instead of calling simple scan
          if (mounted) {
            final result = await Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => DeliveryVerificationScreen(qrToken: code),
              ),
            );
            if (result == true) {
              _showMessage('Delivered successfully!', Colors.green);
            }
          }
        } else {
          // Send normal scan to backend
          final prefs = await SharedPreferences.getInstance();
          final token = prefs.getString('auth_token');

          final response = await http.post(
            Uri.parse('${ApiService.baseUrl}/driver/parcels/qr-scan/scan'),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': 'Bearer $token',
              'X-Scan-Mode': 'qr',
            },
            body: jsonEncode({
              'qr_token': code,
              'event_type': _selectedEvent,
            }),
          );

          final data = jsonDecode(response.body);

          if (response.statusCode >= 200 && response.statusCode < 300 && data['success'] == true) {
            _showMessage('Scanned successfully!', Colors.green);
          } else {
            _showMessage(data['error']?['message'] ?? 'Scan failed', Colors.red);
          }
        }
      } catch (e) {
        _showMessage('Network error', Colors.red);
      } finally {
        await Future.delayed(const Duration(seconds: 2));
        if (mounted) {
          setState(() {
            _isProcessing = false;
          });
          _scannerController.start();
        }
      }
    }
  }

  void _showMessage(String message, Color color) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: color,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Scan Parcel')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: DropdownButton<String>(
              value: _selectedEvent,
              isExpanded: true,
              items: _eventTypes.map((type) {
                return DropdownMenuItem(value: type, child: Text(type));
              }).toList(),
              onChanged: (val) {
                if (val != null) {
                  setState(() => _selectedEvent = val);
                }
              },
            ),
          ),
          Expanded(
            child: Stack(
              children: [
                MobileScanner(
                  controller: _scannerController,
                  onDetect: _onDetect,
                ),
                if (_isProcessing)
                  Container(
                    color: Colors.black54,
                    child: const Center(
                      child: CircularProgressIndicator(),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
