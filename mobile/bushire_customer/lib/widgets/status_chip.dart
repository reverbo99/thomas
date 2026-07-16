import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';

class StatusChip extends StatelessWidget {
  const StatusChip({
    super.key,
    required this.label,
    this.tone = StatusTone.neutral,
  });

  final String label;
  final StatusTone tone;

  factory StatusChip.fromStatus(String? status) {
    final s = (status ?? '').toLowerCase().trim();
    if (s == 'available' || s == 'paid' || s == 'completed') {
      return StatusChip(
        label: _pretty(status),
        tone: StatusTone.success,
      );
    }
    if (s == 'busy' || s == 'cancelled' || s == 'failed') {
      return StatusChip(
        label: _pretty(status),
        tone: StatusTone.danger,
      );
    }
    if (s == 'confirmed') {
      return StatusChip(
        label: _pretty(status),
        tone: StatusTone.confirmed,
      );
    }
    if (s == 'pending' || s == 'in_progress' || s == 'processing') {
      return StatusChip(
        label: _pretty(status),
        tone: StatusTone.warning,
      );
    }
    return StatusChip(label: _pretty(status));
  }

  factory StatusChip.availability(String? status) =>
      StatusChip.fromStatus(status);

  factory StatusChip.order(String? status) => StatusChip.fromStatus(status);

  factory StatusChip.payment(String? status) {
    final label = status == null || status.isEmpty ? 'Pay: —' : 'Pay: $status';
    return StatusChip(
      label: label,
      tone: StatusChip.fromStatus(status).tone,
    );
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

enum StatusTone { success, danger, warning, confirmed, neutral }
