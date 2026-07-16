import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/booking_model.dart';
import '../../widgets/primary_button.dart';

/// Passenger seat names → `POST .../passengers` with `seat_names`.
/// Optional phone is appended as `Name · phone` for display flexibility.
class PassengersPage extends StatefulWidget {
  const PassengersPage({super.key, required this.booking});

  final BookingModel booking;

  @override
  State<PassengersPage> createState() => _PassengersPageState();
}

class _PassengersPageState extends State<PassengersPage> {
  late final List<TextEditingController> _nameControllers;
  late final List<TextEditingController> _phoneControllers;
  final _formKey = GlobalKey<FormState>();
  bool _saving = false;
  String? _error;

  int get _count => widget.booking.passengersCount ?? 1;

  @override
  void initState() {
    super.initState();
    final initials = widget.booking.passengerSeats ?? const <String>[];
    _nameControllers = List.generate(_count, (i) {
      final seed = i < initials.length ? initials[i] : '';
      final name = seed.contains(' · ') ? seed.split(' · ').first : seed;
      return TextEditingController(text: name);
    });
    _phoneControllers = List.generate(_count, (i) {
      final seed = i < initials.length ? initials[i] : '';
      final phone =
          seed.contains(' · ') ? seed.split(' · ').skip(1).join(' · ') : '';
      return TextEditingController(text: phone);
    });
  }

  @override
  void dispose() {
    for (final c in _nameControllers) {
      c.dispose();
    }
    for (final c in _phoneControllers) {
      c.dispose();
    }
    super.dispose();
  }

  List<String> _buildSeatNames() {
    return List.generate(_count, (i) {
      final name = _nameControllers[i].text.trim();
      final phone = _phoneControllers[i].text.trim();
      if (phone.isEmpty) return name;
      return '$name · $phone';
    });
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() => _error = null);
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _saving = true);
    try {
      await AppScope.of(context).bookingRepository.submitPassengers(
            bookingId: widget.booking.id,
            seatNames: _buildSeatNames(),
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Passengers saved')),
      );
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e is ApiException ? e.message : e.toString();
      });
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.passengers)),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
            children: [
              Text(
                'Enter one name per seat (must match passenger count)',
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                '${AppStrings.passengersCountLabel}: $_count',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w600,
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
              for (var i = 0; i < _count; i++) ...[
                Text('Seat ${i + 1}', style: theme.textTheme.labelLarge),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _nameControllers[i],
                  enabled: !_saving,
                  textCapitalization: TextCapitalization.words,
                  decoration: const InputDecoration(
                    labelText: 'Passenger name',
                    prefixIcon: Icon(Icons.person_outline),
                  ),
                  validator: (v) =>
                      (v == null || v.trim().isEmpty) ? 'Required' : null,
                ),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _phoneControllers[i],
                  enabled: !_saving,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Phone (optional)',
                    prefixIcon: Icon(Icons.phone_outlined),
                  ),
                ),
                const SizedBox(height: 16),
              ],
              PrimaryButton(
                label: 'Save passengers',
                isLoading: _saving,
                onPressed: _saving ? null : _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
