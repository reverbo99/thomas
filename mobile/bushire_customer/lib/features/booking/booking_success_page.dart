import 'package:flutter/material.dart';

import '../../core/format.dart';
import '../../core/navigation/main_shell.dart';
import '../../core/strings.dart';
import '../../data/models/booking_model.dart';
import '../../widgets/primary_button.dart';

class BookingSuccessPage extends StatelessWidget {
  const BookingSuccessPage({super.key, required this.booking});

  final BookingModel booking;

  void _goTrips(BuildContext context) {
    // Pop to shell (first route) and ask it to show Trips tab.
    Navigator.of(context).popUntil((route) => route.isFirst);
    MainShell.requestTab(MainShell.tripsTab);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.bookingSuccess)),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Icon(
              Icons.check_circle_outline,
              size: 72,
              color: theme.colorScheme.primary,
            ),
            const SizedBox(height: 16),
            Text(
              'Booking request created',
              textAlign: TextAlign.center,
              style: theme.textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              booking.orderCode ?? 'Order #${booking.id}',
              textAlign: TextAlign.center,
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Text(
              AppFormat.tzs(booking.totalAmount),
              textAlign: TextAlign.center,
            ),
            Text(
              'Status: ${booking.orderStatus ?? 'pending'} · '
              'Payment: ${booking.paymentStatus ?? 'pending'}',
              textAlign: TextAlign.center,
            ),
            const Spacer(),
            PrimaryButton(
              label: AppStrings.viewTrips,
              onPressed: () => _goTrips(context),
            ),
          ],
        ),
      ),
    );
  }
}
