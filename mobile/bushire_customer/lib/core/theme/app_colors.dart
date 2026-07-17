import 'package:flutter/material.dart';

/// Bushire Customer color tokens. Prefer these over ad-hoc hex values.
abstract final class AppColors {
  /// Brand orange seed (matches Material 3 [ColorScheme.fromSeed]).
  static const Color seed = Color(0xFFE85D04);

  /// Coaster available / success states.
  static const Color success = Color(0xFF2E7D32);

  /// Coaster busy / errors.
  static const Color danger = Color(0xFFC62828);

  /// Pending payment / night surcharge callouts.
  static const Color warning = Color(0xFFF9A825);

  /// Confirmed trip chip.
  static const Color confirmed = Color(0xFF00897B);

  /// Soft page-background gradient — top stop (very light orange tint).
  static const Color gradientStart = Color(0xFFFFF3E9);

  /// Soft page-background gradient — bottom stop (near white).
  static const Color gradientEnd = Color(0xFFFFFFFF);
}
