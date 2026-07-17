import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/coaster_model.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/coaster_card.dart';
import '../../widgets/pill_tab_selector.dart';
import '../booking/booking_form_page.dart';
import '../booking/coaster_detail_page.dart';

/// Book tab: list coasters so the customer can start a special-hire booking.
///
/// Calls `GET /api/special-hire/customer/coasters` (optional `date`+`time`).
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

  /// Backend availability filter requires both date and time.
  bool get _hasFullFilter => _date != null && _time != null;

  bool get _hasPartialFilter =>
      (_date != null) != (_time != null); // XOR

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
      // Only send date+time together — API ignores a lone param.
      final list = await repo.listCoasters(
        date: _hasFullFilter ? _dateStr : null,
        time: _hasFullFilter ? _timeStr : null,
      );
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
        builder: (_) => CoasterDetailPage(
          coasterId: c.id,
          preview: c,
          initialHireDate: _date,
          initialHireTime: _time,
        ),
      ),
    );
  }

  /// Starts the booking flow (form → price → confirm → POST /bookings).
  void _startBooking(CoasterModel c) {
    if (!c.canBook) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => BookingFormPage(
          coaster: c,
          initialHireDate: _date,
          initialHireTime: _time,
        ),
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
      body: AppGradientBackground(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  AppStrings.browseHint,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
              child: Row(
                children: [
                  Expanded(
                    child: PillTabSelector(
                      labels: [AppStrings.anyDate, _dateStr ?? 'Pick date'],
                      icons: const [Icons.event_busy, Icons.calendar_today],
                      selectedIndex: _date == null ? 0 : 1,
                      onChanged: (i) {
                        if (i == 0) {
                          setState(() => _date = null);
                          _load();
                        } else {
                          _pickDate();
                        }
                      },
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: PillTabSelector(
                      labels: [AppStrings.anyTime, _timeStr ?? 'Pick time'],
                      icons: const [Icons.schedule_outlined, Icons.schedule],
                      selectedIndex: _time == null ? 0 : 1,
                      onChanged: (i) {
                        if (i == 0) {
                          setState(() => _time = null);
                          _load();
                        } else {
                          _pickTime();
                        }
                      },
                    ),
                  ),
                  if (_date != null || _time != null) ...[
                    const SizedBox(width: 4),
                    IconButton(
                      tooltip: AppStrings.clearFilters,
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
            if (_hasPartialFilter)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    AppStrings.filterBothRequired,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.tertiary,
                    ),
                  ),
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
      // API returns the full bookable fleet even when date+time are set
      // (those only mark busy). An empty list means the server sent [].
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.directions_bus_outlined,
                size: 48,
                color: theme.colorScheme.onSurfaceVariant,
              ),
              const SizedBox(height: 12),
              Text(
                AppStrings.noCoasters,
                textAlign: TextAlign.center,
                style: theme.textTheme.titleMedium,
              ),
              const SizedBox(height: 8),
              Text(
                _hasFullFilter
                    ? AppStrings.emptyCoastersHint
                    : AppStrings.noCoastersHint,
                textAlign: TextAlign.center,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _load,
                child: const Text(AppStrings.retry),
              ),
            ],
          ),
        ),
      );
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
          return CoasterCard(
            coaster: c,
            onTap: () => _openDetail(c),
            showLocation: true,
            trailingAction: Align(
              alignment: Alignment.centerRight,
              child: FilledButton(
                onPressed: available ? () => _startBooking(c) : null,
                child: Text(
                  available ? AppStrings.bookNow : AppStrings.unavailable,
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
