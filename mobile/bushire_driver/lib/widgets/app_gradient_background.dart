import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';

/// Soft teal-tinted gradient wrapper for main screen bodies.
///
/// Purely decorative — wrap a page's `body` (or its content) with this to
/// get the light gradient backdrop used across the redesigned screens.
class AppGradientBackground extends StatelessWidget {
  const AppGradientBackground({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            AppColors.gradientStart,
            AppColors.gradientMid,
            AppColors.gradientEnd,
          ],
          stops: [0.0, 0.45, 1.0],
        ),
      ),
      child: child,
    );
  }
}
