import 'dart:io';

import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../../core/di/app_scope.dart';
import '../../core/format.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/booking_model.dart';
import '../../data/models/coaster_model.dart';
import '../../widgets/next_step_hint.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/status_chip.dart';
import '../booking/booking_form_page.dart';
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
  bool _actionBusy = false;
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

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  String _err(Object e) => e is ApiException ? e.message : e.toString();

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
      _snack('Booking cancelled');
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = _err(e));
    } finally {
      if (mounted) setState(() => _cancelling = false);
    }
  }

  Future<void> _reorder() async {
    final scope = AppScope.of(context);
    setState(() {
      _actionBusy = true;
      _error = null;
    });
    try {
      final prefill =
          await scope.bookingRepository.reorderPrefill(widget.bookingId);
      final coasterId = prefill['coaster_id'];
      final id = coasterId is int
          ? coasterId
          : int.tryParse(coasterId?.toString() ?? '');
      if (id == null || id <= 0) {
        throw ApiException(message: 'Missing coaster for reorder');
      }
      final coaster = await scope.coasterRepository.getCoaster(id);
      if (!mounted) return;
      await Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => BookingFormPage(
            coaster: coaster,
            prefill: prefill,
          ),
        ),
      );
      if (!mounted) return;
      _snack(AppStrings.reorderPrefillApplied);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = _err(e));
    } finally {
      if (mounted) setState(() => _actionBusy = false);
    }
  }

  Future<void> _transfer() async {
    final scope = AppScope.of(context);
    final currentId = _trip?.coasterId;
    setState(() {
      _actionBusy = true;
      _error = null;
    });
    try {
      final coasters = await scope.coasterRepository.listCoasters(
        date: _trip?.hireDate,
        time: _trip?.hireTime,
      );
      final options = coasters
          .where((c) => c.id != currentId && c.isAvailable)
          .toList(growable: false);
      if (!mounted) return;
      setState(() => _actionBusy = false);

      if (options.isEmpty) {
        _snack(AppStrings.noOtherCoasters);
        return;
      }

      final selected = await showDialog<CoasterModel>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text(AppStrings.selectCoaster),
          content: SizedBox(
            width: double.maxFinite,
            child: ListView.builder(
              shrinkWrap: true,
              itemCount: options.length,
              itemBuilder: (_, i) {
                final c = options[i];
                final subtitle = [
                  if (c.plateNumber != null && c.plateNumber!.isNotEmpty)
                    c.plateNumber!,
                  if (c.capacity != null) '${c.capacity} ${AppStrings.seats}',
                ].join(' · ');
                return ListTile(
                  title: Text(c.name),
                  subtitle: subtitle.isEmpty ? null : Text(subtitle),
                  onTap: () => Navigator.of(ctx).pop(c),
                );
              },
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(),
              child: const Text(AppStrings.cancel),
            ),
          ],
        ),
      );
      if (selected == null || !mounted) return;

      setState(() => _actionBusy = true);
      final updated = await scope.bookingRepository.transferBooking(
        bookingId: widget.bookingId,
        coasterId: selected.id,
      );
      if (!mounted) return;
      setState(() => _trip = updated);
      _snack(AppStrings.transferSuccess);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = _err(e));
    } finally {
      if (mounted) setState(() => _actionBusy = false);
    }
  }

  Future<void> _refund() async {
    final reasonCtrl = TextEditingController();
    final phoneCtrl = TextEditingController(text: _trip?.customerPhone ?? '');
    final bankCtrl = TextEditingController();
    final accountCtrl = TextEditingController();

    final submitted = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text(AppStrings.refundRequest),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: reasonCtrl,
                decoration: const InputDecoration(
                  labelText: AppStrings.refundReason,
                ),
                maxLines: 3,
              ),
              const SizedBox(height: 8),
              TextField(
                controller: phoneCtrl,
                decoration: const InputDecoration(
                  labelText: AppStrings.refundPhone,
                ),
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 8),
              TextField(
                controller: bankCtrl,
                decoration: const InputDecoration(
                  labelText: AppStrings.refundBank,
                ),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: accountCtrl,
                decoration: const InputDecoration(
                  labelText: AppStrings.refundBankAccount,
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text(AppStrings.cancel),
          ),
          FilledButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text(AppStrings.submitRefund),
          ),
        ],
      ),
    );

    final reason = reasonCtrl.text.trim();
    final phone = phoneCtrl.text.trim();
    final bank = bankCtrl.text.trim();
    final bankAccount = accountCtrl.text.trim();
    reasonCtrl.dispose();
    phoneCtrl.dispose();
    bankCtrl.dispose();
    accountCtrl.dispose();

    if (submitted != true || !mounted) return;

    setState(() {
      _actionBusy = true;
      _error = null;
    });
    try {
      final updated = await AppScope.of(context).bookingRepository.requestRefund(
        bookingId: widget.bookingId,
        reason: reason.isEmpty ? null : reason,
        phone: phone.isEmpty ? null : phone,
        bank: bank.isEmpty ? null : bank,
        bankAccount: bankAccount.isEmpty ? null : bankAccount,
      );
      if (!mounted) return;
      setState(() => _trip = updated);
      _snack(AppStrings.refundSuccess);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = _err(e));
    } finally {
      if (mounted) setState(() => _actionBusy = false);
    }
  }

  Future<void> _openReceipt() async {
    setState(() {
      _actionBusy = true;
      _error = null;
    });
    try {
      final bytes = await AppScope.of(context)
          .bookingRepository
          .downloadReceiptPdf(widget.bookingId);
      final dir = await getTemporaryDirectory();
      final code = _trip?.orderCode ?? '${widget.bookingId}';
      final safe = code.replaceAll(RegExp(r'[^\w\-]+'), '_');
      final file = File('${dir.path}/receipt_$safe.pdf');
      await file.writeAsBytes(bytes, flush: true);
      final result = await OpenFilex.open(file.path);
      if (!mounted) return;
      if (result.type != ResultType.done) {
        _snack(result.message.isNotEmpty ? result.message : AppStrings.receiptFailed);
      } else {
        _snack(AppStrings.receiptOpened);
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = _err(e));
    } finally {
      if (mounted) setState(() => _actionBusy = false);
    }
  }

  Widget _actionButton({
    required IconData icon,
    required String label,
    required VoidCallback? onPressed,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: OutlinedButton.icon(
        onPressed: _actionBusy ? null : onPressed,
        icon: Icon(icon),
        label: Text(label),
      ),
    );
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
            onPressed: _loading || _actionBusy ? null : _reload,
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
                      if (_actionBusy)
                        const Padding(
                          padding: EdgeInsets.only(bottom: 12),
                          child: LinearProgressIndicator(),
                        ),
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
                        NextStepHint(step: t.hireNextStep, showLabel: true),
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
                      if (t.passengerSeats != null &&
                          t.passengerSeats!.isNotEmpty) ...[
                        const SizedBox(height: 12),
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  AppStrings.seatsSavedTitle,
                                  style: theme.textTheme.titleSmall?.copyWith(
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                for (var i = 0; i < t.passengerSeats!.length; i++)
                                  Padding(
                                    padding: const EdgeInsets.only(bottom: 6),
                                    child: Text(
                                      'Seat ${i + 1}: '
                                      '${t.passengerSeats![i].isEmpty ? AppStrings.seatUnnamed : t.passengerSeats![i]}',
                                      style: theme.textTheme.bodyMedium,
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ),
                      ],
                      const SizedBox(height: 16),
                      if (t.needsDeposit)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: OutlinedButton.icon(
                            onPressed: _actionBusy
                                ? null
                                : () {
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
                            onPressed: _actionBusy
                                ? null
                                : () {
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
                          child: PrimaryButton(
                            label: AppStrings.managePassengers,
                            icon: Icons.groups_outlined,
                            onPressed: _actionBusy
                                ? null
                                : () {
                                    Navigator.of(context)
                                        .push(
                                          MaterialPageRoute<void>(
                                            builder: (_) =>
                                                PassengersPage(booking: t),
                                          ),
                                        )
                                        .then((_) => _reload());
                                  },
                          ),
                        ),
                      if (t.canTrack ||
                          t.orderStatus?.toLowerCase() == 'confirmed' ||
                          t.orderStatus?.toLowerCase() == 'in_progress')
                        Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: OutlinedButton.icon(
                            onPressed: _actionBusy
                                ? null
                                : () {
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
                      if (t.canReorder)
                        _actionButton(
                          icon: Icons.replay_outlined,
                          label: AppStrings.reorder,
                          onPressed: _reorder,
                        ),
                      if (t.canTransfer)
                        _actionButton(
                          icon: Icons.swap_horiz_outlined,
                          label: AppStrings.transferOrder,
                          onPressed: _transfer,
                        ),
                      if (t.canRequestRefund)
                        _actionButton(
                          icon: Icons.money_off_outlined,
                          label: AppStrings.refundRequest,
                          onPressed: _refund,
                        ),
                      if (t.canDownloadReceipt) ...[
                        _actionButton(
                          icon: Icons.download_outlined,
                          label: AppStrings.downloadReceipt,
                          onPressed: _openReceipt,
                        ),
                        _actionButton(
                          icon: Icons.print_outlined,
                          label: AppStrings.printReceipt,
                          onPressed: _openReceipt,
                        ),
                      ],
                      if (t.canCancel) ...[
                        const SizedBox(height: 8),
                        PrimaryButton(
                          label: AppStrings.cancelTrip,
                          isLoading: _cancelling,
                          onPressed: (_cancelling || _actionBusy) ? null : _cancel,
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
