import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/format.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/hero_header_card.dart';
import '../../widgets/payment_waiting_card.dart';
import '../../widgets/primary_button.dart';

enum PayMode { deposit, balance }

/// Initiates ClickPesa USSD via API, then background sync until paid.
/// Handset approval is outside the app (not stubbed — real API calls).
class PayPage extends StatefulWidget {
  const PayPage({
    super.key,
    required this.bookingId,
    required this.mode,
    this.amount,
    this.suggestedPhone,
  });

  final int bookingId;
  final PayMode mode;
  final num? amount;
  final String? suggestedPhone;

  @override
  State<PayPage> createState() => _PayPageState();
}

class _PayPageState extends State<PayPage> {
  static const _pollInterval = Duration(seconds: 5);
  static const _maxPollAttempts = 60;

  late final TextEditingController _phone;
  final _reference = TextEditingController();
  bool _loading = false;
  bool _syncInFlight = false;
  bool _awaitingPayment = false;
  String? _message;
  String? _error;
  int _pollAttempts = 0;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _phone = TextEditingController(text: widget.suggestedPhone ?? '');
  }

  @override
  void dispose() {
    _stopPolling();
    _phone.dispose();
    _reference.dispose();
    super.dispose();
  }

  void _stopPolling() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  String _err(Object e) =>
      e is ApiException ? e.message : e.toString().replaceFirst('Exception: ', '');

  Future<void> _pay() async {
    final phone = _phone.text.trim();
    if (phone.isEmpty) {
      setState(() => _error = 'Phone is required for USSD payment.');
      return;
    }

    final services = AppScope.maybeOf(context);
    if (services == null) {
      setState(() => _error = 'Session not ready. Go back and try again.');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
      _message = null;
    });

    try {
      final repo = services.bookingRepository;
      final result = widget.mode == PayMode.deposit
          ? await repo.payDeposit(bookingId: widget.bookingId, phone: phone)
          : await repo.payBalance(bookingId: widget.bookingId, phone: phone);

      if (!mounted) return;
      if (result.orderReference != null) {
        _reference.text = result.orderReference!;
      }
      setState(() {
        _awaitingPayment = true;
        _message = result.message ?? AppStrings.waitingForPayment;
      });
      _startPolling();
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = _err(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _startPolling() {
    _stopPolling();
    _pollAttempts = 0;
    _pollTimer = Timer.periodic(_pollInterval, (_) => _sync(manual: false));
    Future<void>.delayed(const Duration(seconds: 2), () {
      if (mounted && _awaitingPayment) _sync(manual: false);
    });
  }

  Future<void> _sync({bool manual = true}) async {
    if (_syncInFlight) return;

    final services = AppScope.maybeOf(context);
    if (services == null) {
      if (manual) {
        setState(() => _error = 'Session not ready. Go back and try again.');
      }
      return;
    }

    if (!manual) {
      _pollAttempts++;
      if (_pollAttempts > _maxPollAttempts) {
        _stopPolling();
        if (mounted) {
          setState(() {
            _message = AppStrings.paymentPollTimedOut;
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
      await services.bookingRepository.syncPayment(
        bookingId: widget.bookingId,
        reference: _reference.text.trim().isEmpty
            ? null
            : _reference.text.trim(),
      );
      if (!mounted) return;
      _stopPolling();
      setState(() {
        _awaitingPayment = false;
        _message = AppStrings.paymentReceived;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text(AppStrings.paymentReceived)),
      );
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 400) {
        if (manual) {
          setState(() {
            _message = AppStrings.paymentStillPending;
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

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final title = widget.mode == PayMode.deposit
        ? AppStrings.payDeposit
        : AppStrings.payBalance;
    final polling = _pollTimer != null;
    final ref = _reference.text.trim();

    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: AppGradientBackground(
        child: SafeArea(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
            children: [
              if (_awaitingPayment)
                PaymentWaitingCard(
                  title: AppStrings.waitingForPayment,
                  subtitle: AppStrings.paymentPollingHint,
                  amountLabel: widget.amount == null
                      ? null
                      : AppFormat.tzs(widget.amount),
                  phone: _phone.text.trim().isEmpty
                      ? null
                      : _phone.text.trim(),
                  reference: ref.isEmpty ? null : ref,
                  statusMessage: _message,
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
                            : () => _sync(manual: true),
                      ),
                      const SizedBox(height: 10),
                      OutlinedButton(
                        onPressed: _loading ? null : _pay,
                        child: const Text(AppStrings.resendPaymentPrompt),
                      ),
                    ],
                  ),
                )
              else ...[
                HeroHeaderCard(
                  greeting: title,
                  subtitle: AppStrings.paymentMethodHint,
                  icon: Icons.payments_outlined,
                ),
                if (widget.amount != null) ...[
                  const SizedBox(height: 16),
                  Material(
                    color: theme.colorScheme.surfaceContainerLowest,
                    elevation: 1,
                    shadowColor: Colors.black.withValues(alpha: 0.05),
                    borderRadius: BorderRadius.circular(18),
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            AppStrings.amountDue,
                            style: theme.textTheme.labelLarge?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            AppFormat.tzs(widget.amount),
                            style: theme.textTheme.headlineSmall?.copyWith(
                              fontWeight: FontWeight.w800,
                              color: theme.colorScheme.primary,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
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
                const SizedBox(height: 12),
                TextFormField(
                  controller: _reference,
                  enabled: !_loading,
                  decoration: const InputDecoration(
                    labelText: 'Payment reference (optional)',
                    helperText:
                        'Filled automatically after initiate when available',
                  ),
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  ErrorBanner(
                    message: _error!,
                    onDismiss: () => setState(() => _error = null),
                  ),
                ],
                const SizedBox(height: 24),
                PrimaryButton(
                  label: title,
                  isLoading: _loading,
                  icon: Icons.payments_outlined,
                  onPressed: _loading ? null : _pay,
                ),
                const SizedBox(height: 10),
                OutlinedButton(
                  onPressed: _loading ? null : () => _sync(manual: true),
                  child: const Text(AppStrings.syncPayment),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
