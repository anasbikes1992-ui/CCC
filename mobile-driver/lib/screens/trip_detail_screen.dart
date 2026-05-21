import 'package:flutter/material.dart';
import '../api_service.dart';
import 'scan_screen.dart';

class TripDetailScreen extends StatefulWidget {
  final String tripId;

  const TripDetailScreen({super.key, required this.tripId});

  @override
  State<TripDetailScreen> createState() => _TripDetailScreenState();
}

class _TripDetailScreenState extends State<TripDetailScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _trip;
  List<dynamic> _parcels = [];
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchTripDetails();
  }

  Future<void> _fetchTripDetails() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await ApiService.get('/driver/trips/${widget.tripId}/parcels');
      if (response['success'] == true) {
        setState(() {
          _trip = response['data']['trip'];
          _parcels = response['data']['items'];
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = 'Failed to load trip details';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_trip != null ? _trip!['trip_code'] : 'Trip Details'),
      ),
      body: _buildBody(),
      floatingActionButton: _trip != null ? FloatingActionButton.extended(
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => ScanScreen(tripId: widget.tripId),
            ),
          ).then((_) => _fetchTripDetails()); // Refresh after returning from scan
        },
        icon: const Icon(Icons.qr_code_scanner),
        label: const Text('Scan Parcel'),
      ) : null,
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_errorMessage != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(_errorMessage!, style: const TextStyle(color: Colors.red)),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _fetchTripDetails,
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    if (_parcels.isEmpty) {
      return const Center(child: Text('No parcels assigned to this trip.'));
    }

    return RefreshIndicator(
      onRefresh: _fetchTripDetails,
      child: ListView.builder(
        itemCount: _parcels.length,
        itemBuilder: (context, index) {
          final parcel = _parcels[index];
          return Card(
            margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: ListTile(
              title: Text(parcel['parcel_number']),
              subtitle: Text('Status: ${parcel['status']}\nReceiver: ${parcel['receiver_name']} (${parcel['receiver_phone']})'),
              trailing: _getStatusIcon(parcel['status']),
            ),
          );
        },
      ),
    );
  }

  Widget _getStatusIcon(String status) {
    switch (status) {
      case 'DELIVERED':
        return const Icon(Icons.check_circle, color: Colors.green);
      case 'LOADED_ON_LORRY':
        return const Icon(Icons.local_shipping, color: Colors.blue);
      case 'RECEIVED_AT_ORIGIN_HUB':
      case 'ARRIVED_AT_DESTINATION_HUB':
        return const Icon(Icons.warehouse, color: Colors.orange);
      default:
        return const Icon(Icons.inventory, color: Colors.grey);
    }
  }
}
