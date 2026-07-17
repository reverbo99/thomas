import 'package:flutter/material.dart';

import '../core/format.dart';
import '../core/strings.dart';
import '../data/models/order_model.dart';
import 'primary_button.dart';
import 'status_chip.dart';

/// Hire request / paid hire card with optional accept / decline actions.
class HireRequestCard extends StatelessWidget {
  const HireRequestCard({
    super.key,
    required this.request,
    this.onTap,
    this.onAccept,
    this.onDecline,
    this.showActions,
    this.isAccepting = false,
    this.isDeclining = false,
  });

  final OrderModel request;

  /// Opens the order detail (whole card tappable when set).
  final VoidCallback? onTap;
  final VoidCallback? onAccept;
  final VoidCallback? onDecline;

  /// When null, derived from [OrderModel.canRespondToHire].
  final bool? showActions;
  final bool isAccepting;
  final bool isDeclining;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final busy = isAccepting || isDeclining;
    final actionsVisible = showActions ?? request.canRespondToHire;

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
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    request.title,
                    style: theme.textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Wrap(
                  spacing: 6,
                  runSpacing: 4,
                  alignment: WrapAlignment.end,
                  children: [
                    StatusChip.order(request.orderStatus),
                    StatusChip.payment(request.paymentStatus),
                  ],
                ),
              ],
            ),
            if (request.routeSummary.isNotEmpty) ...[
              const SizedBox(height: 8),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(
                    Icons.route_outlined,
                    size: 18,
                    color: colorScheme.onSurfaceVariant,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      request.routeSummary,
                      style: theme.textTheme.bodyMedium,
                    ),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(
                  Icons.event_outlined,
                  size: 18,
                  color: colorScheme.onSurfaceVariant,
                ),
                const SizedBox(width: 8),
                Text(request.whenLabel, style: theme.textTheme.bodyMedium),
              ],
            ),
            if (request.passengersCount != null) ...[
              const SizedBox(height: 6),
              Row(
                children: [
                  Icon(
                    Icons.people_outline,
                    size: 18,
                    color: colorScheme.onSurfaceVariant,
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '${request.passengersCount} ${AppStrings.passengers}',
                    style: theme.textTheme.bodyMedium,
                  ),
                ],
              ),
            ],
            if (request.totalAmount != null) ...[
              const SizedBox(height: 6),
              Text(
                AppFormat.tzs(request.totalAmount),
                style: theme.textTheme.titleSmall?.copyWith(
                  color: colorScheme.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
            if (actionsVisible) ...[
              const SizedBox(height: 14),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: busy ? null : onDecline,
                      style: OutlinedButton.styleFrom(
                        foregroundColor: colorScheme.error,
                        side: BorderSide(color: colorScheme.error),
                      ),
                      child: isDeclining
                          ? SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: colorScheme.error,
                              ),
                            )
                          : const Text(AppStrings.decline),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: PrimaryButton(
                      label: AppStrings.accept,
                      isLoading: isAccepting,
                      onPressed: busy ? null : onAccept,
                    ),
                  ),
                ],
              ),
            ],
          ],
          ),
        ),
      ),
    );
  }
}
