import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/booking_model.dart';
import '../../widgets/trip_card.dart';
import 'trip_detail_page.dart';

/// Trip list with status filters. Exposes [TripsPageState.load] for [MainShell].
class TripsPage extends StatefulWidget {
  const TripsPage({super.key});

  @override
  State<TripsPage> createState() => TripsPageState();
}

class TripsPageState extends State<TripsPage> {
  static const _filters = <String?>[
    null,
    'pending',
    'confirmed',
    'in_progress',
    'completed',
    'cancelled',
  ];

  String? _status;
  List<BookingModel> _items = const [];
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => load());
  }

  Future<void> load() => _reload();

  Future<void> _reload() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final items = await AppScope.of(context).bookingRepository.listBookings(
            status: _status,
          );
      if (!mounted) return;
      setState(() => _items = items);
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

  void _openTrip(BookingModel trip) {
    Navigator.of(context)
        .push(
          MaterialPageRoute<void>(
            builder: (_) => TripDetailPage(bookingId: trip.id, preview: trip),
          ),
        )
        .then((_) => load());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(AppStrings.myTrips),
        actions: [
          IconButton(
            onPressed: _loading ? null : load,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            SizedBox(
              height: 48,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                itemCount: _filters.length,
                separatorBuilder: (_, _) => const SizedBox(width: 8),
                itemBuilder: (context, index) {
                  final value = _filters[index];
                  final selected = _status == value;
                  final label = value == null
                      ? 'All'
                      : value.replaceAll('_', ' ');
                  return FilterChip(
                    label: Text(
                      '${label[0].toUpperCase()}${label.substring(1)}',
                    ),
                    selected: selected,
                    onSelected: (_) {
                      setState(() => _status = value);
                      load();
                    },
                  );
                },
              ),
            ),
            if (_error != null)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Text(
                  _error!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _items.isEmpty
                      ? Center(
                          child: Padding(
                            padding: const EdgeInsets.all(32),
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Text(AppStrings.emptyTrips),
                                const SizedBox(height: 12),
                                FilledButton.tonal(
                                  onPressed: load,
                                  child: const Text(AppStrings.retry),
                                ),
                              ],
                            ),
                          ),
                        )
                      : RefreshIndicator(
                          onRefresh: load,
                          child: ListView.separated(
                            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                            itemCount: _items.length,
                            separatorBuilder: (_, _) =>
                                const SizedBox(height: 10),
                            itemBuilder: (context, index) {
                              final trip = _items[index];
                              return TripCard(
                                booking: trip,
                                onTap: () => _openTrip(trip),
                              );
                            },
                          ),
                        ),
            ),
          ],
        ),
      ),
    );
  }
}
