import 'package:flutter/material.dart';

import '../core/format.dart';
import '../core/theme/app_colors.dart';
import '../data/models/price_quote.dart';

/// Displays [PriceQuote] breakdown with a prominent TZS total.
class PriceBreakdownCard extends StatelessWidget {
  const PriceBreakdownCard({
    super.key,
    required this.quote,
  });

  final PriceQuote quote;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final labels = quote.effectiveSurchargeLabels;
    final surchargeAmount = quote.effectiveSurchargeAmount;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (quote.coasterName != null) ...[
              Text(
                quote.coasterName!,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 12),
            ],
            _Row(label: 'Distance', value: AppFormat.km(quote.distanceKm)),
            _Row(label: 'Billable km', value: AppFormat.km(quote.billableKm)),
            _Row(
              label: 'Price per km',
              value: AppFormat.tzs(quote.effectivePricePerKm),
            ),
            _Row(
              label: 'Distance amount',
              value: AppFormat.tzs(quote.effectiveKmAmount),
            ),
            const SizedBox(height: 8),
            Text(
              'Surcharges',
              style: theme.textTheme.labelLarge?.copyWith(
                color: colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 6),
            if (labels.isEmpty)
              Text(
                'No surcharges',
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: colorScheme.onSurfaceVariant,
                ),
              )
            else
              ...labels.map(
                (label) => Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.info_outline,
                        size: 16,
                        color: AppColors.warning,
                      ),
                      const SizedBox(width: 8),
                      Expanded(child: Text(label)),
                    ],
                  ),
                ),
              ),
            if (surchargeAmount > 0) ...[
              const SizedBox(height: 4),
              _Row(
                label: 'Surcharge amount',
                value: AppFormat.tzs(surchargeAmount),
              ),
            ],
            const Divider(height: 24),
            Row(
              children: [
                Text(
                  'Total',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const Spacer(),
                Text(
                  AppFormat.tzs(quote.totalAmount),
                  style: theme.textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: colorScheme.primary,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ),
          Text(value, style: theme.textTheme.bodyMedium),
        ],
      ),
    );
  }
}
