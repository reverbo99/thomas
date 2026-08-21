import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/di/app_scope.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/geo/geo_math.dart';
import '../../data/geo/geo_point.dart';
import '../../data/geo/osrm_service.dart';
import '../../data/geo/place_result.dart';
import '../../data/models/coaster_model.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/datetime_range_cards.dart';
import '../../widgets/hero_header_card.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/route_map_preview.dart';
import '../../widgets/route_places_card.dart';
import 'booking_draft.dart';
import 'price_preview_page.dart';

/// Pickup/drop, schedule, passengers → calculate price → preview.
class BookingFormPage extends StatefulWidget {
  const BookingFormPage({
    super.key,
    required this.coaster,
    this.initialHireDate,
    this.initialHireTime,
    this.prefill,
  });

  final CoasterModel coaster;

  /// Pre-fill from Book tab date/time filters when the customer already chose them.
  final DateTime? initialHireDate;
  final TimeOfDay? initialHireTime;

  /// Prefill from `POST /bookings/{id}/reorder` (or similar).
  final Map<String, dynamic>? prefill;

  @override
  State<BookingFormPage> createState() => _BookingFormPageState();
}

class _BookingFormPageState extends State<BookingFormPage> {
  final _formKey = GlobalKey<FormState>();
  final _pickup = TextEditingController();
  final _dropoff = TextEditingController();
  final _passengers = TextEditingController(text: '1');
  final _purpose = TextEditingController();
  final _notes = TextEditingController();
  final _osrm = OsrmService();

  PlaceResult? _pickupPlace;
  PlaceResult? _dropoffPlace;
  List<GeoPoint> _routePoints = const [];
  double? _distanceKm;
  bool _usedRoadRoute = false;
  bool _routing = false;
  int _routeGeneration = 0;

  DateTime? _hireDate;
  TimeOfDay? _hireTime;
  DateTime? _returnDate;
  TimeOfDay? _returnTime;
  bool _loading = false;
  String? _error;

