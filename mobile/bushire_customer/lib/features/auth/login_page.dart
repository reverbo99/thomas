import 'package:flutter/material.dart';

import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../widgets/primary_button.dart';

/// Result returned by [LoginPage.onLogin] after a successful sign-in.
class LoginResult {
  const LoginResult({required this.userName, this.email});

  final String userName;
  final String? email;
}

/// Email + password login. API calls stay outside — inject [onLogin]
/// (wired by [AuthGate] → [AuthRepository.login]).
class LoginPage extends StatefulWidget {
  const LoginPage({
    super.key,
    required this.onLogin,
    this.onRegisterTap,
  });

  /// Called after local validation passes. Throw or return a message via
  /// [Exception] / return `null` name patterns — prefer throwing with a
  /// user-readable [Exception.message], or return a [LoginResult].
  final Future<LoginResult> Function(String email, String password) onLogin;

  /// Optional register navigation; when null, shows disabled placeholder text.
  final VoidCallback? onRegisterTap;

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _emailFocus = FocusNode();
  final _passwordFocus = FocusNode();

  bool _obscurePassword = true;
  bool _isLoading = false;
  String? _bannerError;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _emailFocus.dispose();
    _passwordFocus.dispose();
    super.dispose();
  }

  String? _validateEmail(String? value) {
    final email = value?.trim() ?? '';
    if (email.isEmpty) return AppStrings.emailRequired;
    final emailRegex = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');
    if (!emailRegex.hasMatch(email)) return AppStrings.emailInvalid;
    return null;
  }

  String? _validatePassword(String? value) {
    final password = value ?? '';
    if (password.isEmpty) return AppStrings.passwordRequired;
    if (password.length < 6) return AppStrings.passwordTooShort;
    return null;
  }

  String _formatError(Object e) {
    if (e is ApiException) return e.message;
    if (e is Exception) {
      return e.toString().replaceFirst('Exception: ', '');
    }
    return e.toString();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() => _bannerError = null);

    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _isLoading = true);
    try {
      await widget.onLogin(
        _emailController.text.trim(),
        _passwordController.text,
      );
    } catch (e) {
      final message = _formatError(e);
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
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 400),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Icon(
                      Icons.airport_shuttle_rounded,
                      size: 64,
                      color: colorScheme.primary,
                    ),
                    const SizedBox(height: 16),
                    Text(
                      AppStrings.brandName,
                      textAlign: TextAlign.center,
                      style: theme.textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      AppStrings.brandTagline,
                      textAlign: TextAlign.center,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                    ),
                    const SizedBox(height: 36),
                    Text(
                      AppStrings.loginTitle,
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      AppStrings.loginSubtitle,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                    ),
                    if (_bannerError != null) ...[
                      const SizedBox(height: 16),
                      Material(
                        color: colorScheme.errorContainer,
                        borderRadius: BorderRadius.circular(12),
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(12, 10, 4, 10),
                          child: Row(
                            children: [
                              Icon(
                                Icons.error_outline,
                                color: colorScheme.onErrorContainer,
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  _bannerError!,
                                  style: TextStyle(
                                    color: colorScheme.onErrorContainer,
                                  ),
                                ),
                              ),
                              IconButton(
                                onPressed: () =>
                                    setState(() => _bannerError = null),
                                icon: Icon(
                                  Icons.close,
                                  color: colorScheme.onErrorContainer,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                    const SizedBox(height: 20),
                    TextFormField(
                      controller: _emailController,
                      focusNode: _emailFocus,
                      enabled: !_isLoading,
                      keyboardType: TextInputType.emailAddress,
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.email],
                      decoration: const InputDecoration(
                        labelText: AppStrings.emailLabel,
                        prefixIcon: Icon(Icons.email_outlined),
                      ),
                      validator: _validateEmail,
                      onFieldSubmitted: (_) => _passwordFocus.requestFocus(),
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _passwordController,
                      focusNode: _passwordFocus,
                      enabled: !_isLoading,
                      obscureText: _obscurePassword,
                      textInputAction: TextInputAction.done,
                      autofillHints: const [AutofillHints.password],
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
                      validator: _validatePassword,
                      onFieldSubmitted: (_) => _submit(),
                    ),
                    const SizedBox(height: 24),
                    PrimaryButton(
                      label: AppStrings.loginCta,
                      isLoading: _isLoading,
                      onPressed: _isLoading ? null : _submit,
                    ),
                    const SizedBox(height: 16),
                    TextButton(
                      onPressed: widget.onRegisterTap ??
                          () {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text(AppStrings.registerComingSoon),
                              ),
                            );
                          },
                      child: Text(
                        AppStrings.registerHint,
                        style: TextStyle(
                          color: widget.onRegisterTap == null
                              ? colorScheme.onSurfaceVariant
                              : colorScheme.primary,
                        ),
                      ),
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
