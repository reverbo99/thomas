import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../core/strings.dart';
import '../core/theme/app_colors.dart';
import '../data/geo/geo_point.dart';
import '../data/geo/place_result.dart';

/// OpenStreetMap preview of pickup/drop-off pins, optional route polyline,
/// and a distance chip. Presentational — pass already-resolved places/route.
class RouteMapPreview extends StatefulWidget {
  const RouteMapPreview({
    super.key,
    this.pickup,
    this.dropoff,
    this.routePoints = const [],
    this.distanceKm,
    this.routing = false,
    this.height = 220,
  });

  final PlaceResult? pickup;
  final PlaceResult? dropoff;

  /// Road geometry from OSRM (lon/lat already as [GeoPoint]).
  final List<GeoPoint> routePoints;

  final double? distanceKm;
  final bool routing;
  final double height;

  @override
  State<RouteMapPreview> createState() => _RouteMapPreviewState();
}

class _RouteMapPreviewState extends State<RouteMapPreview> {
  final MapController _mapController = MapController();

  bool get _hasAnyPoint => widget.pickup != null || widget.dropoff != null;

  List<LatLng> get _polyline {
    if (widget.routePoints.length >= 2) {
      return widget.routePoints
          .map((p) => LatLng(p.latitude, p.longitude))
          .toList(growable: false);
    }
    final a = widget.pickup;
    final b = widget.dropoff;
    if (a != null && b != null) {
      return [
        LatLng(a.latitude, a.longitude),
        LatLng(b.latitude, b.longitude),
      ];
    }
    return const [];
  }

  LatLng get _center {
    final a = widget.pickup;
    final b = widget.dropoff;
    if (a != null && b != null) {
      return LatLng(
        (a.latitude + b.latitude) / 2,
        (a.longitude + b.longitude) / 2,
      );
    }
    if (a != null) return LatLng(a.latitude, a.longitude);
    if (b != null) return LatLng(b.latitude, b.longitude);
    return const LatLng(-6.7924, 39.2083); // Dar es Salaam fallback
  }

  double get _zoom {
    if (widget.pickup != null && widget.dropoff != null) return 11;
    return 13;
  }

