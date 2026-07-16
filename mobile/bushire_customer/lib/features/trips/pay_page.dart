import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/format.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../widgets/primary_button.dart';

enum PayMode { deposit, balance }

/// Initiates ClickPesa USSD via API, then optional sync.
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
  late final TextEditingController _phone;
  final _reference = TextEditingController();
  bool _loading = false;
  String? _message;
  String? _error;

  @override
  void initState() {
    super.initState();
    _phone = TextEditingController(text: widget.suggestedPhone ?? '');
  }

  @override
  void dispose() {
    _phone.dispose();
    _reference.dispose();
    super.dispose();
  }

  Future<void> _pay() async {
    final phone = _phone.text.trim();
    if (phone.isEmpty) {
      setState(() => _error = 'Phone is required for USSD payment.');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
      _message = null;
    });

    try {
      final repo = AppScope.of(context).bookingRepository;
      final result = widget.mode == PayMode.deposit
          ? await repo.payDeposit(bookingId: widget.bookingId, phone: phone)
          : await repo.payBalance(bookingId: widget.bookingId, phone: phone);

      if (!mounted) return;
      if (result.orderReference != null) {
        _reference.text = result.orderReference!;
      }
      setState(() {
        _message = result.message ??
            'Payment request sent. Approve on your phone, then sync.';
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e is ApiException ? e.message : e.toString();
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _sync() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await AppScope.of(context).bookingRepository.syncPayment(
            bookingId: widget.bookingId,
            reference: _reference.text.trim().isEmpty
                ? null
                : _reference.text.trim(),
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Payment synced')),
      );
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e is ApiException ? e.message : e.toString();
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.mode == PayMode.deposit
        ? AppStrings.payDeposit
        : AppStrings.payBalance;

    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        children: [
          Text(
            'A payment prompt will be sent to this phone via ClickPesa USSD. '
            'Approve on the handset, then tap Sync payment.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          if (widget.amount != null) ...[
            const SizedBox(height: 16),
            Text(
              'Amount due',
              style: Theme.of(context).textTheme.labelLarge,
            ),
            Text(
              AppFormat.tzs(widget.amount),
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: Theme.of(context).colorScheme.primary,
                  ),
            ),
          ],
          const SizedBox(height: 16),
          TextFormField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(
              labelText: AppStrings.phoneLabel,
              prefixIcon: Icon(Icons.phone_android),
            ),
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _reference,
            decoration: const InputDecoration(
              labelText: 'Payment reference (optional)',
              helperText: 'Filled automatically after initiate when available',
            ),
          ),
          if (_message != null) ...[
            const SizedBox(height: 12),
            Text(_message!),
          ],
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          ],
          const SizedBox(height: 24),
          PrimaryButton(
            label: title,
            isLoading: _loading,
            onPressed: _loading ? null : _pay,
          ),
          const SizedBox(height: 10),
          OutlinedButton(
            onPressed: _loading ? null : _sync,
            child: const Text(AppStrings.syncPayment),
          ),
        ],
      ),
    );
  }
}
