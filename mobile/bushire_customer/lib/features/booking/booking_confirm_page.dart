import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/format.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../widgets/primary_button.dart';
import 'booking_draft.dart';
import 'booking_success_page.dart';

/// Summary + confirm CTA → create booking.
class BookingConfirmPage extends StatefulWidget {
  const BookingConfirmPage({super.key, required this.draft});

  final BookingDraft draft;

  @override
  State<BookingConfirmPage> createState() => _BookingConfirmPageState();
}

class _BookingConfirmPageState extends State<BookingConfirmPage> {
  bool _loading = false;
  String? _error;

  Future<void> _confirm() async {
    final draft = widget.draft;
    final quote = draft.quote;
    if (quote == null) {
      setState(() => _error = 'Missing price quote');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final booking = await AppScope.of(context).bookingRepository.createBooking(
            coasterId: draft.coaster.id,
            pickupLocation: draft.pickupLocation,
            dropoffLocation: draft.dropoffLocation,
            hireDate: draft.hireDate,
            hireTime: draft.hireTime,
            passengersCount: draft.passengersCount,
            distanceKm: draft.distanceKm ?? quote.distanceKm,
            totalAmount: quote.totalAmount,
            pickupLatitude: draft.pickupLatitude,
            pickupLongitude: draft.pickupLongitude,
            dropoffLatitude: draft.dropoffLatitude,
            dropoffLongitude: draft.dropoffLongitude,
            returnDate: draft.returnDate,
            returnTime: draft.returnTime,
            purpose: draft.purpose,
            notes: draft.notes,
          );
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute<void>(
          builder: (_) => BookingSuccessPage(booking: booking),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e is ApiException
            ? e.message
            : e.toString().replaceFirst('Exception: ', '');
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final d = widget.draft;
    final q = d.quote;

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.confirmBooking)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
          children: [
            Text(
              'Step 3 of 3 — confirm',
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(
                _error!,
                style: TextStyle(color: theme.colorScheme.error),
              ),
            ],
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _Line('Coaster', d.coaster.name),
                    _Line(AppStrings.pickupLabel, d.pickupLocation),
                    _Line(AppStrings.dropoffLabel, d.dropoffLocation),
                    _Line(
                      AppStrings.hireDateLabel,
                      '${d.hireDate} · ${d.hireTime}',
                    ),
                    if (d.returnDate != null)
                      _Line(
                        'Return',
                        '${d.returnDate}${d.returnTime != null ? ' · ${d.returnTime}' : ''}',
                      ),
                    _Line(
                      AppStrings.passengersCountLabel,
                      '${d.passengersCount}',
                    ),
                    if (d.purpose != null) _Line('Purpose', d.purpose!),
                    if (d.notes != null) _Line('Notes', d.notes!),
                    if (d.distanceKm != null || q != null)
                      _Line(
                        AppStrings.distanceLabel,
                        AppFormat.km(d.distanceKm ?? q?.billableKm),
                      ),
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
                          AppFormat.tzs(q?.totalAmount),
                          style: theme.textTheme.headlineSmall?.copyWith(
                            fontWeight: FontWeight.w800,
                            color: theme.colorScheme.primary,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            PrimaryButton(
              label: AppStrings.confirmBooking,
              isLoading: _loading,
              icon: Icons.check_circle_outline,
              onPressed: _loading ? null : _confirm,
            ),
          ],
        ),
      ),
    );
  }
}

class _Line extends StatelessWidget {
  const _Line(this.label, this.value);

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(
              label,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ),
          Expanded(child: Text(value, style: theme.textTheme.bodyMedium)),
        ],
      ),
    );
  }
}
