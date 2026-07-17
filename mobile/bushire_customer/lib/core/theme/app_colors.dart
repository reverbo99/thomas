import 'package:flutter/material.dart';

/// Bushire Customer color tokens.
///
/// The brand stays orange, but neutrals use a cool **slate** ramp instead of
/// Material's seed-tinted warm greys — that single change is what moves the UI
/// from "default Flutter" to a polished, professional look. Prefer these tokens
/// over ad-hoc hex values.
abstract final class AppColors {
  // ── Brand ────────────────────────────────────────────────────────────────
  /// Brand chocolate seed.
  static const Color seed = Color(0xFF6B4226);
  static const Color brand = Color(0xFF6B4226);
  static const Color brandDark = Color(0xFF4E2E1A);
  static const Color brandDeep = Color(0xFF3A2015);

  /// Soft cocoa tint used for containers / icon chips.
  static const Color brandSoft = Color(0xFFEDE0D5);
  static const Color brandSofter = Color(0xFFF6EFE8);

  // ── Slate neutral ramp (text, surfaces, borders) ─────────────────────────
  static const Color ink = Color(0xFF0F172A); // slate-900 — primary text
  static const Color inkSoft = Color(0xFF334155); // slate-700 — strong body
  static const Color muted = Color(0xFF64748B); // slate-500 — secondary text
  static const Color faint = Color(0xFF94A3B8); // slate-400 — hints/disabled

  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceLow = Color(0xFFF8FAFC); // slate-50
  static const Color surfaceHigh = Color(0xFFF1F5F9); // slate-100

  static const Color border = Color(0xFFE2E8F0); // slate-200 — hairline
  static const Color borderStrong = Color(0xFFCBD5E1); // slate-300

  // ── Semantic ─────────────────────────────────────────────────────────────
  /// Success / available / paid.
  static const Color success = Color(0xFF16A34A);
  static const Color successSoft = Color(0xFFDCFCE7);

  /// Danger / busy / failed.
  static const Color danger = Color(0xFFDC2626);
  static const Color dangerSoft = Color(0xFFFEE2E2);

  /// Pending payment / night surcharge callouts.
  static const Color warning = Color(0xFFD97706);
  static const Color warningSoft = Color(0xFFFEF3C7);
  static const Color warningInk = Color(0xFF92400E);

  /// Confirmed trip.
  static const Color confirmed = Color(0xFF0E7490);
  static const Color confirmedSoft = Color(0xFFCFFAFE);

  // ── Page-background gradient (very subtle cocoa-to-white) ─────────────────
  static const Color gradientStart = Color(0xFFF6EFE8);
  static const Color gradientEnd = Color(0xFFF8FAFC);

  /// Hand-tuned [ColorScheme] — overrides the warm neutrals that
  /// [ColorScheme.fromSeed] derives from an orange seed.
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
      secondaryContainer: surfaceHigh,
      onSecondaryContainer: ink,
      tertiary: confirmed,
      onTertiary: Colors.white,
      surface: surface,
      onSurface: ink,
      surfaceContainerLowest: surface,
      surfaceContainerLow: surfaceLow,
      surfaceContainer: surfaceLow,
      surfaceContainerHigh: surfaceHigh,
      surfaceContainerHighest: surfaceHigh,
      onSurfaceVariant: muted,
      outline: borderStrong,
      outlineVariant: border,
      error: danger,
      onError: Colors.white,
      errorContainer: dangerSoft,
      onErrorContainer: Color(0xFF7F1D1D),
    );
  }
}
