import 'package:flutter/material.dart';
import '../config/app_theme.dart';
import '../models/parcel.dart';
import 'package:intl/intl.dart';

class ParcelTimeline extends StatelessWidget {
  final Parcel parcel;

  const ParcelTimeline({super.key, required this.parcel});

  @override
  Widget build(BuildContext context) {
    final statusEvents = _getStatusEvents();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.all(AppTheme.spaceMd),
          child: Text(
            'Tracking Timeline',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
          ),
        ),
        ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: statusEvents.length,
          itemBuilder: (context, index) {
            final event = statusEvents[index];
            final isLast = index == statusEvents.length - 1;
            final isActive = event['isActive'] as bool;
            final isPast = event['isPast'] as bool;

            return _TimelineItem(
              title: event['title'] as String,
              subtitle: event['subtitle'] as String,
              timestamp: event['timestamp'] as String?,
              isLast: isLast,
              isActive: isActive,
              isPast: isPast,
            );
          },
        ),
      ],
    );
  }

  List<Map<String, dynamic>> _getStatusEvents() {
    final currentStatus = parcel.status;
    final statusOrder = [
      'BOOKED',
      'LABEL_PRINTED',
      'PICKED_UP',
      'RECEIVED_AT_ORIGIN_HUB',
      'LOADED_ON_LORRY',
      'IN_TRANSIT',
      'ARRIVED_AT_DESTINATION_HUB',
      'OUT_FOR_DELIVERY',
      'DELIVERED',
    ];

    final currentIndex = statusOrder.indexOf(currentStatus);

    return statusOrder.asMap().entries.map((entry) {
      final index = entry.key;
      final status = entry.value;

      final isPast = index < currentIndex;
      final isActive = index == currentIndex;

      // Get timestamp from status history if available
      String? timestamp;
      final historyEvent = parcel.statusHistory.firstWhere(
        (e) => e['status'] == status,
        orElse: () => {},
      );

      if (historyEvent.isNotEmpty && historyEvent['timestamp'] != null) {
        final dt = DateTime.parse(historyEvent['timestamp'] as String);
        timestamp = DateFormat('MMM dd, yyyy h:mm a').format(dt.toLocal());
      }

      return {
        'title': _getStatusTitle(status),
        'subtitle': _getStatusSubtitle(status),
        'timestamp': timestamp,
        'isActive': isActive,
        'isPast': isPast,
      };
    }).toList();
  }

  String _getStatusTitle(String status) {
    switch (status) {
      case 'BOOKED':
        return 'Booking Confirmed';
      case 'LABEL_PRINTED':
        return 'Label Printed';
      case 'PICKED_UP':
        return 'Picked Up';
      case 'RECEIVED_AT_ORIGIN_HUB':
        return 'At Origin Hub';
      case 'LOADED_ON_LORRY':
        return 'Loaded on Vehicle';
      case 'IN_TRANSIT':
        return 'In Transit';
      case 'ARRIVED_AT_DESTINATION_HUB':
        return 'At Destination Hub';
      case 'OUT_FOR_DELIVERY':
        return 'Out for Delivery';
      case 'DELIVERED':
        return 'Delivered';
      default:
        return status;
    }
  }

  String _getStatusSubtitle(String status) {
    switch (status) {
      case 'BOOKED':
        return 'Your parcel has been booked';
      case 'LABEL_PRINTED':
        return 'Shipping label generated';
      case 'PICKED_UP':
        return 'Parcel collected from sender';
      case 'RECEIVED_AT_ORIGIN_HUB':
        return 'Arrived at origin facility';
      case 'LOADED_ON_LORRY':
        return 'Loaded on delivery vehicle';
      case 'IN_TRANSIT':
        return 'On the way to destination';
      case 'ARRIVED_AT_DESTINATION_HUB':
        return 'Reached destination hub';
      case 'OUT_FOR_DELIVERY':
        return 'Driver is on the way';
      case 'DELIVERED':
        return 'Successfully delivered';
      default:
        return '';
    }
  }
}

class _TimelineItem extends StatelessWidget {
  final String title;
  final String subtitle;
  final String? timestamp;
  final bool isLast;
  final bool isActive;
  final bool isPast;

  const _TimelineItem({
    required this.title,
    required this.subtitle,
    this.timestamp,
    required this.isLast,
    required this.isActive,
    required this.isPast,
  });

  @override
  Widget build(BuildContext context) {
    final iconColor = isActive
        ? AppTheme.primary
        : isPast
            ? AppTheme.success
            : AppTheme.textLight;

    final lineColor = isPast ? AppTheme.success : AppTheme.borderLight;

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Timeline indicator
          Column(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: isActive || isPast
                      ? iconColor.withValues(alpha: 0.1)
                      : AppTheme.surfaceLight,
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: iconColor,
                    width: isActive ? 3 : 2,
                  ),
                ),
                child: Icon(
                  isPast
                      ? Icons.check
                      : isActive
                          ? Icons.circle
                          : Icons.circle_outlined,
                  color: iconColor,
                  size: isActive ? 12 : 20,
                ),
              ),
              if (!isLast)
                Expanded(
                  child: Container(
                    width: 2,
                    color: lineColor,
                    margin: const EdgeInsets.symmetric(vertical: 4),
                  ),
                ),
            ],
          ),
          const SizedBox(width: AppTheme.spaceMd),
          // Content
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(bottom: AppTheme.spaceLg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: isActive ? FontWeight.w600 : FontWeight.w500,
                      color: isActive || isPast
                          ? AppTheme.textPrimary
                          : AppTheme.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 14,
                      color: AppTheme.textSecondary,
                    ),
                  ),
                  if (timestamp != null) ...[
                    const SizedBox(height: 4),
                    Text(
                      timestamp!,
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppTheme.textLight,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
