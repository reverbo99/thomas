import 'package:flutter/material.dart';

import '../../core/strings.dart';
import '../../core/ui_errors.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/primary_button.dart';

/// Registration result after a successful [RegisterPage.onRegister].
class RegisterResult {
  const RegisterResult({required this.userName, this.email});

  final String userName;
  final String? email;
}

/// Name, email, phone, password + confirm. API via [onRegister] callback.
class RegisterPage extends StatefulWidget {
  const RegisterPage({
    super.key,
    required this.onRegister,
    this.onLoginTap,
  });

  final Future<RegisterResult> Function({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) onRegister;

  final VoidCallback? onLoginTap;

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmController = TextEditingController();

  bool _obscurePassword = true;
  bool _obscureConfirm = true;
  bool _isLoading = false;
  String? _bannerError;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  String? _validateEmail(String? value) {
    final email = value?.trim() ?? '';
    if (email.isEmpty) return AppStrings.emailRequired;
    final emailRegex = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');
    if (!emailRegex.hasMatch(email)) return AppStrings.emailInvalid;
    return null;
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() => _bannerError = null);
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _isLoading = true);
    try {
      await widget.onRegister(
        name: _nameController.text.trim(),
        email: _emailController.text.trim(),
        phone: _phoneController.text.trim(),
        password: _passwordController.text,
        passwordConfirmation: _confirmController.text,
      );
    } catch (e) {
      final message = formatUiError(e);
      if (!mounted) return;
      setState(() => _bannerError = message);
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(message)));
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.registerTitle)),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 400),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      AppStrings.registerSubtitle,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                    ),
                    if (_bannerError != null) ...[
                      const SizedBox(height: 16),
                      ErrorBanner(
                        message: _bannerError!,
                        onDismiss: () => setState(() => _bannerError = null),
                      ),
                    ],
                    const SizedBox(height: 20),
                    TextFormField(
                      controller: _nameController,
                      enabled: !_isLoading,
                      textInputAction: TextInputAction.next,
                      textCapitalization: TextCapitalization.words,
                      decoration: const InputDecoration(
                        labelText: AppStrings.nameLabel,
                        prefixIcon: Icon(Icons.person_outline),
                      ),
                      validator: (v) =>
                          (v == null || v.trim().isEmpty)
                              ? AppStrings.nameRequired
                              : null,
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _emailController,
                      enabled: !_isLoading,
                      keyboardType: TextInputType.emailAddress,
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.email],
                      decoration: const InputDecoration(
                        labelText: AppStrings.emailLabel,
                        prefixIcon: Icon(Icons.email_outlined),
                      ),
                      validator: _validateEmail,
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _phoneController,
                      enabled: !_isLoading,
                      keyboardType: TextInputType.phone,
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.telephoneNumber],
                      decoration: const InputDecoration(
                        labelText: AppStrings.phoneLabel,
                        prefixIcon: Icon(Icons.phone_outlined),
                        hintText: '+2557…',
                      ),
                      validator: (v) =>
                          (v == null || v.trim().isEmpty)
                              ? AppStrings.phoneRequired
                              : null,
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _passwordController,
                      enabled: !_isLoading,
                      obscureText: _obscurePassword,
                      textInputAction: TextInputAction.next,
                      decoration: InputDecoration(
                        labelText: AppStrings.passwordLabel,
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          onPressed: _isLoading
                              ? null
                              : () => setState(
                                    () => _obscurePassword = !_obscurePassword,
                                  ),
                          icon: Icon(
                            _obscurePassword
                                ? Icons.visibility_outlined
                                : Icons.visibility_off_outlined,
                          ),
                        ),
                      ),
                      validator: (v) {
                        if (v == null || v.isEmpty) {
                          return AppStrings.passwordRequired;
                        }
                        if (v.length < 6) return AppStrings.passwordTooShort;
                        return null;
                      },
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _confirmController,
                      enabled: !_isLoading,
                      obscureText: _obscureConfirm,
                      textInputAction: TextInputAction.done,
                      decoration: InputDecoration(
                        labelText: AppStrings.confirmPasswordLabel,
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          onPressed: _isLoading
                              ? null
                              : () => setState(
                                    () => _obscureConfirm = !_obscureConfirm,
                                  ),
                          icon: Icon(
                            _obscureConfirm
                                ? Icons.visibility_outlined
                                : Icons.visibility_off_outlined,
                          ),
                        ),
                      ),
                      validator: (v) {
                        if (v != _passwordController.text) {
                          return AppStrings.passwordMismatch;
                        }
                        return null;
                      },
                      onFieldSubmitted: (_) => _submit(),
                    ),
                    const SizedBox(height: 24),
                    PrimaryButton(
                      label: AppStrings.registerCta,
                      isLoading: _isLoading,
                      onPressed: _isLoading ? null : _submit,
                    ),
                    const SizedBox(height: 12),
                    TextButton(
                      onPressed: widget.onLoginTap ??
                          () => Navigator.of(context).maybePop(),
                      child: const Text(AppStrings.haveAccount),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
