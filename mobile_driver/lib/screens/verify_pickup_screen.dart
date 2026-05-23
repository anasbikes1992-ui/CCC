import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../config/app_theme.dart';
import '../config/app_config.dart';
import '../models/parcel.dart';
import '../services/driver_api_service.dart';

class VerifyPickupScreen extends StatefulWidget {
  final Parcel parcel;

  const VerifyPickupScreen({super.key, required this.parcel});

  @override
  State<VerifyPickupScreen> createState() => _VerifyPickupScreenState();
}

class _VerifyPickupScreenState extends State<VerifyPickupScreen> {
  final _otpController = TextEditingController();
  final _otpFocusNode = FocusNode();
  bool _isVerifying = false;
  bool _isRegenerating = false;
  String? _errorMessage;
  int? _attemptsLeft;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _otpFocusNode.requestFocus();
    });
  }

  @override
  void dispose() {
    _otpController.dispose();
    _otpFocusNode.dispose();
    super.dispose();
  }

  Future<void> _verifyOTP() async {
    final otp = _otpController.text.trim();
    
    if (otp.length != 6) {
      setState(() {
        _errorMessage = 'Please enter a valid 6-digit OTP';
      });
      return;
    }

    setState(() {
      _isVerifying = true;
      _errorMessage = null;
    });

    try {
      final result = await DriverApiService().verifyPickupOTP(
        parcelId: widget.parcel.id,
        otp: otp,
      );

      if (!mounted) return;

      if (result['success'] == true) {
        // OTP verified successfully, proceed to delivery screen
        Navigator.of(context).pushReplacementNamed(
          '/complete-delivery',
          arguments: widget.parcel,
        );
      } else {
        setState(() {
          _errorMessage = result['error']['message'] ?? 'Invalid OTP';
          _attemptsLeft = result['error']['details']?['attempts_left'];
          _isVerifying = false;
          _otpController.clear();
          _otpFocusNode.requestFocus();
        });
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _errorMessage = 'Verification failed: ${e.toString()}';
        _isVerifying = false;
        _otpController.clear();
      });
    }
  }

  Future<void> _regenerateOTP() async {
    setState(() {
      _isRegenerating = true;
      _errorMessage = null;
    });

    try {
      await DriverApiService().regeneratePickupOTP(
        parcelId: widget.parcel.id,
      );

      if (!mounted) return;

      setState(() {
        _isRegenerating = false;
        _attemptsLeft = null;
        _otpController.clear();
        _otpFocusNode.requestFocus();
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('New OTP has been sent to the receiver'),
          backgroundColor: AppTheme.success,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _errorMessage = 'Failed to regenerate OTP: ${e.toString()}';
        _isRegenerating = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Verify Pickup OTP'),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppTheme.spaceLg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildParcelSummary(),
            const SizedBox(height: AppTheme.spaceLg),
            _buildInstructions(),
            const SizedBox(height: AppTheme.spaceLg),
            _buildOTPInput(),
            if (_errorMessage != null) ...[
              const SizedBox(height: AppTheme.spaceSm),
              _buildErrorMessage(),
            ],
            if (_attemptsLeft != null && _attemptsLeft! > 0) ...[
              const SizedBox(height: AppTheme.spaceSm),
              _buildAttemptsRemaining(),
            ],
            const SizedBox(height: AppTheme.spaceLg),
            _buildVerifyButton(),
            const SizedBox(height: AppTheme.spaceMd),
            _buildRegenerateButton(),
          ],
        ),
      ),
    );
  }

  Widget _buildParcelSummary() {
    return Card(
      elevation: 0,
      color: AppTheme.surfaceLight,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppTheme.radiusMd),
        side: const BorderSide(color: AppTheme.borderLight),
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppTheme.spaceMd),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Parcel Details',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: AppTheme.spaceSm),
            _buildDetailRow('Tracking Number', widget.parcel.parcelNumber),
            _buildDetailRow('Receiver', widget.parcel.receiverName),
            _buildDetailRow('Phone', widget.parcel.receiverPhone),
            _buildDetailRow('Size', widget.parcel.size),
            _buildDetailRow('Weight', '${widget.parcel.weightKg} kg'),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 14,
              color: AppTheme.textSecondary,
            ),
          ),
          Text(
            value,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: AppTheme.textPrimary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInstructions() {
    return Container(
      padding: const EdgeInsets.all(AppTheme.spaceMd),
      decoration: BoxDecoration(
        color: AppTheme.info.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(AppTheme.radiusSm),
        border: Border.all(color: AppTheme.info.withValues(alpha: 0.3)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            Icons.info_outline,
            color: AppTheme.info,
            size: 20,
          ),
          const SizedBox(width: AppTheme.spaceSm),
          Expanded(
            child: Text(
              'Ask the receiver to provide the 6-digit OTP code that was sent to their WhatsApp number: ${widget.parcel.receiverPhone}',
              style: TextStyle(
                fontSize: 13,
                color: AppTheme.info.darker(20),
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildOTPInput() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Enter OTP Code',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: AppTheme.textPrimary,
          ),
        ),
        const SizedBox(height: AppTheme.spaceSm),
        TextField(
          controller: _otpController,
          focusNode: _otpFocusNode,
          keyboardType: TextInputType.number,
          textAlign: TextAlign.center,
          maxLength: 6,
          style: const TextStyle(
            fontSize: 32,
            fontWeight: FontWeight.w600,
            letterSpacing: 8,
            fontFamily: 'monospace',
          ),
          decoration: InputDecoration(
            hintText: '000000',
            counterText: '',
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppTheme.radiusMd),
              borderSide: const BorderSide(color: AppTheme.borderLight, width: 2),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppTheme.radiusMd),
              borderSide: const BorderSide(color: AppTheme.borderLight, width: 2),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppTheme.radiusMd),
              borderSide: const BorderSide(color: AppTheme.primary, width: 2),
            ),
            errorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppTheme.radiusMd),
              borderSide: const BorderSide(color: AppTheme.danger, width: 2),
            ),
            filled: true,
            fillColor: Colors.white,
            contentPadding: const EdgeInsets.symmetric(vertical: AppTheme.spaceLg),
          ),
          inputFormatters: [
            FilteringTextInputFormatter.digitsOnly,
            LengthLimitingTextInputFormatter(6),
          ],
          onChanged: (value) {
            if (value.length == 6) {
              _verifyOTP();
            }
          },
          onSubmitted: (_) => _verifyOTP(),
        ),
      ],
    );
  }

  Widget _buildErrorMessage() {
    return Container(
      padding: const EdgeInsets.all(AppTheme.spaceSm),
      decoration: BoxDecoration(
        color: AppTheme.danger.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(AppTheme.radiusSm),
        border: Border.all(color: AppTheme.danger.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.error_outline,
            color: AppTheme.danger,
            size: 20,
          ),
          const SizedBox(width: AppTheme.spaceSm),
          Expanded(
            child: Text(
              _errorMessage!,
              style: const TextStyle(
                fontSize: 13,
                color: AppTheme.danger,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAttemptsRemaining() {
    return Container(
      padding: const EdgeInsets.all(AppTheme.spaceSm),
      decoration: BoxDecoration(
        color: AppTheme.warning.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(AppTheme.radiusSm),
        border: Border.all(color: AppTheme.warning.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.warning_amber_outlined,
            color: AppTheme.warning,
            size: 20,
          ),
          const SizedBox(width: AppTheme.spaceSm),
          Expanded(
            child: Text(
              '${_attemptsLeft!} attempt${_attemptsLeft! > 1 ? 's' : ''} remaining',
              style: const TextStyle(
                fontSize: 13,
                color: AppTheme.warning,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildVerifyButton() {
    return ElevatedButton(
      onPressed: _isVerifying || _isRegenerating ? null : _verifyOTP,
      style: ElevatedButton.styleFrom(
        padding: const EdgeInsets.symmetric(vertical: AppTheme.spaceMd),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppTheme.radiusMd),
        ),
      ),
      child: _isVerifying
          ? const SizedBox(
              height: 20,
              width: 20,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
              ),
            )
          : const Text(
              'Verify OTP',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
    );
  }

  Widget _buildRegenerateButton() {
    return OutlinedButton.icon(
      onPressed: _isVerifying || _isRegenerating ? null : _regenerateOTP,
      style: OutlinedButton.styleFrom(
        padding: const EdgeInsets.symmetric(vertical: AppTheme.spaceMd),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppTheme.radiusMd),
        ),
        side: const BorderSide(color: AppTheme.primary),
      ),
      icon: _isRegenerating
          ? const SizedBox(
              height: 16,
              width: 16,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primary),
              ),
            )
          : const Icon(Icons.refresh),
      label: const Text(
        'Regenerate OTP',
        style: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }
}

// Extension for color darkening
extension ColorExtension on Color {
  Color darker(int percent) {
    assert(1 <= percent && percent <= 100);
    final f = 1 - percent / 100;
    return Color.fromARGB(
      alpha,
      (red * f).round(),
      (green * f).round(),
      (blue * f).round(),
    );
  }
}
