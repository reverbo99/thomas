import 'package:flutter/material.dart';

import '../../core/format.dart';
import '../../core/navigation/main_shell.dart';
import '../../core/strings.dart';
import '../../data/models/booking_model.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/hero_header_card.dart';
import '../../widgets/primary_button.dart';
import '../trips/trip_detail_page.dart';

class BookingSuccessPage extends StatelessWidget {
  const BookingSuccessPage({super.key, required this.booking});

  final BookingModel booking;

  void _goTrips(BuildContext context) {
    // Pop to shell (first route), select Trips tab, then push the fresh
    // trip's detail on top so the customer lands straight on it.
    final navigator = Navigator.of(context);
    navigator.popUntil((route) => route.isFirst);
    MainShell.requestTab(MainShell.tripsTab);
    navigator.push(
      MaterialPageRoute<void>(
        builder: (_) => TripDetailPage(bookingId: booking.id, preview: booking),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.bookingSuccess)),
      body: AppGradientBackground(
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                HeroHeaderCard(
                  greeting: AppStrings.paymentReceived,
                  subtitle: AppStrings.bookingConfirmed,
                  icon: Icons.check_circle_rounded,
                ),
                const SizedBox(height: 20),
                Text(
                  booking.orderCode ?? 'Order #${booking.id}',
                  textAlign: TextAlign.center,
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  AppFormat.tzs(booking.totalAmount),
                  textAlign: TextAlign.center,
                  style: theme.textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: theme.colorScheme.primary,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Status: ${booking.orderStatus ?? 'pending'} · '
                  'Payment: ${booking.paymentStatus ?? 'paid'}',
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                const Spacer(),
                PrimaryButton(
                  label: AppStrings.viewTrips,
                  icon: Icons.confirmation_number_outlined,
                  onPressed: () => _goTrips(context),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
