import 'package:flutter/material.dart';

import '../core/strings.dart';
import '../core/theme/app_colors.dart';

/// Branded ClickPesa waiting / sync panel — hero status, not a bare spinner.
///
/// Use after initiating USSD: show amount, status copy, and optional actions
/// (e.g. Check payment). Presentational only.
class PaymentWaitingCard extends StatelessWidget {
  const PaymentWaitingCard({
    super.key,
    this.title = AppStrings.waitingForPayment,
    this.subtitle = AppStrings.paymentPollingHint,
    this.amountLabel,
    this.phone,
    this.reference,
    this.statusMessage,
    this.isPolling = true,
    this.trailing,
  });

  final String title;
  final String subtitle;
  final String? amountLabel;
  final String? phone;
  final String? reference;
  final String? statusMessage;
  final bool isPolling;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(20, 22, 20, 22),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                colorScheme.primary,
                colorScheme.primary.withValues(alpha: 0.82),
              ],
            ),
            borderRadius: BorderRadius.circular(22),
            boxShadow: [
              BoxShadow(
                color: colorScheme.primary.withValues(alpha: 0.25),
                blurRadius: 16,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Column(
            children: [
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  color: colorScheme.onPrimary.withValues(alpha: 0.18),
                  shape: BoxShape.circle,
                ),
                child: isPolling
                    ? Padding(
                        padding: const EdgeInsets.all(16),
                        child: CircularProgressIndicator(
                          strokeWidth: 3,
                          color: colorScheme.onPrimary,
                        ),
                      )
                    : Icon(
                        Icons.phone_android_rounded,
                        color: colorScheme.onPrimary,
                        size: 32,
                      ),
              ),
              const SizedBox(height: 16),
              Text(
                title,
                textAlign: TextAlign.center,
                style: theme.textTheme.titleLarge?.copyWith(
                  color: colorScheme.onPrimary,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                subtitle,
                textAlign: TextAlign.center,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: colorScheme.onPrimary.withValues(alpha: 0.9),
                  height: 1.35,
                ),
              ),
              if (amountLabel != null && amountLabel!.isNotEmpty) ...[
                const SizedBox(height: 16),
                Text(
                  AppStrings.amountDue,
                  style: theme.textTheme.labelMedium?.copyWith(
                    color: colorScheme.onPrimary.withValues(alpha: 0.75),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  amountLabel!,
                  style: theme.textTheme.headlineSmall?.copyWith(
                    color: colorScheme.onPrimary,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ],
          ),
        ),
        if ((phone != null && phone!.isNotEmpty) ||
            (reference != null && reference!.isNotEmpty) ||
            (statusMessage != null && statusMessage!.isNotEmpty)) ...[
          const SizedBox(height: 14),
          Material(
            color: colorScheme.surfaceContainerLowest,
            elevation: 2,
            shadowColor: Colors.black.withValues(alpha: 0.06),
            borderRadius: BorderRadius.circular(18),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (phone != null && phone!.isNotEmpty)
                    _MetaRow(
                      icon: Icons.phone_outlined,
                      label: phone!,
                    ),
                  if (reference != null && reference!.isNotEmpty) ...[
                    if (phone != null && phone!.isNotEmpty)
                      const SizedBox(height: 8),
                    _MetaRow(
                      icon: Icons.tag_rounded,
                      label: reference!,
                    ),
                  ],
                  if (statusMessage != null && statusMessage!.isNotEmpty) ...[
                    if ((phone != null && phone!.isNotEmpty) ||
                        (reference != null && reference!.isNotEmpty))
                      const SizedBox(height: 10),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(
                          isPolling
                              ? Icons.sync_rounded
                              : Icons.info_outline_rounded,
                          size: 18,
                          color: AppColors.warning.withValues(alpha: 0.95),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            statusMessage!,
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: colorScheme.onSurfaceVariant,
                              height: 1.35,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
        if (trailing != null) ...[
          const SizedBox(height: 16),
          trailing!,
        ],
      ],
    );
  }
}

class _MetaRow extends StatelessWidget {
  const _MetaRow({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Row(
      children: [
        Icon(icon, size: 18, color: colorScheme.primary),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            label,
            style: theme.textTheme.bodyMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ],
    );
  }
}
