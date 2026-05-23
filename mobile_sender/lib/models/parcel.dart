class Parcel {
  final String id;
  final String parcelNumber;
  final String status;
  final String? statusLabel;
  final String routeCode;
  final String routeDisplay;
  final String size;
  final double weight;
  final double? length;
  final double? width;
  final double? height;
  final int baseFee;
  final int finalFee;
  final String senderName;
  final String senderPhone;
  final String receiverName;
  final String receiverPhone;
  final String? tripId;
  final DateTime? deliveredAt;
  final DateTime createdAt;
  final List<StatusEvent> statusHistory;

  Parcel({
    required this.id,
    required this.parcelNumber,
    required this.status,
    this.statusLabel,
    required this.routeCode,
    required this.routeDisplay,
    required this.size,
    required this.weight,
    this.length,
    this.width,
    this.height,
    required this.baseFee,
    required this.finalFee,
    required this.senderName,
    required this.senderPhone,
    required this.receiverName,
    required this.receiverPhone,
    this.tripId,
    this.deliveredAt,
    required this.createdAt,
    this.statusHistory = const [],
  });

  factory Parcel.fromJson(Map<String, dynamic> json) {
    return Parcel(
      id: json['id'],
      parcelNumber: json['parcel_number'],
      status: json['status'],
      statusLabel: json['status_label'],
      routeCode: json['route_code'],
      routeDisplay: json['route_display'] ?? '',
      size: json['size'],
      weight: (json['weight'] as num).toDouble(),
      length: json['length'] != null
          ? (json['length'] as num).toDouble()
          : null,
      width: json['width'] != null ? (json['width'] as num).toDouble() : null,
      height: json['height'] != null
          ? (json['height'] as num).toDouble()
          : null,
      baseFee: json['base_fee'],
      finalFee: json['final_fee'],
      senderName: json['sender_name'],
      senderPhone: json['sender_phone'],
      receiverName: json['receiver_name'],
      receiverPhone: json['receiver_phone'],
      tripId: json['trip_id'],
      deliveredAt: json['delivered_at'] != null
          ? DateTime.parse(json['delivered_at'])
          : null,
      createdAt: DateTime.parse(json['created_at']),
      statusHistory:
          (json['status_history'] as List?)
              ?.map((e) => StatusEvent.fromJson(e))
              .toList() ??
          [],
    );
  }

  String get statusColor {
    switch (status) {
      case 'DELIVERED':
        return 'success';
      case 'CANCELLED':
      case 'DELIVERY_FAILED':
        return 'error';
      case 'IN_TRANSIT':
      case 'OUT_FOR_DELIVERY':
        return 'warning';
      default:
        return 'info';
    }
  }

  bool get isDelivered => status == 'DELIVERED';
  bool get canCancel =>
      !['DELIVERED', 'CANCELLED', 'OUT_FOR_DELIVERY'].contains(status);
}

class StatusEvent {
  final String status;
  final String statusLabel;
  final DateTime timestamp;
  final String? location;
  final String? notes;

  StatusEvent({
    required this.status,
    required this.statusLabel,
    required this.timestamp,
    this.location,
    this.notes,
  });

  factory StatusEvent.fromJson(Map<String, dynamic> json) {
    return StatusEvent(
      status: json['status'],
      statusLabel: json['status_label'],
      timestamp: DateTime.parse(json['timestamp']),
      location: json['location'],
      notes: json['notes'],
    );
  }
}
