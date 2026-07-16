import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/format.dart';
import '../../core/strings.dart';
import '../../core/theme/app_colors.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/coaster_model.dart';
import '../../widgets/status_chip.dart';
import '../booking/coaster_detail_page.dart';

/// Home tab: coaster list with optional date/time availability filter.
/// Google Maps is stubbed — markers shown as lat/lng on cards.
class BrowsePage extends StatefulWidget {
  const BrowsePage({super.key});

  @override
  State<BrowsePage> createState() => _BrowsePageState();
}

class _BrowsePageState extends State<BrowsePage> {
  List<CoasterModel> _items = const [];
  bool _loading = true;
  String? _error;
  DateTime? _date;
  TimeOfDay? _time;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  String? get _dateStr {
    final d = _date;
    if (d == null) return null;
    return '${d.year.toString().padLeft(4, '0')}-'
        '${d.month.toString().padLeft(2, '0')}-'
        '${d.day.toString().padLeft(2, '0')}';
  }

  String? get _timeStr {
    final t = _time;
    if (t == null) return null;
    return '${t.hour.toString().padLeft(2, '0')}:'
        '${t.minute.toString().padLeft(2, '0')}';
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final repo = AppScope.of(context).coasterRepository;
      final list = await repo.listCoasters(date: _dateStr, time: _timeStr);
      if (!mounted) return;
      setState(() {
        _items = list;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e is ApiException
            ? e.message
            : e.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _date ?? now,
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() => _date = picked);
      await _load();
    }
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _time ?? TimeOfDay.now(),
    );
    if (picked != null) {
      setState(() => _time = picked);
      await _load();
    }
  }

  void _openDetail(CoasterModel c) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => CoasterDetailPage(coasterId: c.id, preview: c),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text(AppStrings.browseTitle),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _loading ? null : _load,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _pickDate,
                    icon: const Icon(Icons.calendar_today, size: 18),
                    label: Text(_dateStr ?? 'Any date'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _pickTime,
                    icon: const Icon(Icons.schedule, size: 18),
                    label: Text(_timeStr ?? 'Any time'),
                  ),
                ),
                if (_date != null || _time != null) ...[
                  const SizedBox(width: 4),
                  IconButton(
                    tooltip: 'Clear filters',
                    onPressed: () {
                      setState(() {
                        _date = null;
                        _time = null;
                      });
                      _load();
                    },
                    icon: const Icon(Icons.clear),
                  ),
                ],
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Align(
              alignment: Alignment.centerLeft,
              child: Text(
                AppStrings.mapPlaceholder,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
            ),
          ),
          const SizedBox(height: 8),
          Expanded(child: _buildBody(theme)),
        ],
      ),
    );
  }

  Widget _buildBody(ThemeData theme) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(_error!, textAlign: TextAlign.center),
              const SizedBox(height: 12),
              FilledButton(
                onPressed: _load,
                child: const Text(AppStrings.retry),
              ),
            ],
          ),
        ),
      );
    }
    if (_items.isEmpty) {
      return const Center(child: Text(AppStrings.emptyCoasters));
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
        itemCount: _items.length,
        separatorBuilder: (_, _) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          final c = _items[index];
          final available = c.canBook;
          return Card(
            clipBehavior: Clip.antiAlias,
            child: InkWell(
              onTap: () => _openDetail(c),
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(
                          Icons.airport_shuttle_rounded,
                          color: available
                              ? AppColors.success
                              : AppColors.danger,
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            c.name,
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        StatusChip.availability(
                          c.availabilityStatus ??
                              (available ? 'available' : 'busy'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    if (c.plateNumber != null)
                      Text('${c.plateNumber} · ${c.capacity ?? '—'} seats'),
                    if (c.pricing != null)
                      Text(
                        '${AppFormat.tzs(c.pricing!.pricePerKm)} / km'
                        ' · min ${AppFormat.km(c.pricing!.minKm)}',
                        style: theme.textTheme.bodySmall,
                      ),
                    if (c.latitude != null && c.longitude != null)
                      Text(
                        '${c.latitude!.toStringAsFixed(4)}, '
                        '${c.longitude!.toStringAsFixed(4)}',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    if (c.driver?.hasInfo == true) ...[
                      const SizedBox(height: 4),
                      Text(
                        'Driver: ${c.driver!.name}'
                        '${c.driver!.phone != null ? ' · ${c.driver!.phone}' : ''}',
                        style: theme.textTheme.bodySmall,
                      ),
                    ],
                    const SizedBox(height: 10),
                    Align(
                      alignment: Alignment.centerRight,
                      child: TextButton(
                        onPressed: available ? () => _openDetail(c) : null,
                        child: Text(
                          available ? AppStrings.bookNow : AppStrings.unavailable,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
