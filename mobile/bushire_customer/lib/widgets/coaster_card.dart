import 'package:flutter/material.dart';

import '../core/format.dart';
import '../core/strings.dart';
import '../data/models/coaster_model.dart';
import 'status_chip.dart';

/// Result-card list tile: circular icon avatar, title/route line, subtitle
/// (plate/capacity/price), trailing colored status/price pill.
class CoasterCard extends StatelessWidget {
  const CoasterCard({
    super.key,
    required this.coaster,
    this.onTap,
    this.trailingAction,
    this.showLocation = false,
  });

  final CoasterModel coaster;
  final VoidCallback? onTap;

  /// Optional action row shown below the details (e.g. a "Book now" button).
  final Widget? trailingAction;

  /// When true, shows lat/lng (or the "location pending" hint) like the
  /// original Browse list tile did.
  final bool showLocation;

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
          padding: const EdgeInsets.all(16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: colorScheme.primaryContainer,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(
                  Icons.airport_shuttle_rounded,
                  color: colorScheme.onPrimaryContainer,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            coaster.name,
                            style: theme.textTheme.titleMedium,
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
                    const SizedBox(height: 6),
                    if (coaster.capacity != null || price != null)
                      Row(
                        children: [
                          if (coaster.capacity != null) ...[
                            Icon(Icons.event_seat_outlined,
                                size: 14, color: colorScheme.onSurfaceVariant),
                            const SizedBox(width: 4),
                            Text(
                              '${coaster.capacity} seats',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ],
                          if (coaster.capacity != null && price != null)
                            const SizedBox(width: 10),
                          if (price != null)
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: colorScheme.primaryContainer,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                '${AppFormat.tzs(price)} / km',
                                style: theme.textTheme.labelSmall?.copyWith(
                                  color: colorScheme.onPrimaryContainer,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                        ],
                      ),
                    if (coaster.driver?.hasInfo == true) ...[
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          Icon(Icons.person_outline,
                              size: 14, color: colorScheme.onSurfaceVariant),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              'Driver: ${coaster.driver!.name}'
                              '${coaster.driver!.phone != null ? ' · ${coaster.driver!.phone}' : ''}',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: colorScheme.onSurfaceVariant,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                    if (showLocation) ...[
                      const SizedBox(height: 4),
                      if (coaster.showsOnMap)
                        Text(
                          '${coaster.latitude!.toStringAsFixed(4)}, '
                          '${coaster.longitude!.toStringAsFixed(4)}',
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: colorScheme.onSurfaceVariant,
                          ),
                        )
                      else
                        Text(
                          AppStrings.locationPending,
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: colorScheme.onSurfaceVariant,
                          ),
                        ),
                    ],
                    if (trailingAction != null) ...[
                      const SizedBox(height: 10),
                      trailingAction!,
                    ],
                  ],
                ),
              ),
              if (trailingAction == null)
                Icon(Icons.chevron_right, color: colorScheme.onSurfaceVariant),
            ],
          ),
        ),
      ),
    );
  }
}
