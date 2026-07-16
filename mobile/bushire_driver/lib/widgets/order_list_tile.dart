import 'package:flutter/material.dart';

import '../data/models/order_model.dart';
import 'status_chip.dart';

/// Shared list row for hire requests / orders / schedule / history.
class OrderListTile extends StatelessWidget {
  const OrderListTile({
    super.key,
    required this.order,
    this.onTap,
    this.trailing,
  });

  final OrderModel order;
  final VoidCallback? onTap;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 14, 12, 14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            order.title,
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (order.orderCode != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        order.orderCode!,
                        style: theme.textTheme.bodySmall?.copyWith(color: muted),
                      ),
                    ],
                    const SizedBox(height: 6),
                    Wrap(
                      spacing: 8,
                      runSpacing: 4,
                      children: [
                        StatusChip.order(order.orderStatus),
                        StatusChip.payment(order.paymentStatus),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      order.routeSummary,
                      style: theme.textTheme.bodyMedium,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      order.whenLabel,
                      style: theme.textTheme.bodySmall?.copyWith(color: muted),
                    ),
                    if (trailing != null) ...[
                      const SizedBox(height: 10),
                      trailing!,
                    ],
                  ],
                ),
              ),
              if (onTap != null && trailing == null)
                Icon(Icons.chevron_right, color: muted),
            ],
          ),
        ),
      ),
    );
  }
}
