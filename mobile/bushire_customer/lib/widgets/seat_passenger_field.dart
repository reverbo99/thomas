import 'package:flutter/material.dart';

import '../core/strings.dart';

/// One seat’s name (+ optional phone) with a branded seat chip label.
///
/// Soft surface grouping for interaction — not a heavy dashboard card.
class SeatPassengerField extends StatelessWidget {
  const SeatPassengerField({
    super.key,
    required this.seatNumber,
    required this.nameController,
    this.phoneController,
    this.enabled = true,
    this.showPhone = true,
    this.nameValidator,
  });

  final int seatNumber;
  final TextEditingController nameController;
  final TextEditingController? phoneController;
  final bool enabled;
  final bool showPhone;
  final String? Function(String?)? nameValidator;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Material(
      color: colorScheme.surfaceContainerLowest,
      elevation: 1,
      shadowColor: Colors.black.withValues(alpha: 0.05),
      borderRadius: BorderRadius.circular(18),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: colorScheme.primaryContainer,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.event_seat_outlined,
                        size: 14,
                        color: colorScheme.onPrimaryContainer,
                      ),
                      const SizedBox(width: 5),
                      Text(
                        AppStrings.seatNumberLabel(seatNumber),
                        style: theme.textTheme.labelMedium?.copyWith(
                          color: colorScheme.onPrimaryContainer,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: nameController,
              enabled: enabled,
              textCapitalization: TextCapitalization.words,
              textInputAction: showPhone && phoneController != null
                  ? TextInputAction.next
                  : TextInputAction.done,
              decoration: const InputDecoration(
                labelText: AppStrings.passengerName,
                prefixIcon: Icon(Icons.person_outline),
              ),
              validator: nameValidator ??
                  (v) => (v == null || v.trim().isEmpty)
                      ? AppStrings.fieldRequired
                      : null,
            ),
            if (showPhone && phoneController != null) ...[
              const SizedBox(height: 10),
              TextFormField(
                controller: phoneController,
                enabled: enabled,
                keyboardType: TextInputType.phone,
                textInputAction: TextInputAction.done,
                decoration: const InputDecoration(
                  labelText: AppStrings.passengerPhoneOptional,
                  prefixIcon: Icon(Icons.phone_outlined),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
