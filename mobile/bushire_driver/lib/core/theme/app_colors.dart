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

  /// Soft teal-tinted gradient used behind main screens.
  static const Color gradientStart = Color(0xFFEAF6F6);
  static const Color gradientMid = Color(0xFFF3FAFA);
  static const Color gradientEnd = Color(0xFFFFFFFF);

  /// TripWay-style navy heading (not a second brand — body hierarchy only).
  static const Color heading = Color(0xFF1B2B34);

  /// Muted secondary body text.
  static const Color mutedText = Color(0xFF6B7C85);

  /// Elevated white card / floating nav surface.
  static const Color cardSurface = Color(0xFFFFFFFF);
}
