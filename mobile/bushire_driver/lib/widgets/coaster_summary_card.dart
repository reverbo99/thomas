import 'package:flutter/material.dart';

import '../core/strings.dart';
import 'status_chip.dart';

/// Assigned coaster summary for the driver home dashboard.
class CoasterSummaryCard extends StatelessWidget {
  const CoasterSummaryCard({
    super.key,
    required this.name,
    this.plateNumber,
    this.status,
    this.model,
    this.capacity,
  });

  final String name;
  final String? plateNumber;
  final String? status;
  final String? model;
  final int? capacity;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: colorScheme.primaryContainer,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(
                Icons.directions_bus_filled,
                color: colorScheme.onPrimaryContainer,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    AppStrings.assignedCoaster,
                    style: theme.textTheme.labelMedium?.copyWith(
                      color: colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    name,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  if (plateNumber != null && plateNumber!.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      '${AppStrings.plate}: $plateNumber',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                  if (model != null && model!.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(
                      '${AppStrings.model}: $model',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                  if (capacity != null) ...[
                    const SizedBox(height: 2),
                    Text(
                      '${AppStrings.capacity}: $capacity ${AppStrings.seats}',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                  if (status != null && status!.isNotEmpty) ...[
                    const SizedBox(height: 10),
                    StatusChip.coaster(status),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Empty state when the driver has no assigned coaster.
class NoCoasterCard extends StatelessWidget {
  const NoCoasterCard({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: colorScheme.surfaceContainerHighest,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(
                Icons.directions_bus_outlined,
                color: colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    AppStrings.noCoasterAssigned,
                    style: theme.textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    AppStrings.noCoasterHint,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
