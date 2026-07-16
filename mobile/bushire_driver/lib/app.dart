import 'package:flutter/material.dart';

import 'core/auth/auth_gate.dart';
import 'core/theme/app_theme.dart';
import 'data/repositories/auth_repository.dart';

export 'core/auth/auth_gate.dart' show AuthSession;

/// Thin [MaterialApp] shell. [home] defaults to [AuthGate].
class BushireDriverApp extends StatelessWidget {
  const BushireDriverApp({
    super.key,
    required this.authRepository,
    this.home,
  });

  final AuthRepository authRepository;

  /// Overrides the default auth-gated home when set (useful in tests).
  final Widget? home;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Bushire Driver',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      home: home ?? AuthGate(authRepository: authRepository),
    );
  }
}
