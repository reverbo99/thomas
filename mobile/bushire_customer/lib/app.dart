import 'package:flutter/material.dart';

import 'core/auth/auth_gate.dart';
import 'core/theme/app_theme.dart';
import 'data/repositories/auth_repository.dart';

export 'core/auth/auth_gate.dart' show AuthSession;

/// Thin [MaterialApp] shell. [home] defaults to [AuthGate].
class BushireCustomerApp extends StatelessWidget {
  const BushireCustomerApp({
    super.key,
    required this.authRepository,
    this.home,
  });

  final AuthRepository authRepository;

  /// Overrides the default auth-gated home when set (useful in tests).
  final Widget? home;

  @override
  Widget build(BuildContext context) {
    if (home != null) {
      return MaterialApp(
        title: 'Bushire Customer',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light(),
        home: home,
      );
    }

    // AuthGate owns MaterialApp + AppScope (via builder) when logged in.
    return AuthGate(authRepository: authRepository);
  }
}
