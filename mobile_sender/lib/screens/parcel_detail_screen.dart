import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../api_service.dart';

class ParcelDetailScreen extends StatefulWidget {
  final String parcelId;

  const ParcelDetailScreen({super.key, required this.parcelId});

  @override
  State<ParcelDetailScreen> createState() => _ParcelDetailScreenState();
}

class _ParcelDetailScreenState extends State<ParcelDetailScreen> {
  Map<String, dynamic>? _parcel;
  List<Map<String, dynamic>> _events = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadParcelDetails();
  }

  Future<void> _loadParcelDetails() async {
    setState(() => _isLoading = true);

    try {
      final response = await ApiService.get(
        '/customer/parcels/${widget.parcelId}',
      );
      if (!mounted) return;

      if (response['success'] == true) {
        setState(() {
          _parcel = response['data']['parcel'];
          _events = List<Map<String, dynamic>>.from(
            response['data']['events'] ?? [],
          );
        });
      } else {
        setState(() {
          _parcel = null;
          _events = [];
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _parcel = null;
          _events = [];
        });
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'delivered':
        return Colors.green;
      case 'in_transit':
      case 'out_for_delivery':
        return Colors.orange;
      case 'cancelled':
      case 'delivery_failed':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: const Text('Parcel Details')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_parcel == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Parcel Details')),
        body: const Center(child: Text('Parcel not found')),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Parcel Details'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadParcelDetails,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadParcelDetails,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // QR Code
              Center(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.grey.withOpacity(0.2),
                        blurRadius: 8,
                        spreadRadius: 2,
                      ),
                    ],
                  ),
                  child: QrImageView(
                    data: _parcel!['qr_token'] ?? '',
                    version: QrVersions.auto,
                    size: 200.0,
                  ),
                ),
              ),
              const SizedBox(height: 24),

              // Parcel Number
              Center(
                child: Text(
                  _parcel!['parcel_number'] ?? 'N/A',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(height: 8),

              // Status Badge
              Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: _getStatusColor(_parcel!['status']),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    _parcel!['status']
                        .toString()
                        .replaceAll('_', ' ')
                        .toUpperCase(),
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 24),

              // Details Card
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildDetailRow('Receiver', _parcel!['receiver_name']),
                      _buildDetailRow('Phone', _parcel!['receiver_phone']),
                      _buildDetailRow(
                        'Route',
                        _parcel!['route']?['code'] ?? _parcel!['route_code'],
                      ),
                      _buildDetailRow(
                        'Size',
                        _parcel!['package_size']?['code'] ??
                            _parcel!['package_size_code'],
                      ),
                      _buildDetailRow(
                        'Price',
                        'LKR ${_parcel!['price']?['total_lkr'] ?? _parcel!['total_price_lkr'] ?? '0'}',
                      ),
                      _buildDetailRow('Pickup', _parcel!['pickup_address']),
                      _buildDetailRow('Drop', _parcel!['drop_address']),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),

              // Tracking Timeline
              Text(
                'Tracking History',
                style: Theme.of(
                  context,
                ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),

              ..._events.map((event) => _buildTimelineItem(event)).toList(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetailRow(String label, String? value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(
                color: Colors.grey[600],
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value ?? 'N/A',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTimelineItem(Map<String, dynamic> event) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 12,
              height: 12,
              decoration: BoxDecoration(
                color: Colors.blue,
                shape: BoxShape.circle,
              ),
            ),
            Container(width: 2, height: 40, color: Colors.grey[300]),
          ],
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                event['event_type']
                    .toString()
                    .replaceAll('_', ' ')
                    .toUpperCase(),
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              Text(
                event['occurred_at'] ?? event['created_at'] ?? '',
                style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              ),
              if (event['notes'] != null)
                Text(
                  event['notes'],
                  style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                ),
            ],
          ),
        ),
      ],
    );
  }
}
