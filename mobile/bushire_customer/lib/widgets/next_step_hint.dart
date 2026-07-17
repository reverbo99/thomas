import 'package:flutter/material.dart';

import '../core/strings.dart';
import '../core/theme/app_colors.dart';

/// Muted icon + text hint describing the next action required on a trip
/// (e.g. "Pay deposit"). Copy comes from [AppStrings.nextStepLabel] so the
/// wording stays a single source of truth. Deliberately plain text — never
/// a pill/badge — so it reads as a hint and never gets confused with the
/// pill-shaped [StatusChip] widgets used for order/payment status.
class NextStepHint extends StatelessWidget {
  const NextStepHint({super.key, required this.step, this.showLabel = false});

  /// `pay_deposit` | `wait_owner` | `pay_balance` | `enter_passengers` |
  /// `done` | `legacy_pending`
  final String? step;

  /// Prefix with "Next step: " for fuller context (used on trip detail).
  /// Card/list usages keep this off to stay compact.
  final bool showLabel;

  @override
  Widget build(BuildContext context) {
    final s = step;
    if (s == null || s.isEmpty) return const SizedBox.shrink();

    final theme = Theme.of(context);
    final isDone = s == 'done';
    final color = switch (s) {
      'pay_deposit' || 'pay_balance' => AppColors.warning,
      'enter_passengers' => theme.colorScheme.primary,
      'done' => AppColors.success,
      _ => theme.colorScheme.onSurfaceVariant,
    };
    final icon = isDone ? Icons.check_circle_outline : Icons.arrow_forward_rounded;
    final label = AppStrings.nextStepLabel(s);
    if (label.isEmpty) return const SizedBox.shrink();

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: color),
        const SizedBox(width: 4),
        Flexible(
          child: Text(
            showLabel ? '${AppStrings.nextStep}: $label' : label,
            style: theme.textTheme.labelMedium?.copyWith(
              color: color,
              fontWeight: FontWeight.w600,
            ),
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}
