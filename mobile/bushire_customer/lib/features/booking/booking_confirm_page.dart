import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/format.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/booking_model.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/hero_header_card.dart';
import '../../widgets/payment_waiting_card.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/seat_passenger_field.dart';
import 'booking_draft.dart';
import 'booking_success_page.dart';

enum _ConfirmPhase { review, pay, passengers }

/// Confirm → ClickPesa (no hire yet) → sync creates hire → seat names → success.
class BookingConfirmPage extends StatefulWidget {
  const BookingConfirmPage({super.key, required this.draft});

  final BookingDraft draft;

  @override
  State<BookingConfirmPage> createState() => _BookingConfirmPageState();
}

class _BookingConfirmPageState extends State<BookingConfirmPage> {
  static const _pollInterval = Duration(seconds: 5);
  static const _maxPollAttempts = 60;

  _ConfirmPhase _phase = _ConfirmPhase.review;
  BookingModel? _booking;
  int? _intentId;
  late final TextEditingController _phone;
  String? _orderReference;

  bool _loading = false;
  bool _syncInFlight = false;
  String? _error;
  String? _statusMessage;
  int _pollAttempts = 0;
  Timer? _pollTimer;

  final _passengersFormKey = GlobalKey<FormState>();
  List<TextEditingController> _seatNameControllers = const [];
  List<TextEditingController> _seatPhoneControllers = const [];

  bool _phonePrefillDone = false;

