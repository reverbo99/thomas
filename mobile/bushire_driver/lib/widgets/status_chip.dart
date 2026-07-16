import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';

enum StatusTone { success, danger, warning, confirmed, active, neutral }

/// Compact status pill for order / coaster / payment labels.
class StatusChip extends StatelessWidget {
  const StatusChip({
    super.key,
    required this.label,
    this.tone = StatusTone.neutral,
  });

  final String label;
  final StatusTone tone;

  /// Coaster availability: available → green, on_hire → primary/amber.
  factory StatusChip.coaster(String? status) {
    final s = (status ?? '').toLowerCase().trim();
    if (s == 'available') {
      return const StatusChip(
        label: 'Available',
        tone: StatusTone.success,
      );
    }
    if (s == 'on_hire' || s == 'on hire') {
      return const StatusChip(
        label: 'On hire',
        tone: StatusTone.active,
      );
    }
    if (s == 'maintenance' || s == 'unavailable') {
      return StatusChip(
        label: _pretty(status),
        tone: StatusTone.danger,
      );
    }
    return StatusChip(label: _pretty(status));
  }

  factory StatusChip.payment(String? status) {
    final label = status == null || status.isEmpty ? 'Pay: —' : 'Pay: $status';
    return StatusChip(
      label: label.replaceAll('_', ' '),
      tone: switch ((status ?? '').toLowerCase()) {
        'paid' => StatusTone.success,
        'failed' || 'cancelled' => StatusTone.danger,
        'pending' || 'partial' => StatusTone.warning,
        _ => StatusTone.neutral,
      },
    );
  }

  factory StatusChip.order(String? status) {
    final s = (status ?? '').toLowerCase().trim();
    if (s == 'pending') {
      return StatusChip(label: _pretty(status), tone: StatusTone.warning);
    }
    if (s == 'confirmed') {
      return StatusChip(label: _pretty(status), tone: StatusTone.confirmed);
    }
    if (s == 'in_progress') {
      return StatusChip(label: _pretty(status), tone: StatusTone.active);
    }
    if (s == 'completed') {
      return StatusChip(label: _pretty(status), tone: StatusTone.success);
    }
    if (s == 'cancelled') {
      return StatusChip(label: _pretty(status), tone: StatusTone.danger);
    }
    return StatusChip(label: _pretty(status));
  }

  static String _pretty(String? status) {
    if (status == null || status.isEmpty) return '—';
    return status.replaceAll('_', ' ');
  }

  @override
  Widget build(BuildContext context) {
    final (bg, fg) = switch (tone) {
      StatusTone.success => (
          AppColors.success.withValues(alpha: 0.15),
          AppColors.success,
        ),
      StatusTone.danger => (
          AppColors.danger.withValues(alpha: 0.15),
          AppColors.danger,
        ),
      StatusTone.warning => (
          AppColors.warning.withValues(alpha: 0.2),
          const Color(0xFF6D4C00),
        ),
      StatusTone.confirmed => (
          AppColors.confirmed.withValues(alpha: 0.15),
          AppColors.confirmed,
        ),
      StatusTone.active => (
          AppColors.activeTrip.withValues(alpha: 0.15),
          AppColors.activeTrip,
        ),
      StatusTone.neutral => (
          Theme.of(context).colorScheme.surfaceContainerHighest,
          Theme.of(context).colorScheme.onSurfaceVariant,
        ),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: fg,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
