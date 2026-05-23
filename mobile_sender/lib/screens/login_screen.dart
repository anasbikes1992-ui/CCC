import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../config/app_theme.dart';
import '../providers/auth_provider.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _phoneController = TextEditingController();
  final _otpController = TextEditingController();
  bool _otpSent = false;

  @override
  void dispose() {
    _phoneController.dispose();
    _otpController.dispose();
    super.dispose();
  }

  Future<void> _sendOtp() async {
    final phone = _phoneController.text.trim();
    if (phone.isEmpty) {
      _showError('Please enter phone number');
      return;
    }

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final success = await authProvider.login(phone);
    
    if (success && mounted) {
      setState(() => _otpSent = true);
      _showSuccess('OTP sent to $phone');
    } else if (mounted) {
      _showError(authProvider.error ?? 'Failed to send OTP');
    }
  }

  Future<void> _verifyOtp() async {
    final phone = _phoneController.text.trim();
    final otp = _otpController.text.trim();
    
    if (otp.isEmpty) {
      _showError('Please enter OTP');
      return;
    }

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final success = await authProvider.verifyOtp(phone, otp);
    
    if (success && mounted) {
      Navigator.of(context).pushReplacementNamed('/dashboard');
    } else if (mounted) {
      _showError(authProvider.error ?? 'Invalid OTP');
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: AppTheme.error),
    );
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: AppTheme.success),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppTheme.spaceLg),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: AppTheme.spaceXxl),
              _buildHeader(),
              const SizedBox(height: AppTheme.spaceXl),
              _buildForm(),
              const SizedBox(height: AppTheme.spaceLg),
              _buildActionButton(),
              const SizedBox(height: AppTheme.spaceMd),
              if (_otpSent) _buildResendButton(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Column(
      children: [
        Container(
          width: 80,
          height: 80,
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [AppTheme.primary, AppTheme.primaryDark],
            ),
            borderRadius: BorderRadius.circular(AppTheme.radiusLg),
          ),
          child: const Icon(Icons.local_shipping_rounded, size: 48, color: Colors.white),
        ),
        const SizedBox(height: AppTheme.spaceMd),
        const Text(
          'Welcome Back',
          style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: AppTheme.spaceXs),
        const Text(
          'Login to your CCC account',
          style: TextStyle(fontSize: 16, color: AppTheme.textSecondary),
        ),
      ],
    );
  }

  Widget _buildForm() {
    return Column(
      children: [
        TextField(
          controller: _phoneController,
          decoration: const InputDecoration(
            labelText: 'Phone Number',
            hintText: '+94771234567',
            prefixIcon: Icon(Icons.phone),
          ),
          keyboardType: TextInputType.phone,
          enabled: !_otpSent,
        ),
        if (_otpSent) ...[
          const SizedBox(height: AppTheme.spaceMd),
          TextField(
            controller: _otpController,
            decoration: const InputDecoration(
              labelText: 'OTP Code',
              hintText: '6-digit code',
              prefixIcon: Icon(Icons.lock),
            ),
            keyboardType: TextInputType.number,
            maxLength: 6,
          ),
        ],
      ],
    );
  }

  Widget _buildActionButton() {
    return Consumer<AuthProvider>(
      builder: (context, auth, _) {
        return ElevatedButton(
          onPressed: auth.isLoading
              ? null
              : (_otpSent ? _verifyOtp : _sendOtp),
          child: auth.isLoading
              ? const SizedBox(
                  height: 20,
                  width: 20,
                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                )
              : Text(_otpSent ? 'Verify OTP' : 'Send OTP'),
        );
      },
    );
  }

  Widget _buildResendButton() {
    return TextButton(
      onPressed: _sendOtp,
      child: const Text('Resend OTP'),
    );
  }
}
