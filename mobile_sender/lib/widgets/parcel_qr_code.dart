import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../config/app_theme.dart';
import '../config/app_config.dart';
import '../models/parcel.dart';

class ParcelQRCode extends StatelessWidget {
  final Parcel parcel;

  const ParcelQRCode({super.key, required this.parcel});

  @override
  Widget build(BuildContext context) {
    final trackingUrl =
        '${AppConfig.webTrackingUrl}/track/${parcel.parcelNumber}';

    return Card(
      elevation: 0,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppTheme.radiusMd),
        side: const BorderSide(color: AppTheme.borderLight),
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppTheme.spaceLg),
        child: Column(
          children: [
            const Text(
              'Scan to Track',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: AppTheme.spaceMd),
            Container(
              padding: const EdgeInsets.all(AppTheme.spaceMd),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(AppTheme.radiusSm),
                border: Border.all(color: AppTheme.borderLight, width: 2),
              ),
              child: QrImageView(
                data: trackingUrl,
                version: QrVersions.auto,
                size: 200.0,
                backgroundColor: Colors.white,
                foregroundColor: Colors.black,
                errorCorrectionLevel: QrErrorCorrectLevel.H,
                embeddedImage: const AssetImage('assets/logo.png'),
                embeddedImageStyle: const QrEmbeddedImageStyle(
                  size: Size(40, 40),
                ),
              ),
            ),
            const SizedBox(height: AppTheme.spaceMd),
            Text(
              parcel.parcelNumber,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w500,
                fontFamily: 'monospace',
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: AppTheme.spaceSm),
            const Text(
              'Show this QR code at pickup/delivery points',
              style: TextStyle(
                fontSize: 12,
                color: AppTheme.textSecondary,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppTheme.spaceMd),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _ActionButton(
                  icon: Icons.download,
                  label: 'Download',
                  onTap: () => _downloadQR(context),
                ),
                _ActionButton(
                  icon: Icons.share,
                  label: 'Share',
                  onTap: () => _shareQR(context),
                ),
                _ActionButton(
                  icon: Icons.print,
                  label: 'Print',
                  onTap: () => _printQR(context),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _downloadQR(BuildContext context) {
    // TODO: Implement QR download
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Download feature coming soon')),
    );
  }

  void _shareQR(BuildContext context) {
    // TODO: Implement QR sharing
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Share feature coming soon')),
    );
  }

  void _printQR(BuildContext context) {
    // TODO: Implement QR printing
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Print feature coming soon')),
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _ActionButton({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppTheme.radiusSm),
      child: Padding(
        padding: const EdgeInsets.symmetric(
          horizontal: AppTheme.spaceMd,
          vertical: AppTheme.spaceSm,
        ),
        child: Column(
          children: [
            Icon(icon, color: AppTheme.primary, size: 24),
            const SizedBox(height: 4),
            Text(
              label,
              style: const TextStyle(
                fontSize: 12,
                color: AppTheme.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
