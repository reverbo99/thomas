import 'dart:async';

import 'package:flutter/material.dart';

import '../../data/api/api_exception.dart';
import '../../data/models/user_model.dart';
import '../../data/repositories/auth_repository.dart';
import '../../features/auth/login_page.dart';
import '../di/app_scope.dart';
import '../navigation/main_shell.dart';
import '../notifications/push_service.dart';

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

/// Bootstrap: restore token → [MainShell]; otherwise [LoginPage].
///
/// Single auth gate — do not nest another [AuthGate].
/// Navigation is a simple widget swap (no named routes / go_router).
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
  final GlobalKey<NavigatorState> _shellNavKey = GlobalKey<NavigatorState>();

  AuthRepository get _auth => widget.authRepository;

  @override
  void initState() {
    super.initState();
    _services = AppServices(authRepository: _auth);
    _bootstrap();
  }

  @override
  void dispose() {
    _services.dispose();
    super.dispose();
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
        _services.setPendingHireCount(profile.pendingHireRequests);
        session = AuthSession.fromUser(profile.user);
      } on ApiException catch (e) {
        if (e.isUnauthorized || e.isForbidden) {
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
            userName: _auth.currentUser?.name ?? 'Driver',
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
      // Keep the backend's push token current for this signed-in driver.
      unawaited(PushService.instance.registerWith(_auth));
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
      unawaited(PushService.instance.registerWith(_auth));
      return LoginResult(userName: session.userName, email: session.email);
    } on ApiException catch (e) {
      throw Exception(e.message);
    }
  }

  Future<void> _handleLogout() async {
    _services.stopAll();
    await PushService.instance.unregister(_auth);
    try {
      await _auth.logout();
    } finally {
      if (mounted) setState(() => _session = null);
    }
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
      return LoginPage(onLogin: _handleLogin);
    }

    // Nested navigator so pushed pages (e.g. OrderDetail) stay under AppScope.
    // Root MaterialApp routes are siblings of AuthGate home and would lose scope.
    return AppScope(
      services: _services,
      child: Navigator(
        key: _shellNavKey,
        onGenerateRoute: (settings) {
          return MaterialPageRoute<void>(
            settings: settings,
            builder: (_) => MainShell(
              onLogout: _handleLogout,
              initialUserName: session.userName,
              initialEmail: session.email,
              initialPendingRequests: _services.pendingHireCount.value,
            ),
          );
        },
      ),
    );
  }
}
