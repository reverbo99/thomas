import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';

/// Soft top-to-bottom gradient wrapper used as the page body background on
/// main screens (dashboard, browse, login, shell). Presentational only.
class AppGradientBackground extends StatelessWidget {
  const AppGradientBackground({
    super.key,
    required this.child,
  });

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [AppColors.gradientStart, AppColors.gradientEnd],
          stops: [0.0, 0.32],
        ),
      ),
      child: child,
    );
  }
}
