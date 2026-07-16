import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/format.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/booking_model.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/status_chip.dart';
import '../payments/pay_balance_page.dart';
import '../payments/pay_deposit_page.dart';
import 'passengers_page.dart';
import 'track_page.dart';

/// Full trip detail with cancel + links to track / pay / passengers.
class TripDetailPage extends StatefulWidget {
  const TripDetailPage({
    super.key,
    required this.bookingId,
    this.preview,
  });

  final int bookingId;
  final BookingModel? preview;

  @override
  State<TripDetailPage> createState() => _TripDetailPageState();
}

class _TripDetailPageState extends State<TripDetailPage> {
  BookingModel? _trip;
  bool _loading = true;
  bool _cancelling = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _trip = widget.preview;
    WidgetsBinding.instance.addPostFrameCallback((_) => _reload());
  }

  Future<void> _reload() async {
    setState(() {
      _loading = _trip == null;
      _error = null;
    });
    try {
      final fresh =
          await AppScope.of(context).bookingRepository.getBooking(widget.bookingId);
      if (!mounted) return;
      setState(() {
        _trip = fresh;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        if (_trip == null) {
          _error = e is ApiException ? e.message : e.toString();
        }
      });
    }
  }

  Future<void> _cancel() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text(AppStrings.cancelTrip),
        content: const Text('Cancel this booking? This cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text(AppStrings.cancel),
          ),
          FilledButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text(AppStrings.cancelTrip),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    final repo = AppScope.of(context).bookingRepository;
    setState(() {
      _cancelling = true;
      _error = null;
    });
    try {
      final updated = await repo.cancelBooking(widget.bookingId);
      if (!mounted) return;
      setState(() => _trip = updated);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Booking cancelled')),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e is ApiException ? e.message : e.toString();
      });
    } finally {
      if (mounted) setState(() => _cancelling = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final t = _trip;

    return Scaffold(
      appBar: AppBar(
        title: Text(t?.orderCode ?? AppStrings.tripDetail),
        actions: [
          IconButton(
            onPressed: _loading ? null : _reload,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: SafeArea(
        child: _loading && t == null
            ? const Center(child: CircularProgressIndicator())
            : _error != null && t == null
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(_error!),
                        TextButton(
                          onPressed: _reload,
                          child: const Text(AppStrings.retry),
                        ),
                      ],
                    ),
                  )
                : ListView(
                    padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
                    children: [
                      if (_error != null) ...[
                        Text(
                          _error!,
                          style: TextStyle(color: theme.colorScheme.error),
                        ),
                        const SizedBox(height: 12),
                      ],
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          StatusChip.order(t!.orderStatus),
                          StatusChip.payment(t.paymentStatus),
                        ],
                      ),
                      const SizedBox(height: 12),
                      if (t.coasterName != null)
                        Text(
                          t.coasterName!,
                          style: theme.textTheme.headlineSmall?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      if (t.totalAmount != null) ...[
                        const SizedBox(height: 4),
                        Text(
                          AppFormat.tzs(t.totalAmount),
                          style: theme.textTheme.titleLarge?.copyWith(
                            color: theme.colorScheme.primary,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ],
                      if (t.hireNextStep != null &&
                          t.hireNextStep!.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(
                          'Next: ${t.hireNextStep!.replaceAll('_', ' ')}',
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                      const SizedBox(height: 16),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(14),
                          child: Column(
                            children: [
                              _Line('Order', t.orderCode ?? '#${t.id}'),
                              if (t.pickupLocation != null)
                                _Line(AppStrings.pickupLabel, t.pickupLocation!),
                              if (t.dropoffLocation != null)
                                _Line(
                                  AppStrings.dropoffLabel,
                                  t.dropoffLocation!,
                                ),
                              if (t.hireDate != null)
                                _Line(
                                  AppStrings.hireDateLabel,
                                  '${t.hireDate}${t.hireTime != null ? ' · ${t.hireTime}' : ''}',
                                ),
                              if (t.passengersCount != null)
                                _Line(
                                  AppStrings.passengersCountLabel,
                                  '${t.passengersCount}',
                                ),
                              if (t.purpose != null)
                                _Line('Purpose', t.purpose!),
                              if (t.distanceKm != null)
                                _Line(
                                  AppStrings.distanceLabel,
                                  AppFormat.km(t.distanceKm),
                                ),
                              if (t.depositAmount != null)
                                _Line(
                                  AppStrings.payDeposit,
                                  AppFormat.tzs(t.depositAmount),
                                ),
                              if (t.balanceAmount != null)
                                _Line(
                                  AppStrings.payBalance,
                                  AppFormat.tzs(t.balanceAmount),
                                ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      if (t.needsDeposit)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: OutlinedButton.icon(
                            onPressed: () {
                              Navigator.of(context)
                                  .push(
                                    MaterialPageRoute<void>(
                                      builder: (_) => PayDepositPage(
                                        bookingId: t.id,
                                        amount: t.depositAmount,
                                        suggestedPhone: t.customerPhone,
                                      ),
                                    ),
                                  )
                                  .then((_) => _reload());
                            },
                            icon: const Icon(Icons.payments_outlined),
                            label: const Text(AppStrings.payDeposit),
                          ),
                        ),
                      if (t.needsBalance)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: OutlinedButton.icon(
                            onPressed: () {
                              Navigator.of(context)
                                  .push(
                                    MaterialPageRoute<void>(
                                      builder: (_) => PayBalancePage(
                                        bookingId: t.id,
                                        amount: t.balanceAmount,
                                        suggestedPhone: t.customerPhone,
                                      ),
                                    ),
                                  )
                                  .then((_) => _reload());
                            },
                            icon: const Icon(
                              Icons.account_balance_wallet_outlined,
                            ),
                            label: const Text(AppStrings.payBalance),
                          ),
                        ),
                      if (t.needsPassengers)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: OutlinedButton.icon(
                            onPressed: () {
                              Navigator.of(context)
                                  .push(
                                    MaterialPageRoute<void>(
                                      builder: (_) => PassengersPage(booking: t),
                                    ),
                                  )
                                  .then((_) => _reload());
                            },
                            icon: const Icon(Icons.groups_outlined),
                            label: const Text(AppStrings.passengers),
                          ),
                        ),
                      if (t.canTrack ||
                          t.orderStatus?.toLowerCase() == 'confirmed' ||
                          t.orderStatus?.toLowerCase() == 'in_progress')
                        Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: OutlinedButton.icon(
                            onPressed: () {
                              Navigator.of(context).push(
                                MaterialPageRoute<void>(
                                  builder: (_) => TrackPage(
                                    bookingId: t.id,
                                    orderCode: t.orderCode,
                                  ),
                                ),
                              );
                            },
                            icon: const Icon(Icons.my_location_outlined),
                            label: const Text(AppStrings.trackTrip),
                          ),
                        ),
                      if (t.canCancel) ...[
                        const SizedBox(height: 8),
                        PrimaryButton(
                          label: AppStrings.cancelTrip,
                          isLoading: _cancelling,
                          onPressed: _cancelling ? null : _cancel,
                        ),
                      ],
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
            width: 120,
            child: Text(
              label,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }
}
