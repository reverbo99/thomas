import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/format.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/track_info.dart';
import '../../widgets/live_track_map.dart';
import '../../widgets/status_chip.dart';

/// Live OSM map of the driver + coordinates / last seen; polls every 20s.
class TrackPage extends StatefulWidget {
  const TrackPage({
    super.key,
    required this.bookingId,
    this.orderCode,
    this.pollInterval = const Duration(seconds: 20),
  });

  final int bookingId;
  final String? orderCode;
  final Duration pollInterval;

  @override
  State<TrackPage> createState() => _TrackPageState();
}

class _TrackPageState extends State<TrackPage> {
  TrackInfo? _info;
  Timer? _timer;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    // Defer AppScope.of(context) until after initState (InheritedWidget rule).
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _refresh(initial: true);
      _timer = Timer.periodic(widget.pollInterval, (_) => _refresh());
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _refresh({bool initial = false}) async {
    if (initial) setState(() => _loading = true);
    try {
      final info = await AppScope.of(context)
          .bookingRepository
          .trackBooking(widget.bookingId);
      if (!mounted) return;
      setState(() {
        _info = info;
        _error = null;
        _loading = false;
      });
      final status = info.orderStatus?.toLowerCase();
      if (status == 'completed' || status == 'cancelled') {
        _timer?.cancel();
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e is ApiException ? e.message : e.toString();
      });
    }
  }

  bool _isStale(String? iso) {
    if (iso == null || iso.isEmpty) return true;
    final dt = DateTime.tryParse(iso);
    if (dt == null) return true;
    return DateTime.now().difference(dt.toLocal()) > const Duration(minutes: 5);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final info = _info;
    final hasLocation = info?.latitude != null && info?.longitude != null;

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.orderCode ?? AppStrings.trackTrip),
        actions: [
          IconButton(
            onPressed: () => _refresh(initial: true),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: SafeArea(
        child: _loading && info == null
            ? const Center(child: CircularProgressIndicator())
            : ListView(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
                children: [
                  Text(
                    AppStrings.trackPollingHint,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: colorScheme.onSurfaceVariant,
                    ),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(
                      _error!,
                      style: TextStyle(color: colorScheme.error),
                    ),
                  ],
                  const SizedBox(height: 16),
                  LiveTrackMap(
                    latitude: info?.latitude,
                    longitude: info?.longitude,
                    label: info?.plateNumber ?? info?.coasterName,
                    stale: info != null &&
                        _isStale(info.lastLocationUpdate),
                  ),
                  const SizedBox(height: 16),
                  if (info?.orderStatus != null)
                    Align(
                      alignment: Alignment.centerLeft,
                      child: StatusChip.order(info!.orderStatus),
                    ),
                  const SizedBox(height: 12),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (info?.coasterName != null) ...[
                            Text(
                              info!.coasterName!,
                              style: theme.textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            if (info.plateNumber != null)
                              Text(
                                info.plateNumber!,
                                style: theme.textTheme.bodySmall?.copyWith(
                                  color: colorScheme.onSurfaceVariant,
                                ),
                              ),
                            const SizedBox(height: 12),
                          ],
                          if (!hasLocation)
                            Text(
                              AppStrings.locationUnavailable,
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: colorScheme.onSurfaceVariant,
                              ),
                            )
                          else ...[
                            _Line(
                              'Latitude',
                              info!.latitude!.toStringAsFixed(5),
                            ),
                            _Line(
                              'Longitude',
                              info.longitude!.toStringAsFixed(5),
                            ),
                          ],
                          const SizedBox(height: 8),
                          _Line(
                            AppStrings.lastSeen,
                            AppFormat.lastSeen(info?.lastLocationUpdate),
                          ),
                          if (_isStale(info?.lastLocationUpdate)) ...[
                            const SizedBox(height: 8),
                            Text(
                              AppStrings.locationMayBeOutdated,
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: colorScheme.error,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}

class _Line extends StatelessWidget {
  const _Line(this.label, this.value);

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ),
          Expanded(child: Text(value, style: theme.textTheme.bodyMedium)),
        ],
      ),
    );
  }
}