  int? get _capacity => widget.coaster.capacity;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    final seeded = widget.initialHireDate;
    _hireDate = seeded != null
        ? DateTime(seeded.year, seeded.month, seeded.day)
        : DateTime(now.year, now.month, now.day);
    _hireTime = widget.initialHireTime ?? TimeOfDay.now();
    _applyPrefill(widget.prefill);
  }

  void _applyPrefill(Map<String, dynamic>? prefill) {
    if (prefill == null || prefill.isEmpty) return;

    final pickup = prefill['pickup_location']?.toString();
    if (pickup != null && pickup.isNotEmpty) _pickup.text = pickup;

    final dropoff = prefill['dropoff_location']?.toString();
    if (dropoff != null && dropoff.isNotEmpty) _dropoff.text = dropoff;

    final passengers = prefill['passengers_count'];
    if (passengers != null) _passengers.text = passengers.toString();

    final purpose = prefill['purpose']?.toString();
    if (purpose != null && purpose.isNotEmpty) _purpose.text = purpose;

    final notes = prefill['notes']?.toString();
    if (notes != null && notes.isNotEmpty) _notes.text = notes;

    final hireDate = _parseDate(prefill['hire_date']?.toString());
    if (hireDate != null) _hireDate = hireDate;

    final hireTime = _parseTime(prefill['hire_time']?.toString());
    if (hireTime != null) _hireTime = hireTime;

    final returnDate = _parseDate(prefill['return_date']?.toString());
    if (returnDate != null) _returnDate = returnDate;

    final returnTime = _parseTime(prefill['return_time']?.toString());
    if (returnTime != null) _returnTime = returnTime;

    final pLat = _asDouble(prefill['pickup_latitude']);
    final pLng = _asDouble(prefill['pickup_longitude']);
    if (pickup != null &&
        pickup.isNotEmpty &&
        pLat != null &&
        pLng != null) {
      _pickupPlace = PlaceResult(
        displayName: pickup,
        latitude: pLat,
        longitude: pLng,
      );
    }

    final dLat = _asDouble(prefill['dropoff_latitude']);
    final dLng = _asDouble(prefill['dropoff_longitude']);
    if (dropoff != null &&
        dropoff.isNotEmpty &&
        dLat != null &&
        dLng != null) {
      _dropoffPlace = PlaceResult(
        displayName: dropoff,
        latitude: dLat,
        longitude: dLng,
      );
    }

    final dist = prefill['distance_km'];
    if (dist is num) {
      _distanceKm = dist.toDouble();
    } else if (dist != null) {
      _distanceKm = double.tryParse(dist.toString());
    }

    if (_pickupPlace != null && _dropoffPlace != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _refreshRoute());
    }
  }

  static DateTime? _parseDate(String? raw) {
    if (raw == null || raw.length < 10) return null;
    return DateTime.tryParse(raw.substring(0, 10));
  }

  static TimeOfDay? _parseTime(String? raw) {
    if (raw == null || raw.length < 5) return null;
    final parts = raw.substring(0, 5).split(':');
    if (parts.length < 2) return null;
    final h = int.tryParse(parts[0]);
    final m = int.tryParse(parts[1]);
    if (h == null || m == null) return null;
    return TimeOfDay(hour: h, minute: m);
  }

  static double? _asDouble(dynamic value) {
    if (value == null) return null;
    if (value is double) return value;
    if (value is num) return value.toDouble();
    return double.tryParse(value.toString());
  }

  @override
  void dispose() {
    _pickup.dispose();
    _dropoff.dispose();
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

  BookingDraft _draft() {
    return BookingDraft(
      coaster: widget.coaster,
      pickupLocation: _pickup.text.trim(),
      dropoffLocation: _dropoff.text.trim(),
      hireDate: _fmtDate(_hireDate!),
      hireTime: _fmtTime(_hireTime!),
      passengersCount: int.tryParse(_passengers.text.trim()) ?? 1,
      pickupLatitude: _pickupPlace?.latitude,
      pickupLongitude: _pickupPlace?.longitude,
      dropoffLatitude: _dropoffPlace?.latitude,
      dropoffLongitude: _dropoffPlace?.longitude,
      distanceKm: _distanceKm,
      routedDistanceKm: _usedRoadRoute ? _distanceKm : null,
      distanceMode: _usedRoadRoute ? 'route' : 'straight',
      returnDate: _returnDate == null ? null : _fmtDate(_returnDate!),
      returnTime: _returnTime == null ? null : _fmtTime(_returnTime!),
      purpose: _purpose.text.trim().isEmpty ? null : _purpose.text.trim(),
      notes: _notes.text.trim().isEmpty ? null : _notes.text.trim(),
    );
  }

  void _onPickupSelected(PlaceResult? place) {
    setState(() => _pickupPlace = place);
    _refreshRoute();
  }

  void _onDropoffSelected(PlaceResult? place) {
    setState(() => _dropoffPlace = place);
    _refreshRoute();
  }

  void _onSwapPlaces() {
    final swappedPickup = _dropoffPlace;
    final swappedDropoff = _pickupPlace;
    setState(() {
      _pickupPlace = swappedPickup;
      _dropoffPlace = swappedDropoff;
    });
    _refreshRoute();
  }

  Future<void> _refreshRoute() async {
    final from = _pickupPlace;
    final to = _dropoffPlace;
    if (from == null || to == null) {
      setState(() {
        _distanceKm = null;
        _routePoints = const [];
        _usedRoadRoute = false;
        _routing = false;
      });
      return;
    }

    final gen = ++_routeGeneration;
    setState(() => _routing = true);

    final routed = await _osrm.route(
      fromLat: from.latitude,
      fromLng: from.longitude,
      toLat: to.latitude,
      toLng: to.longitude,
    );

    if (!mounted || gen != _routeGeneration) return;

    if (routed != null) {
      setState(() {
        _distanceKm = routed.distanceKm;
        _routePoints = routed.geometry;
        _usedRoadRoute = true;
        _routing = false;
      });
      return;
    }

    // Fallback: straight-line distance when OSRM is unreachable.
    final km = GeoMath.haversineKm(
      from.latitude,
      from.longitude,
      to.latitude,
      to.longitude,
    );
    setState(() {
      _distanceKm = km;
      _routePoints = [
        GeoPoint(latitude: from.latitude, longitude: from.longitude),
        GeoPoint(latitude: to.latitude, longitude: to.longitude),
      ];
      _usedRoadRoute = false;
      _routing = false;
    });
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() => _error = null);
    if (!(_formKey.currentState?.validate() ?? false)) return;
    if (_hireDate == null || _hireTime == null) return;

    if (_pickupPlace == null || _dropoffPlace == null) {
      setState(() => _error = AppStrings.selectPlaceHint);
      return;
    }
    if (_distanceKm == null || _distanceKm! <= 0) {
      setState(() => _error = 'Could not calculate route distance');
      return;
    }

    final draft = _draft();
    final services = AppScope.maybeOf(context);
    if (services == null) {
      setState(() => _error = 'Session not ready. Go back and open Book again.');
      return;
    }

    setState(() => _loading = true);
    try {
      final quote = await services.coasterRepository.calculatePrice(
            coasterId: draft.coaster.id,
            hireDate: draft.hireDate,
            hireTime: draft.hireTime,
            pickupLatitude: draft.pickupLatitude,
            pickupLongitude: draft.pickupLongitude,
            dropoffLatitude: draft.dropoffLatitude,
            dropoffLongitude: draft.dropoffLongitude,
            distanceKm: draft.distanceKm,
            routedDistanceKm: draft.routedDistanceKm,
            distanceMode: draft.distanceMode,
            returnDate: draft.returnDate,
            returnTime: draft.returnTime,
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

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.bookingForm)),
      body: AppGradientBackground(
        child: SafeArea(
          child: Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
              children: [
                HeroHeaderCard(
                  greeting: widget.coaster.name,
                  subtitle: AppStrings.bookingStepHint,
                  icon: Icons.edit_road_rounded,
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Material(
                    color: colorScheme.errorContainer.withValues(alpha: 0.55),
                    borderRadius: BorderRadius.circular(14),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 12,
                      ),
                      child: Row(
                        children: [
                          Icon(
                            Icons.error_outline,
                            color: colorScheme.error,
                            size: 20,
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              _error!,
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: colorScheme.onErrorContainer,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
                const SizedBox(height: 20),
                _SectionLabel(
                  icon: Icons.route_rounded,
                  label: AppStrings.routeSectionTitle,
                ),
                const SizedBox(height: 6),
                Text(
                  AppStrings.routeSectionHint,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: 10),
                RoutePlacesCard(
                  pickupController: _pickup,
                  dropoffController: _dropoff,
                  enabled: !_loading,
                  pickupLabel: AppStrings.pickupLabel,
                  dropoffLabel: AppStrings.dropoffLabel,
                  onPickupSelected: _onPickupSelected,
                  onDropoffSelected: _onDropoffSelected,
                  onSwap: _onSwapPlaces,
                  pickupValidator: (v) =>
                      (v == null || v.trim().isEmpty) ? 'Required' : null,
                  dropoffValidator: (v) =>
                      (v == null || v.trim().isEmpty) ? 'Required' : null,
                ),
                const SizedBox(height: 12),
                RouteMapPreview(
                  pickup: _pickupPlace,
                  dropoff: _dropoffPlace,
                  routePoints: _routePoints,
                  distanceKm: _distanceKm,
                  routing: _routing,
                ),
                const SizedBox(height: 20),
                _SectionLabel(
                  icon: Icons.event_available_rounded,
                  label: AppStrings.scheduleSection,
                ),
                const SizedBox(height: 10),
                DateTimeRangeCards(
                  hireDate: _hireDate,
                  hireTime: _hireTime,
                  returnDate: _returnDate,
                  returnTime: _returnTime,
                  enabled: !_loading,
                  onHireDateChanged: (d) => setState(() {
                    _hireDate = d;
                    if (_returnDate != null && _returnDate!.isBefore(d)) {
                      _returnDate = d;
                    }
                  }),
                  onHireTimeChanged: (t) => setState(() => _hireTime = t),
                  onReturnDateChanged: (d) => setState(() => _returnDate = d),
                  onReturnTimeChanged: (t) => setState(() {
                    _returnTime = t;
                    _returnDate ??= _hireDate;
                  }),
                  onClearReturn: () => setState(() {
                    _returnDate = null;
                    _returnTime = null;
                  }),
                ),
                const SizedBox(height: 20),
                _SectionLabel(
                  icon: Icons.groups_outlined,
                  label: AppStrings.detailsSection,
                ),
                const SizedBox(height: 10),
                TextFormField(
                  controller: _passengers,
                  enabled: !_loading,
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: InputDecoration(
                    labelText: AppStrings.passengersCountLabel,
                    prefixIcon: const Icon(Icons.groups_outlined),
                    helperText: _capacity == null
                        ? null
                        : AppStrings.passengersCapacityHint(_capacity!),
                  ),
                  validator: (v) {
                    final n = int.tryParse(v?.trim() ?? '');
                    if (n == null || n < 1) return 'Enter at least 1';
                    final cap = _capacity;
                    if (cap != null && n > cap) {
                      return AppStrings.passengersOverCapacity(cap);
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 6),
                Text(
                  AppStrings.passengersSeatsLaterHint,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: colorScheme.onSurfaceVariant,
                  ),
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
                  onPressed: _loading || _routing ? null : _submit,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SectionLabel extends StatelessWidget {
  const _SectionLabel({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Row(
      children: [
        Icon(icon, size: 18, color: colorScheme.primary),
        const SizedBox(width: 8),
        Text(
          label,
          style: theme.textTheme.titleSmall?.copyWith(
            fontWeight: FontWeight.w700,
            letterSpacing: 0.2,
          ),
        ),
      ],
    );
  }
}