  @override
  void didUpdateWidget(covariant RouteMapPreview oldWidget) {
    super.didUpdateWidget(oldWidget);
    final moved = oldWidget.pickup?.latitude != widget.pickup?.latitude ||
        oldWidget.pickup?.longitude != widget.pickup?.longitude ||
        oldWidget.dropoff?.latitude != widget.dropoff?.latitude ||
        oldWidget.dropoff?.longitude != widget.dropoff?.longitude ||
        oldWidget.routePoints.length != widget.routePoints.length;
    if (moved && _hasAnyPoint) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        try {
          _fitBounds();
        } catch (_) {
          _mapController.move(_center, _zoom);
        }
      });
    }
  }

  void _fitBounds() {
    final pts = <LatLng>[
      if (widget.pickup != null)
        LatLng(widget.pickup!.latitude, widget.pickup!.longitude),
      if (widget.dropoff != null)
        LatLng(widget.dropoff!.latitude, widget.dropoff!.longitude),
      ..._polyline,
    ];
    if (pts.isEmpty) return;
    if (pts.length == 1) {
      _mapController.move(pts.first, 14);
      return;
    }

    var minLat = pts.first.latitude;
    var maxLat = pts.first.latitude;
    var minLng = pts.first.longitude;
    var maxLng = pts.first.longitude;
    for (final p in pts.skip(1)) {
      if (p.latitude < minLat) minLat = p.latitude;
      if (p.latitude > maxLat) maxLat = p.latitude;
      if (p.longitude < minLng) minLng = p.longitude;
      if (p.longitude > maxLng) maxLng = p.longitude;
    }
    final bounds = LatLngBounds(LatLng(minLat, minLng), LatLng(maxLat, maxLng));
    _mapController.fitCamera(
      CameraFit.bounds(bounds: bounds, padding: const EdgeInsets.all(36)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    if (!_hasAnyPoint) {
      return Card(
        margin: EdgeInsets.zero,
        clipBehavior: Clip.antiAlias,
        child: SizedBox(
          height: widget.height * 0.55,
          child: DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  AppColors.gradientStart,
                  colorScheme.surfaceContainerHighest.withValues(alpha: 0.4),
                ],
              ),
            ),
            child: Center(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.map_outlined,
                      size: 36,
                      color: colorScheme.primary.withValues(alpha: 0.7),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      AppStrings.mapPreviewHint,
                      textAlign: TextAlign.center,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      );
    }

    return Card(
      margin: EdgeInsets.zero,
      clipBehavior: Clip.antiAlias,
      child: SizedBox(
        height: widget.height,
        child: Stack(
          children: [
            FlutterMap(
              mapController: _mapController,
              options: MapOptions(
                initialCenter: _center,
                initialZoom: _zoom,
                interactionOptions: const InteractionOptions(
                  flags: InteractiveFlag.pinchZoom |
                      InteractiveFlag.drag |
                      InteractiveFlag.doubleTapZoom,
                ),
              ),
              children: [
                TileLayer(
                  urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                  userAgentPackageName: 'com.bushire.bushire_customer',
                ),
                if (_polyline.length >= 2)
                  PolylineLayer(
                    polylines: [
                      Polyline(
                        points: _polyline,
                        strokeWidth: 4.5,
                        color: colorScheme.primary.withValues(alpha: 0.85),
                        borderStrokeWidth: 2,
                        borderColor: Colors.white.withValues(alpha: 0.7),
                      ),
                    ],
                  ),
                MarkerLayer(
                  markers: [
                    if (widget.pickup != null)
                      Marker(
                        point: LatLng(
                          widget.pickup!.latitude,
                          widget.pickup!.longitude,
                        ),
                        width: 40,
                        height: 40,
                        child: _PinMarker(
                          color: AppColors.success,
                          icon: Icons.trip_origin,
                        ),
                      ),
                    if (widget.dropoff != null)
                      Marker(
                        point: LatLng(
                          widget.dropoff!.latitude,
                          widget.dropoff!.longitude,
                        ),
                        width: 40,
                        height: 40,
                        child: _PinMarker(
                          color: colorScheme.primary,
                          icon: Icons.place,
                        ),
                      ),
                  ],
                ),
              ],
            ),
            Positioned(
              left: 12,
              top: 12,
              child: _DistanceChip(
                distanceKm: widget.distanceKm,
                loading: widget.routing,
              ),
            ),
            Positioned(
              right: 12,
              bottom: 12,
              child: Material(
                color: colorScheme.surface.withValues(alpha: 0.92),
                borderRadius: BorderRadius.circular(10),
                elevation: 1,
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  child: Text(
                    '© OpenStreetMap',
                    style: theme.textTheme.labelSmall?.copyWith(
                      color: colorScheme.onSurfaceVariant,
                      fontSize: 10,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PinMarker extends StatelessWidget {
  const _PinMarker({required this.color, required this.icon});

  final Color color;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: color,
        shape: BoxShape.circle,
        border: Border.all(color: Colors.white, width: 2.5),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.22),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Icon(icon, color: Colors.white, size: 18),
    );
  }
}

class _DistanceChip extends StatelessWidget {
  const _DistanceChip({this.distanceKm, this.loading = false});

  final double? distanceKm;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    String label;
    if (loading) {
      label = AppStrings.calculatingRoute;
    } else if (distanceKm != null) {
      final km = distanceKm!;
      label = km >= 100
          ? '${km.toStringAsFixed(0)} km'
          : '${km.toStringAsFixed(1)} km';
    } else {
      label = AppStrings.routeDistancePending;
    }

    return Material(
      color: colorScheme.surface.withValues(alpha: 0.94),
      borderRadius: BorderRadius.circular(20),
      elevation: 2,
      shadowColor: Colors.black.withValues(alpha: 0.12),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (loading)
              SizedBox(
                width: 14,
                height: 14,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: colorScheme.primary,
                ),
              )
            else
              Icon(
                Icons.route_rounded,
                size: 16,
                color: colorScheme.primary,
              ),
            const SizedBox(width: 6),
            Text(
              label,
              style: theme.textTheme.labelLarge?.copyWith(
                fontWeight: FontWeight.w700,
                color: colorScheme.onSurface,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
