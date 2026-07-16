import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/format.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/coaster_model.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/status_chip.dart';
import 'booking_form_page.dart';

class CoasterDetailPage extends StatefulWidget {
  const CoasterDetailPage({
    super.key,
    required this.coasterId,
    this.preview,
  });

  final int coasterId;
  final CoasterModel? preview;

  @override
  State<CoasterDetailPage> createState() => _CoasterDetailPageState();
}

class _CoasterDetailPageState extends State<CoasterDetailPage> {
  CoasterModel? _coaster;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _coaster = widget.preview;
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = _coaster == null;
      _error = null;
    });
    try {
      final c = await AppScope.of(context)
          .coasterRepository
          .getCoaster(widget.coasterId);
      if (!mounted) return;
      setState(() {
        _coaster = c;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        if (_coaster == null) {
          _error = e is ApiException ? e.message : e.toString();
        }
      });
    }
  }

  void _book() {
    final c = _coaster;
    if (c == null || !c.canBook) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => BookingFormPage(coaster: c),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final c = _coaster;
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: Text(c?.name ?? AppStrings.coasterDetail)),
      body: _loading && c == null
          ? const Center(child: CircularProgressIndicator())
          : _error != null && c == null
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(_error!),
                      TextButton(onPressed: _load, child: const Text(AppStrings.retry)),
                    ],
                  ),
                )
              : ListView(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            c!.name,
                            style: theme.textTheme.headlineSmall?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        StatusChip.availability(
                          c.availabilityStatus ??
                              (c.canBook ? 'available' : 'busy'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    if (c.plateNumber != null) Text('Plate: ${c.plateNumber}'),
                    if (c.model != null) Text('Model: ${c.model}'),
                    if (c.color != null) Text('Color: ${c.color}'),
                    if (c.capacity != null) Text('Capacity: ${c.capacity}'),
                    if (c.features != null && c.features!.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      Text('Features', style: theme.textTheme.titleSmall),
                      Text(c.features!),
                    ],
                    if (c.pricing != null) ...[
                      const SizedBox(height: 16),
                      Text('Pricing', style: theme.textTheme.titleSmall),
                      Text('${AppFormat.tzs(c.pricing!.pricePerKm)} per km'),
                      Text('Minimum: ${AppFormat.km(c.pricing!.minKm)}'),
                      Text(
                        'Weekend +${c.pricing!.weekendSurchargePercent}% · '
                        'Night +${c.pricing!.nightSurchargePercent}%',
                      ),
                    ],
                    if (c.driver?.hasInfo == true) ...[
                      const SizedBox(height: 16),
                      Text('Driver', style: theme.textTheme.titleSmall),
                      Text(c.driver!.name),
                      if (c.driver!.phone != null) Text(c.driver!.phone!),
                    ],
                    if (c.latitude != null) ...[
                      const SizedBox(height: 16),
                      Text(
                        AppStrings.mapPlaceholder,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                      Text(
                        '${c.latitude!.toStringAsFixed(5)}, '
                        '${c.longitude!.toStringAsFixed(5)}',
                      ),
                    ],
                    const SizedBox(height: 28),
                    PrimaryButton(
                      label: c.canBook ? AppStrings.bookNow : AppStrings.unavailable,
                      onPressed: c.canBook ? _book : null,
                    ),
                  ],
                ),
    );
  }
}
