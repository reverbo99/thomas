import 'package:flutter/material.dart';

import '../../core/strings.dart';
import '../../widgets/price_breakdown_card.dart';
import '../../widgets/primary_button.dart';
import 'booking_confirm_page.dart';
import 'booking_draft.dart';

/// Price breakdown with Continue CTA.
class PricePreviewPage extends StatelessWidget {
  const PricePreviewPage({super.key, required this.draft});

  final BookingDraft draft;

  @override
  Widget build(BuildContext context) {
    final quote = draft.quote;
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.pricePreview)),
      body: SafeArea(
        child: quote == null
            ? const Center(child: Text('No price quote'))
            : ListView(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
                children: [
                  Text(
                    'Step 2 of 3 — review price',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 12),
                  PriceBreakdownCard(quote: quote),
                  const SizedBox(height: 24),
                  PrimaryButton(
                    label: AppStrings.confirm,
                    icon: Icons.arrow_forward,
                    onPressed: () {
                      Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) => BookingConfirmPage(draft: draft),
                        ),
                      );
                    },
                  ),
                ],
              ),
      ),
    );
  }
}
