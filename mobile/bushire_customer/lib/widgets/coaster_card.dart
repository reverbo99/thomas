import 'package:flutter/material.dart';

import '../core/format.dart';
import '../data/models/coaster_model.dart';
import 'status_chip.dart';

/// Interactive coaster list tile (optional shared widget for browse).
class CoasterCard extends StatelessWidget {
  const CoasterCard({
    super.key,
    required this.coaster,
    this.onTap,
  });

  final CoasterModel coaster;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final price = coaster.pricing?.pricePerKm;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: colorScheme.primaryContainer,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  Icons.airport_shuttle_rounded,
                  color: colorScheme.onPrimaryContainer,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            coaster.name,
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        StatusChip.availability(
                          coaster.availabilityStatus ??
                              (coaster.canBook ? 'available' : 'busy'),
                        ),
                      ],
                    ),
                    if (coaster.plateNumber != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        coaster.plateNumber!,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                    const SizedBox(height: 8),
                    if (coaster.capacity != null || price != null)
                      Text(
                        [
                          if (coaster.capacity != null)
                            '${coaster.capacity} seats',
                          if (price != null) '${AppFormat.tzs(price)} / km',
                        ].join(' · '),
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: colorScheme.onSurfaceVariant,
                        ),
                      ),
                  ],
                ),
              ),
              Icon(Icons.chevron_right, color: colorScheme.onSurfaceVariant),
            ],
          ),
        ),
      ),
    );
  }
}