  @override
  void initState() {
    super.initState();
    _phone = TextEditingController();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_phonePrefillDone) return;
    _phonePrefillDone = true;
    final phone =
        AppScope.maybeOf(context)?.authRepository.currentUser?.phone;
    if (phone != null && phone.isNotEmpty && _phone.text.isEmpty) {
      _phone.text = phone;
    }
  }

  @override
  void dispose() {
    _stopPolling();
    _phone.dispose();
    _disposeSeatControllers();
    super.dispose();
  }

  void _disposeSeatControllers() {
    for (final c in _seatNameControllers) {
      c.dispose();
    }
    for (final c in _seatPhoneControllers) {
      c.dispose();
    }
    _seatNameControllers = const [];
    _seatPhoneControllers = const [];
  }

  void _stopPolling() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  String _err(Object e) => e is ApiException
      ? e.message
      : e.toString().replaceFirst('Exception: ', '');

  /// ClickPesa first — hire is created only after payment sync succeeds.
  Future<void> _createAndPay() async {
    final draft = widget.draft;
    final quote = draft.quote;
    if (quote == null) {
      setState(() => _error = 'Missing price quote');
      return;
    }

    final phone = _phone.text.trim();
    if (phone.isEmpty) {
      setState(() => _error = AppStrings.phoneRequired);
      return;
    }

    final services = AppScope.maybeOf(context);
    if (services == null) {
      setState(() => _error = 'Session not ready. Go back and try again.');
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() {
      _loading = true;
      _error = null;
      _statusMessage = null;
      _booking = null;
      _intentId = null;
    });

    try {
      final result = await services.bookingRepository.preparePayment(
        coasterId: draft.coaster.id,
        pickupLocation: draft.pickupLocation,
        dropoffLocation: draft.dropoffLocation,
        hireDate: draft.hireDate,
        hireTime: draft.hireTime,
        passengersCount: draft.passengersCount,
        distanceKm: quote.billableKm > 0
            ? quote.billableKm
            : (draft.distanceKm ?? quote.distanceKm),
        totalAmount: quote.totalAmount,
        phone: phone,
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

      if (result.booking != null) {
        setState(() {
          _statusMessage = result.message ?? AppStrings.paymentReceived;
          _booking = result.booking;
        });
        _onPaymentSynced(result.booking!);
        return;
      }

      if (result.intentId == null) {
        setState(() => _error = 'Could not start payment. Try again.');
        return;
      }

      _intentId = result.intentId;
      if (result.orderReference != null && result.orderReference!.isNotEmpty) {
        _orderReference = result.orderReference;
      }

      setState(() {
        _phase = _ConfirmPhase.pay;
        _statusMessage = result.message ?? AppStrings.waitingForPayment;
      });
      _startPolling();
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = _err(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _resendPaymentPrompt() async {
    final phone = _phone.text.trim();
    if (phone.isEmpty) {
      setState(() => _error = AppStrings.phoneRequired);
      return;
    }
    // New intent + new USSD push (previous pending intent expires unused).
    await _createAndPay();
  }

  void _startPolling() {
    _stopPolling();
    _pollAttempts = 0;
    _pollTimer = Timer.periodic(_pollInterval, (_) => _pollSync());
    Future<void>.delayed(const Duration(seconds: 2), () {
      if (mounted && _phase == _ConfirmPhase.pay) _pollSync();
    });
  }

  Future<void> _pollSync({bool manual = false}) async {
    if (_phase != _ConfirmPhase.pay || _syncInFlight) return;
    final intentId = _intentId;
    final services = AppScope.maybeOf(context);
    if (intentId == null || services == null) return;

    if (!manual) {
      _pollAttempts++;
      if (_pollAttempts > _maxPollAttempts) {
        _stopPolling();
        if (mounted) {
          setState(() {
            _statusMessage = AppStrings.paymentPollTimedOut;
          });
        }
        return;
      }
    }

    _syncInFlight = true;
    if (manual) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }

    try {
      final booking = await services.bookingRepository.syncIntentPayment(
        intentId: intentId,
        reference: _orderReference,
      );
      if (!mounted) return;
      _onPaymentSynced(booking);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 400) {
        if (manual) {
          setState(() {
            _statusMessage = AppStrings.paymentStillPending;
            _error = null;
          });
        }
      } else {
        setState(() => _error = e.message);
      }
    } catch (e) {
      if (!mounted) return;
      if (manual) setState(() => _error = _err(e));
    } finally {
      _syncInFlight = false;
      if (mounted && manual) setState(() => _loading = false);
    }
  }

  void _onPaymentSynced(BookingModel updated) {
    _stopPolling();
    setState(() {
      _booking = updated;
      _error = null;
      _statusMessage = AppStrings.paymentReceived;
      _loading = false;
    });
    if (updated.needsPassengers ||
        updated.hireNextStep == 'enter_passengers') {
      _enterPassengersPhase(updated);
      return;
    }
    if (updated.hireNextStep == 'done') {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute<void>(
          builder: (_) => BookingSuccessPage(booking: updated),
        ),
      );
      return;
    }
    _enterPassengersPhase(updated);
  }

  void _enterPassengersPhase(BookingModel booking) {
    _disposeSeatControllers();
    final count = booking.passengersCount ?? widget.draft.passengersCount;
    final initials = booking.passengerSeats ?? const <String>[];
    _seatNameControllers = List.generate(count, (i) {
      final seed = i < initials.length ? initials[i] : '';
      final name = seed.contains(' · ') ? seed.split(' · ').first : seed;
      return TextEditingController(text: name);
    });
    _seatPhoneControllers = List.generate(count, (i) {
      final seed = i < initials.length ? initials[i] : '';
      final phone =
          seed.contains(' · ') ? seed.split(' · ').skip(1).join(' · ') : '';
      return TextEditingController(text: phone);
    });
    setState(() => _phase = _ConfirmPhase.passengers);
  }

  List<String> _buildSeatNames() {
    return List.generate(_seatNameControllers.length, (i) {
      final name = _seatNameControllers[i].text.trim();
      final phone = _seatPhoneControllers[i].text.trim();
      if (phone.isEmpty) return name;
      return '$name · $phone';
    });
  }

  Future<void> _submitPassengers() async {
    FocusScope.of(context).unfocus();
    setState(() => _error = null);
    if (!(_passengersFormKey.currentState?.validate() ?? false)) return;

    final booking = _booking;
    final services = AppScope.maybeOf(context);
    if (booking == null || services == null) {
      setState(() => _error = 'Session not ready. Go back and try again.');
      return;
    }

    setState(() => _loading = true);
    try {
      final updated = await services.bookingRepository.submitPassengers(
        bookingId: booking.id,
        seatNames: _buildSeatNames(),
      );
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute<void>(
          builder: (_) => BookingSuccessPage(booking: updated),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = _err(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.confirmBooking)),
      body: AppGradientBackground(
        child: SafeArea(
          child: switch (_phase) {
            _ConfirmPhase.review => _buildReview(theme),
            _ConfirmPhase.pay => _buildPay(theme),
            _ConfirmPhase.passengers => _buildPassengers(theme),
          },
        ),
      ),
    );
  }

  Widget _buildReview(ThemeData theme) {
    final d = widget.draft;
    final q = d.quote;

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      children: [
        HeroHeaderCard(
          greeting: AppStrings.confirmBooking,
          subtitle: AppStrings.confirmSubtitle,
          icon: Icons.receipt_long_rounded,
        ),
        if (_error != null) ...[
          const SizedBox(height: 12),
          ErrorBanner(
            message: _error!,
            onDismiss: () => setState(() => _error = null),
          ),
        ],
        const SizedBox(height: 16),
        Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  AppStrings.summary,
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 10),
                _Line('Coaster', d.coaster.name),
                _Line(AppStrings.pickupLabel, d.pickupLocation),
                _Line(AppStrings.dropoffLabel, d.dropoffLocation),
                _Line(
                  AppStrings.startDateLabel,
                  '${d.hireDate} · ${d.hireTime}',
                ),
                if (d.returnDate != null)
                  _Line(
                    AppStrings.returnDateLabel,
                    '${d.returnDate}${d.returnTime != null ? ' · ${d.returnTime}' : ''}',
                  ),
                _Line(
                  AppStrings.passengersCountLabel,
                  '${d.passengersCount}',
                ),
                if (d.purpose != null) _Line(AppStrings.purpose, d.purpose!),
                if (d.notes != null) _Line(AppStrings.notes, d.notes!),
                if (d.distanceKm != null || q != null)
                  _Line(
                    AppStrings.distanceLabel,
                    AppFormat.km(d.distanceKm ?? q?.billableKm),
                  ),
                const Divider(height: 24),
                Row(
                  children: [
                    Text(
                      AppStrings.total,
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
        const SizedBox(height: 16),
        TextFormField(
          controller: _phone,
          keyboardType: TextInputType.phone,
          enabled: !_loading,
          decoration: const InputDecoration(
            labelText: AppStrings.mpesaPhone,
            prefixIcon: Icon(Icons.phone_android),
            helperText: AppStrings.paymentMethodHint,
          ),
        ),
        const SizedBox(height: 24),
        PrimaryButton(
          label: AppStrings.payWithClickPesa,
          isLoading: _loading,
          icon: Icons.payments_outlined,
          onPressed: _loading ? null : _createAndPay,
        ),
      ],
    );
  }

  Widget _buildPay(ThemeData theme) {
    final booking = _booking;
    final amount = booking?.depositAmount ??
        booking?.totalAmount ??
        widget.draft.quote?.totalAmount;
    final polling = _pollTimer != null;
    final orderCode = booking?.orderCode;
    final intentLabel =
        _intentId == null ? null : 'Payment #$_intentId (hire not saved yet)';

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      children: [
        if (orderCode != null && orderCode.isNotEmpty) ...[
          Text(
            orderCode,
            style: theme.textTheme.labelLarge?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 10),
        ] else if (intentLabel != null) ...[
          Text(
            intentLabel,
            style: theme.textTheme.labelLarge?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 10),
        ],
        PaymentWaitingCard(
          title: AppStrings.waitingForPayment,
          subtitle: AppStrings.paymentPollingHint,
          amountLabel: AppFormat.tzs(amount),
          phone: _phone.text.trim().isEmpty ? null : _phone.text.trim(),
          reference: (_orderReference == null || _orderReference!.isEmpty)
              ? null
              : _orderReference,
          statusMessage: _statusMessage,
          isPolling: polling || _syncInFlight,
          trailing: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (_error != null) ...[
                ErrorBanner(
                  message: _error!,
                  onDismiss: () => setState(() => _error = null),
                ),
                const SizedBox(height: 12),
              ],
              PrimaryButton(
                label: AppStrings.checkPayment,
                isLoading: _loading && _syncInFlight,
                icon: Icons.sync,
                onPressed: (_loading && _syncInFlight)
                    ? null
                    : () => _pollSync(manual: true),
              ),
              const SizedBox(height: 10),
              OutlinedButton(
                onPressed: _loading ? null : _resendPaymentPrompt,
                child: const Text(AppStrings.resendPaymentPrompt),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildPassengers(ThemeData theme) {
    final count = _seatNameControllers.length;

    if (count == 0) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text(
            AppStrings.passengersCountMissing,
            textAlign: TextAlign.center,
            style: theme.textTheme.bodyMedium,
          ),
        ),
      );
    }

    return Form(
      key: _passengersFormKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
        children: [
          HeroHeaderCard(
            greeting: AppStrings.enterPassengerNames,
            subtitle: AppStrings.enterPassengerNamesHint,
            icon: Icons.airline_seat_recline_normal_rounded,
          ),
          const SizedBox(height: 12),
          Text(
            '${AppStrings.passengersCountLabel}: $count',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
          if (_error != null) ...[
            const SizedBox(height: 12),
            ErrorBanner(
              message: _error!,
              onDismiss: () => setState(() => _error = null),
            ),
          ],
          const SizedBox(height: 14),
          for (var i = 0; i < count; i++) ...[
            SeatPassengerField(
              seatNumber: i + 1,
              nameController: _seatNameControllers[i],
              phoneController: _seatPhoneControllers[i],
              enabled: !_loading,
            ),
            if (i < count - 1) const SizedBox(height: 12),
          ],
          const SizedBox(height: 24),
          PrimaryButton(
            label: AppStrings.finishBooking,
            isLoading: _loading,
            icon: Icons.check_circle_outline,
            onPressed: _loading ? null : _submitPassengers,
          ),
        ],
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
