import 'package:flutter/material.dart';
import '../api_service.dart';
import 'parcel_detail_screen.dart';

class ParcelListScreen extends StatefulWidget {
  const ParcelListScreen({super.key});

  @override
  State<ParcelListScreen> createState() => _ParcelListScreenState();
}

class _ParcelListScreenState extends State<ParcelListScreen> {
  List<Map<String, dynamic>> _parcels = [];
  bool _isLoading = true;
  String _filter = 'all'; // all, active, delivered

  @override
  void initState() {
    super.initState();
    _loadParcels();
  }

  Future<void> _loadParcels() async {
    setState(() => _isLoading = true);

    try {
      final response = await ApiService.get('/customer/parcels?filter=$_filter');
      if (!mounted) return;

      if (response['success'] == true) {
        setState(() {
          _parcels = List<Map<String, dynamic>>.from(response['data'] ?? []);
        });
      } else {
        setState(() {
          _parcels = [];
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _parcels = [];
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
    return Scaffold(
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: 'all', label: Text('All')),
                ButtonSegment(value: 'active', label: Text('Active')),
                ButtonSegment(value: 'delivered', label: Text('Delivered')),
              ],
              selected: {_filter},
              onSelectionChanged: (Set<String> newSelection) {
                setState(() {
                  _filter = newSelection.first;
                  _loadParcels();
                });
              },
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _parcels.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.inbox,
                              size: 80,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              'No parcels found',
                              style: TextStyle(
                                fontSize: 18,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadParcels,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(8.0),
                          itemCount: _parcels.length,
                          itemBuilder: (context, index) {
                            final parcel = _parcels[index];
                            return Card(
                              margin: const EdgeInsets.only(bottom: 8.0),
                              child: ListTile(
                                leading: CircleAvatar(
                                  backgroundColor: _getStatusColor(parcel['status']).withOpacity(0.2),
                                  child: Icon(
                                    Icons.local_shipping,
                                    color: _getStatusColor(parcel['status']),
                                  ),
                                ),
                                title: Text(
                                  parcel['parcel_number'] ?? 'N/A',
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                                subtitle: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text('To: ${parcel['receiver_name'] ?? 'Receiver'}'),
                                    const SizedBox(height: 4),
                                    Row(
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 8,
                                            vertical: 2,
                                          ),
                                          decoration: BoxDecoration(
                                            color: _getStatusColor(parcel['status']),
                                            borderRadius: BorderRadius.circular(12),
                                          ),
                                          child: Text(
                                            parcel['status'].toString().replaceAll('_', ' ').toUpperCase(),
                                            style: const TextStyle(
                                              color: Colors.white,
                                              fontSize: 10,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                                trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                                onTap: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => ParcelDetailScreen(
                                        parcelId: parcel['id'],
                                      ),
                                    ),
                                  );
                                },
                              ),
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}
