import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/booking_model.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/hero_header_card.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/seat_passenger_field.dart';

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

  bool get _hasCount => widget.booking.passengersCount != null;

  int get _count => widget.booking.passengersCount ?? 0;

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
        const SnackBar(content: Text(AppStrings.passengersSaved)),
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

    if (!_hasCount) {
      return Scaffold(
        appBar: AppBar(title: const Text(AppStrings.passengersTitle)),
        body: AppGradientBackground(
          child: SafeArea(
            child: Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.error_outline,
                      size: 48,
                      color: theme.colorScheme.error,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      AppStrings.passengersCountMissing,
                      textAlign: TextAlign.center,
                      style: theme.textTheme.bodyMedium,
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.passengersTitle)),
      body: AppGradientBackground(
        child: SafeArea(
          child: Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
              children: [
                HeroHeaderCard(
                  greeting: AppStrings.passengersTitle,
                  subtitle: AppStrings.passengersSubtitle,
                  icon: Icons.airline_seat_recline_normal_rounded,
                ),
                const SizedBox(height: 12),
                Text(
                  '${AppStrings.passengersCountLabel}: $_count',
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
                for (var i = 0; i < _count; i++) ...[
                  SeatPassengerField(
                    seatNumber: i + 1,
                    nameController: _nameControllers[i],
                    phoneController: _phoneControllers[i],
                    enabled: !_saving,
                  ),
                  if (i < _count - 1) const SizedBox(height: 12),
                ],
                const SizedBox(height: 24),
                PrimaryButton(
                  label: AppStrings.savePassengers,
                  isLoading: _saving,
                  icon: Icons.check_circle_outline,
                  onPressed: _saving ? null : _submit,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
