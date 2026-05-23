import 'package:flutter/material.dart';

class ParcelDetailsScreen extends StatelessWidget {
  final String parcelId;
  
  const ParcelDetailsScreen({super.key, required this.parcelId});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Parcel Details'),
      ),
      body: Center(
        child: Text('Parcel Details for ID: $parcelId'),
      ),
    );
  }
}
