import 'package:flutter/material.dart';

/// Bushire Driver color tokens. Prefer these over ad-hoc hex values.
abstract final class AppColors {
  /// Brand teal seed (matches Material 3 [ColorScheme.fromSeed]).
  static const Color seed = Color(0xFF0D7377);

  static const Color success = Color(0xFF2E7D32);
  static const Color danger = Color(0xFFC62828);
  static const Color warning = Color(0xFFF9A825);

  /// Confirmed order / schedule chip.
  static const Color confirmed = Color(0xFF00897B);

  /// Active / in-progress trip chip.
  static const Color activeTrip = Color(0xFF0277BD);
}
