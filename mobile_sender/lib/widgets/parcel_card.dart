import 'package:flutter/material.dart';
import '../config/app_theme.dart';
import '../models/parcel.dart';
import 'package:intl/intl.dart';

class ParcelCard extends StatelessWidget {
  final Parcel parcel;
  final VoidCallback onTap;

  const ParcelCard({super.key, required this.parcel, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: AppTheme.spaceMd),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppTheme.radiusMd),
        child: Padding(
          padding: const EdgeInsets.all(AppTheme.spaceMd),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  _buildStatusBadge(),
                  const Spacer(),
                  Text(
                    parcel.parcelNumber,
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12),
                  ),
                ],
              ),
              const SizedBox(height: AppTheme.spaceMd),
              Row(
                children: [
                  const Icon(Icons.location_on_outlined, size: 16, color: AppTheme.textSecondary),
                  const SizedBox(width: AppTheme.spaceXs),
                  Expanded(
                    child: Text(
                      parcel.routeDisplay,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppTheme.spaceSm),
              Row(
                children: [
                  const Icon(Icons.person_outline, size: 16, color: AppTheme.textSecondary),
                  const SizedBox(width: AppTheme.spaceXs),
                  Expanded(
                    child: Text(
                      '${parcel.senderName} → ${parcel.receiverName}',
                      style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppTheme.spaceSm),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Size: ${parcel.size} • ${parcel.weight}kg',
                    style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                  ),
                  Text(
                    'LKR ${NumberFormat('#,###').format(parcel.finalFee)}',
                    style: const TextStyle(fontWeight: FontWeight.w600, color: AppTheme.primary),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusBadge() {
    Color bgColor;
    Color textColor;
    
    switch (parcel.statusColor) {
      case 'success':
        bgColor = AppTheme.success.withValues(alpha: 0.1);
        textColor = AppTheme.success;
        break;
      case 'error':
        bgColor = AppTheme.error.withValues(alpha: 0.1);
        textColor = AppTheme.error;
        break;
      case 'warning':
        bgColor = AppTheme.warning.withValues(alpha: 0.1);
        textColor = AppTheme.warning;
        break;
      default:
        bgColor = AppTheme.info.withValues(alpha: 0.1);
        textColor = AppTheme.info;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(AppTheme.radiusSm),
      ),
      child: Text(
        parcel.statusLabel ?? parcel.status,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: textColor,
        ),
      ),
    );
  }
}
