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
      StatusTone.success => (AppColors.successSoft, const Color(0xFF166534)),
      StatusTone.danger => (AppColors.dangerSoft, const Color(0xFF991B1B)),
      StatusTone.warning => (AppColors.warningSoft, AppColors.warningInk),
      StatusTone.confirmed => (AppColors.confirmedSoft, const Color(0xFF155E75)),
      StatusTone.neutral => (
          Theme.of(context).colorScheme.surfaceContainerHigh,
          Theme.of(context).colorScheme.onSurfaceVariant,
        ),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: fg.withValues(alpha: 0.18)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            margin: const EdgeInsets.only(right: 6),
            decoration: BoxDecoration(color: fg, shape: BoxShape.circle),
          ),
          Text(
            label,
            style: TextStyle(
              color: fg,
              fontSize: 12,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.1,
            ),
          ),
        ],
      ),
    );
  }
}

enum StatusTone { success, danger, warning, confirmed, neutral }
