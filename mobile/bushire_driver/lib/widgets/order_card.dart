import 'package:flutter/material.dart';

import '../core/format.dart';
import '../core/strings.dart';
import '../data/models/order_model.dart';
import 'status_chip.dart';

/// Order list card with status and payment chips.
class OrderCard extends StatelessWidget {
  const OrderCard({
    super.key,
    required this.order,
    this.onTap,
    this.compact = false,
  });

  final OrderModel order;
  final VoidCallback? onTap;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

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
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: colorScheme.primaryContainer,
                ),
                child: Icon(
                  Icons.route_outlined,
                  color: colorScheme.onPrimaryContainer,
                  size: 22,
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
                            order.orderCode ?? order.title,
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        if (order.totalAmount != null)
                          Text(
                            AppFormat.tzs(order.totalAmount),
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w700,
                              color: colorScheme.primary,
                            ),
                          ),
                      ],
                    ),
                    if (!compact) ...[
                      const SizedBox(height: 4),
                      Text(order.title, style: theme.textTheme.bodyMedium),
                    ],
                    if (order.routeSummary.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        order.routeSummary,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: colorScheme.onSurfaceVariant,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                    const SizedBox(height: 4),
                    Text(
                      order.whenLabel,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                    ),
                    if (!compact && order.passengersCount != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        '${order.passengersCount} ${AppStrings.passengers}',
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
                        StatusChip.order(order.orderStatus),
                        StatusChip.payment(order.paymentStatus),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
