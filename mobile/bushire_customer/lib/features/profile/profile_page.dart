import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/user_model.dart';
import '../../widgets/primary_button.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({
    super.key,
    required this.onLogout,
    this.onProfileUpdated,
  });

  final Future<void> Function() onLogout;
  final ValueChanged<UserModel>? onProfileUpdated;

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();

  String? _email;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final user = await AppScope.of(context).authRepository.getProfile();
      if (!mounted) return;
      _name.text = user.name;
      _phone.text = user.phone ?? '';
      _email = user.email;
      setState(() => _loading = false);
    } catch (e) {
      if (!mounted) return;
      final cached = AppScope.of(context).authRepository.currentUser;
      if (cached != null) {
        _name.text = cached.name;
        _phone.text = cached.phone ?? '';
        _email = cached.email;
      }
      setState(() {
        _loading = false;
        _error = e is ApiException ? e.message : e.toString();
      });
    }
  }

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final pwd = _password.text;
      final user = await AppScope.of(context).authRepository.updateProfile(
            name: _name.text.trim(),
            phone: _phone.text.trim(),
            password: pwd.isEmpty ? null : pwd,
            passwordConfirmation: pwd.isEmpty ? null : _confirm.text,
          );
      _password.clear();
      _confirm.clear();
      widget.onProfileUpdated?.call(user);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profile updated')),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e is ApiException ? e.message : e.toString();
      });
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _logout() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text(AppStrings.logout),
        content: const Text('Sign out of Bushire Customer?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text(AppStrings.cancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text(AppStrings.logout),
          ),
        ],
      ),
    );
    if (ok == true) await widget.onLogout();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(AppStrings.profile),
        actions: [
          IconButton(
            tooltip: AppStrings.logout,
            onPressed: _logout,
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
                children: [
                  if (_email != null)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.email_outlined),
                      title: Text(_email!),
                      subtitle: const Text('Email (read-only)'),
                    ),
                  if (_error != null) ...[
                    Text(
                      _error!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                    const SizedBox(height: 12),
                  ],
                  TextFormField(
                    controller: _name,
                    decoration: const InputDecoration(
                      labelText: AppStrings.nameLabel,
                      prefixIcon: Icon(Icons.person_outline),
                    ),
                    validator: (v) =>
                        (v == null || v.trim().isEmpty)
                            ? AppStrings.nameRequired
                            : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _phone,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: AppStrings.phoneLabel,
                      prefixIcon: Icon(Icons.phone_outlined),
                    ),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    'Change password (optional)',
                    style: Theme.of(context).textTheme.titleSmall,
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _password,
                    obscureText: true,
                    decoration: const InputDecoration(
                      labelText: 'New password',
                      prefixIcon: Icon(Icons.lock_outline),
                    ),
                    validator: (v) {
                      if (v != null && v.isNotEmpty && v.length < 6) {
                        return AppStrings.passwordTooShort;
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _confirm,
                    obscureText: true,
                    decoration: const InputDecoration(
                      labelText: AppStrings.confirmPasswordLabel,
                      prefixIcon: Icon(Icons.lock_outline),
                    ),
                    validator: (v) {
                      if (_password.text.isNotEmpty && v != _password.text) {
                        return AppStrings.passwordMismatch;
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 24),
                  PrimaryButton(
                    label: AppStrings.save,
                    isLoading: _saving,
                    onPressed: _saving ? null : _save,
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton(
                    onPressed: _logout,
                    child: const Text(AppStrings.logout),
                  ),
                ],
              ),
            ),
    );
  }
}
