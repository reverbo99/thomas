import 'package:flutter/material.dart';

/// Bushire Driver color tokens.
///
/// Distinct from the customer app: a deep **teal** operator identity with cool
/// blue-grey neutrals and elevated (shadowed) card surfaces. Prefer these
/// tokens over ad-hoc hex values.
abstract final class AppColors {
  // ── Brand (teal) ──────────────────────────────────────────────────────────
  /// Brand teal seed.
  static const Color seed = Color(0xFF0D7377);
  static const Color brand = Color(0xFF0D7377);
  static const Color brandDark = Color(0xFF0A5A5D);
  static const Color brandDeep = Color(0xFF063B3D);

  /// Soft teal tint used for containers / icon chips.
  static const Color brandSoft = Color(0xFFD2ECEC);
  static const Color brandSofter = Color(0xFFEAF6F6);

  // ── Neutrals ──────────────────────────────────────────────────────────────
  /// Navy-teal heading ink (primary text).
  static const Color heading = Color(0xFF14232B);

  /// Strong body text.
  static const Color inkSoft = Color(0xFF35505A);

  /// Muted secondary body text.
  static const Color mutedText = Color(0xFF6B7C85);

  static const Color border = Color(0xFFE1EAEC);
  static const Color borderStrong = Color(0xFFCBD9DC);

  // ── Semantic ──────────────────────────────────────────────────────────────
  static const Color success = Color(0xFF15803D);
  static const Color successSoft = Color(0xFFDCFCE7);

  static const Color danger = Color(0xFFC62828);
  static const Color dangerSoft = Color(0xFFFDE4E4);

  static const Color warning = Color(0xFFB45309);
  static const Color warningSoft = Color(0xFFFEF0D3);
  static const Color warningInk = Color(0xFF7C4A03);

  /// Confirmed order / schedule chip.
  static const Color confirmed = Color(0xFF00897B);
  static const Color confirmedSoft = Color(0xFFCDEFEB);

  /// Active / in-progress trip chip.
  static const Color activeTrip = Color(0xFF0369A1);
  static const Color activeSoft = Color(0xFFD8ECFB);

  // ── Surfaces & gradient ─────────────────────────────────────────────────
  /// Soft teal-tinted gradient used behind main screens.
  static const Color gradientStart = Color(0xFFEAF6F6);
  static const Color gradientMid = Color(0xFFF3FAFA);
  static const Color gradientEnd = Color(0xFFFFFFFF);

  /// Elevated white card / floating nav surface.
  static const Color cardSurface = Color(0xFFFFFFFF);

  /// Hand-tuned teal [ColorScheme] with cool neutral overrides.
  static ColorScheme lightScheme() {
    final base = ColorScheme.fromSeed(
      seedColor: seed,
      brightness: Brightness.light,
    );
    return base.copyWith(
      primary: brand,
      onPrimary: Colors.white,
      primaryContainer: brandSoft,
      onPrimaryContainer: brandDeep,
      secondary: inkSoft,
      onSecondary: Colors.white,
      secondaryContainer: brandSofter,
      onSecondaryContainer: heading,
      tertiary: activeTrip,
      onTertiary: Colors.white,
      surface: cardSurface,
      onSurface: heading,
      surfaceContainerLowest: cardSurface,
      surfaceContainerLow: gradientStart,
      surfaceContainer: gradientStart,
      surfaceContainerHigh: const Color(0xFFECF4F4),
      surfaceContainerHighest: const Color(0xFFE6F0F0),
      onSurfaceVariant: mutedText,
      outline: borderStrong,
      outlineVariant: border,
      error: danger,
      onError: Colors.white,
      errorContainer: dangerSoft,
      onErrorContainer: const Color(0xFF7F1D1D),
    );
  }
}
