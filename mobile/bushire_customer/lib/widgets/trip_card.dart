import 'package:flutter/material.dart';

import '../core/format.dart';
import '../core/strings.dart';
import '../data/models/booking_model.dart';
import 'status_chip.dart';

/// Interactive trip / booking list tile.
class TripCard extends StatelessWidget {
  const TripCard({
    super.key,
    required this.booking,
    this.onTap,
  });

  final BookingModel booking;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final route = [
      booking.pickupLocation,
      booking.dropoffLocation,
    ].whereType<String>().where((s) => s.isNotEmpty).join(' → ');

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      booking.orderCode ?? 'Trip #${booking.id}',
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  if (booking.totalAmount != null)
                    Text(
                      AppFormat.tzs(booking.totalAmount),
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                        color: colorScheme.primary,
                      ),
                    ),
                ],
              ),
              if (booking.coasterName != null) ...[
                const SizedBox(height: 4),
                Text(booking.coasterName!, style: theme.textTheme.bodyMedium),
              ],
              if (route.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  route,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: colorScheme.onSurfaceVariant,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              if (booking.hireDate != null) ...[
                const SizedBox(height: 4),
                Text(
                  [
                    booking.hireDate,
                    if (booking.hireTime != null) booking.hireTime,
                  ].join(' · '),
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 6,
                children: [
                  StatusChip.order(booking.orderStatus),
                  StatusChip.payment(booking.paymentStatus),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                '${AppStrings.orderStatus} / ${AppStrings.paymentStatus}',
                style: theme.textTheme.labelSmall?.copyWith(
                  color: colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
