import 'package:flutter/material.dart';

import '../core/strings.dart';
import '../core/theme/app_colors.dart';

/// At-a-glance stat tiles under the hero band (TripWay "popular routes"
/// summary-row pattern, adapted to real driver data — no fabricated
/// counters).
class DashboardStatsRow extends StatelessWidget {
  const DashboardStatsRow({
    super.key,
    required this.pendingRequests,
    this.coasterStatus,
    this.capacity,
  });

  final int pendingRequests;
  final String? coasterStatus;
  final int? capacity;

  @override
  Widget build(BuildContext context) {
    final statusLabel = (coasterStatus == null || coasterStatus!.isEmpty)
        ? '—'
        : coasterStatus!.replaceAll('_', ' ');

    return Row(
      children: [
        Expanded(
          child: _StatTile(
            icon: Icons.inbox_outlined,
            value: '$pendingRequests',
            label: AppStrings.pendingRequests,
            emphasize: pendingRequests > 0,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _StatTile(
            icon: Icons.directions_bus_outlined,
            value: statusLabel,
            label: AppStrings.status,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _StatTile(
            icon: Icons.event_seat_outlined,
            value: capacity != null ? '$capacity' : '—',
            label: AppStrings.capacity,
          ),
        ),
      ],
    );
  }
}

class _StatTile extends StatelessWidget {
  const _StatTile({
    required this.icon,
    required this.value,
    required this.label,
    this.emphasize = false,
  });

  final IconData icon;
  final String value;
  final String label;
  final bool emphasize;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final tint = emphasize ? AppColors.warning : AppColors.seed;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      decoration: BoxDecoration(
        color: AppColors.cardSurface,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: tint),
          const SizedBox(height: 8),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: theme.textTheme.titleMedium?.copyWith(
              color: AppColors.heading,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: theme.textTheme.labelSmall?.copyWith(
              color: AppColors.mutedText,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
