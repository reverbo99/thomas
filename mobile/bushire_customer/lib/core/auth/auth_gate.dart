import 'package:flutter/material.dart';

import '../../data/api/api_exception.dart';
import '../../data/models/user_model.dart';
import '../../data/repositories/auth_repository.dart';
import '../../features/auth/login_page.dart';
import '../../features/auth/register_page.dart';
import '../di/app_scope.dart';
import '../navigation/main_shell.dart';

/// Session snapshot for greetings / profile.
class AuthSession {
  const AuthSession({required this.userName, this.email, this.user});

  final String userName;
  final String? email;
  final UserModel? user;

  factory AuthSession.fromUser(UserModel user) {
    return AuthSession(
      userName: user.name,
      email: user.email,
      user: user,
    );
  }
}

/// Bootstrap: restore token → [MainShell]; otherwise Login (+ Register).
///
/// Single auth gate — do not nest another [AuthGate].
class AuthGate extends StatefulWidget {
  const AuthGate({
    super.key,
    required this.authRepository,
  });

  final AuthRepository authRepository;

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  AuthSession? _session;
  bool _bootstrapping = true;
  late final AppServices _services;

  AuthRepository get _auth => widget.authRepository;

  @override
  void initState() {
    super.initState();
    _services = AppServices(authRepository: _auth);
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    try {
      final cached = await _auth.restoreSession();
      if (!await _auth.isLoggedIn()) {
        if (mounted) {
          setState(() {
            _session = null;
            _bootstrapping = false;
          });
        }
        return;
      }

      AuthSession session;
      try {
        final profile = await _auth.getProfile();
        session = AuthSession.fromUser(profile);
      } on ApiException catch (e) {
        if (e.isUnauthorized) {
          await _auth.clearLocalSession();
          if (mounted) {
            setState(() {
              _session = null;
              _bootstrapping = false;
            });
          }
          return;
        }
        if (cached != null && cached.name.isNotEmpty) {
          session = AuthSession.fromUser(cached);
        } else {
          session = AuthSession(
            userName: _auth.currentUser?.name ?? 'Customer',
            email: _auth.currentUser?.email,
            user: _auth.currentUser,
          );
        }
      }

      if (mounted) {
        setState(() {
          _session = session;
          _bootstrapping = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _session = null;
          _bootstrapping = false;
        });
      }
    }
  }

  Future<LoginResult> _handleLogin(String email, String password) async {
    try {
      final auth = await _auth.login(email: email, password: password);
      final session = AuthSession.fromUser(auth.user);
      if (mounted) setState(() => _session = session);
      return LoginResult(userName: session.userName, email: session.email);
    } on ApiException catch (e) {
      throw Exception(e.message);
    }
  }

  Future<RegisterResult> _handleRegister({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      final auth = await _auth.register(
        name: name,
        email: email,
        phone: phone,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
      final session = AuthSession.fromUser(auth.user);
      if (mounted) {
        // Pop register route if pushed, then show shell.
        Navigator.of(context).popUntil((r) => r.isFirst);
        setState(() => _session = session);
      }
      return RegisterResult(userName: session.userName, email: session.email);
    } on ApiException catch (e) {
      throw Exception(e.message);
    }
  }

  Future<void> _handleLogout() async {
    try {
      await _auth.logout();
    } finally {
      if (mounted) setState(() => _session = null);
    }
  }

  void _openRegister() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RegisterPage(
          onRegister: _handleRegister,
          onLoginTap: () => Navigator.of(context).pop(),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_bootstrapping) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    final session = _session;
    if (session == null) {
      return LoginPage(
        onLogin: _handleLogin,
        onRegisterTap: _openRegister,
      );
    }

    return AppScope(
      services: _services,
      child: MainShell(onLogout: _handleLogout),
    );
  }
}
