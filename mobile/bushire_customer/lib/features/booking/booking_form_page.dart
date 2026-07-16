import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/di/app_scope.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/coaster_model.dart';
import '../../widgets/primary_button.dart';
import 'booking_draft.dart';
import 'price_preview_page.dart';

/// Pickup/drop, schedule, passengers → calculate price → preview.
class BookingFormPage extends StatefulWidget {
  const BookingFormPage({super.key, required this.coaster});

  final CoasterModel coaster;

  @override
  State<BookingFormPage> createState() => _BookingFormPageState();
}

class _BookingFormPageState extends State<BookingFormPage> {
  final _formKey = GlobalKey<FormState>();
  final _pickup = TextEditingController();
  final _dropoff = TextEditingController();
  final _pickupLat = TextEditingController();
  final _pickupLng = TextEditingController();
  final _dropoffLat = TextEditingController();
  final _dropoffLng = TextEditingController();
  final _distance = TextEditingController();
  final _passengers = TextEditingController(text: '1');
  final _purpose = TextEditingController();
  final _notes = TextEditingController();

  DateTime? _hireDate;
  TimeOfDay? _hireTime;
  DateTime? _returnDate;
  TimeOfDay? _returnTime;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _hireDate = DateTime(now.year, now.month, now.day);
    _hireTime = TimeOfDay.now();
  }

  @override
  void dispose() {
    _pickup.dispose();
    _dropoff.dispose();
    _pickupLat.dispose();
    _pickupLng.dispose();
    _dropoffLat.dispose();
    _dropoffLng.dispose();
    _distance.dispose();
    _passengers.dispose();
    _purpose.dispose();
    _notes.dispose();
    super.dispose();
  }

  String _fmtDate(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-'
      '${d.month.toString().padLeft(2, '0')}-'
      '${d.day.toString().padLeft(2, '0')}';

  String _fmtTime(TimeOfDay t) =>
      '${t.hour.toString().padLeft(2, '0')}:'
      '${t.minute.toString().padLeft(2, '0')}';

  double? _optDouble(String text) {
    final t = text.trim();
    if (t.isEmpty) return null;
    return double.tryParse(t);
  }

  BookingDraft _draft() {
    return BookingDraft(
      coaster: widget.coaster,
      pickupLocation: _pickup.text.trim(),
      dropoffLocation: _dropoff.text.trim(),
      hireDate: _fmtDate(_hireDate!),
      hireTime: _fmtTime(_hireTime!),
      passengersCount: int.tryParse(_passengers.text.trim()) ?? 1,
      pickupLatitude: _optDouble(_pickupLat.text),
      pickupLongitude: _optDouble(_pickupLng.text),
      dropoffLatitude: _optDouble(_dropoffLat.text),
      dropoffLongitude: _optDouble(_dropoffLng.text),
      distanceKm: num.tryParse(_distance.text.trim()),
      returnDate: _returnDate == null ? null : _fmtDate(_returnDate!),
      returnTime: _returnTime == null ? null : _fmtTime(_returnTime!),
      purpose: _purpose.text.trim().isEmpty ? null : _purpose.text.trim(),
      notes: _notes.text.trim().isEmpty ? null : _notes.text.trim(),
    );
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() => _error = null);
    if (!(_formKey.currentState?.validate() ?? false)) return;
    if (_hireDate == null || _hireTime == null) return;

    final draft = _draft();
    setState(() => _loading = true);
    try {
      final quote = await AppScope.of(context).coasterRepository.calculatePrice(
            coasterId: draft.coaster.id,
            hireDate: draft.hireDate,
            hireTime: draft.hireTime,
            pickupLatitude: draft.pickupLatitude,
            pickupLongitude: draft.pickupLongitude,
            dropoffLatitude: draft.dropoffLatitude,
            dropoffLongitude: draft.dropoffLongitude,
            distanceKm: draft.distanceKm,
            returnDate: draft.returnDate,
          );
      draft.quote = quote;
      if (!mounted) return;
      await Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => PricePreviewPage(draft: draft),
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

  Future<void> _pickHireDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _hireDate ?? now,
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _hireDate = picked);
  }

  Future<void> _pickHireTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _hireTime ?? TimeOfDay.now(),
    );
    if (picked != null) setState(() => _hireTime = picked);
  }

  Future<void> _pickReturnDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _returnDate ?? _hireDate ?? now,
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _returnDate = picked);
  }

  Future<void> _pickReturnTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _returnTime ?? TimeOfDay.now(),
    );
    if (picked != null) setState(() => _returnTime = picked);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.bookingForm)),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
            children: [
              Text(
                widget.coaster.name,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Step 1 of 3 — route & schedule',
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
              TextFormField(
                controller: _pickup,
                enabled: !_loading,
                textCapitalization: TextCapitalization.sentences,
                decoration: const InputDecoration(
                  labelText: AppStrings.pickupLabel,
                  prefixIcon: Icon(Icons.trip_origin),
                ),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Required' : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _dropoff,
                enabled: !_loading,
                textCapitalization: TextCapitalization.sentences,
                decoration: const InputDecoration(
                  labelText: AppStrings.dropoffLabel,
                  prefixIcon: Icon(Icons.place_outlined),
                ),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Required' : null,
              ),
              const SizedBox(height: 8),
              Text(
                'Optional coordinates — or enter distance below',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _pickupLat,
                      enabled: !_loading,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                        signed: true,
                      ),
                      decoration: const InputDecoration(labelText: 'Pickup lat'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: TextFormField(
                      controller: _pickupLng,
                      enabled: !_loading,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                        signed: true,
                      ),
                      decoration: const InputDecoration(labelText: 'Pickup lng'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _dropoffLat,
                      enabled: !_loading,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                        signed: true,
                      ),
                      decoration:
                          const InputDecoration(labelText: 'Drop-off lat'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: TextFormField(
                      controller: _dropoffLng,
                      enabled: !_loading,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                        signed: true,
                      ),
                      decoration:
                          const InputDecoration(labelText: 'Drop-off lng'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _distance,
                enabled: !_loading,
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
                ],
                decoration: const InputDecoration(
                  labelText: AppStrings.distanceLabel,
                  prefixIcon: Icon(Icons.straighten),
                ),
                validator: (v) {
                  final hasCoords = _pickupLat.text.trim().isNotEmpty &&
                      _pickupLng.text.trim().isNotEmpty &&
                      _dropoffLat.text.trim().isNotEmpty &&
                      _dropoffLng.text.trim().isNotEmpty;
                  if (hasCoords) return null;
                  final n = double.tryParse(v?.trim() ?? '');
                  if (n == null || n <= 0) return 'Enter distance or coordinates';
                  return null;
                },
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _loading ? null : _pickHireDate,
                      icon: const Icon(Icons.calendar_today_outlined, size: 18),
                      label: Text(
                        _hireDate == null
                            ? AppStrings.hireDateLabel
                            : _fmtDate(_hireDate!),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _loading ? null : _pickHireTime,
                      icon: const Icon(Icons.schedule_outlined, size: 18),
                      label: Text(
                        _hireTime == null
                            ? AppStrings.hireTimeLabel
                            : _fmtTime(_hireTime!),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _loading ? null : _pickReturnDate,
                      icon: const Icon(Icons.event_outlined, size: 18),
                      label: Text(
                        _returnDate == null
                            ? 'Return date'
                            : _fmtDate(_returnDate!),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _loading ? null : _pickReturnTime,
                      icon: const Icon(Icons.schedule, size: 18),
                      label: Text(
                        _returnTime == null
                            ? 'Return time'
                            : _fmtTime(_returnTime!),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _passengers,
                enabled: !_loading,
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                decoration: const InputDecoration(
                  labelText: AppStrings.passengersCountLabel,
                  prefixIcon: Icon(Icons.groups_outlined),
                ),
                validator: (v) {
                  final n = int.tryParse(v?.trim() ?? '');
                  if (n == null || n < 1) return 'Enter at least 1';
                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _purpose,
                enabled: !_loading,
                decoration: const InputDecoration(
                  labelText: AppStrings.purposeLabel,
                  prefixIcon: Icon(Icons.flag_outlined),
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _notes,
                enabled: !_loading,
                maxLines: 3,
                decoration: const InputDecoration(
                  labelText: AppStrings.notesLabel,
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 24),
              PrimaryButton(
                label: AppStrings.calculatePrice,
                isLoading: _loading,
                icon: Icons.calculate_outlined,
                onPressed: _loading ? null : _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
