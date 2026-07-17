import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../core/strings.dart';
import '../core/theme/app_colors.dart';

/// OpenStreetMap view of the driver's live position.
/// Same stack as [RouteMapPreview] (`flutter_map` + OSM tiles) — no Google Maps SDK.
class LiveTrackMap extends StatefulWidget {
  const LiveTrackMap({
    super.key,
    this.latitude,
    this.longitude,
    this.label,
    this.stale = false,
    this.height = 260,
  });

  final double? latitude;
  final double? longitude;
  final String? label;
  final bool stale;
  final double height;

  bool get hasLocation => latitude != null && longitude != null;

  @override
  State<LiveTrackMap> createState() => _LiveTrackMapState();
}

class _LiveTrackMapState extends State<LiveTrackMap> {
  final MapController _mapController = MapController();

  LatLng get _point => LatLng(widget.latitude!, widget.longitude!);

  @override
  void dispose() {
    _mapController.dispose();
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant LiveTrackMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    final moved = oldWidget.latitude != widget.latitude ||
        oldWidget.longitude != widget.longitude;
    if (moved && widget.hasLocation) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        try {
          _mapController.move(_point, 15);
        } catch (_) {}
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    if (!widget.hasLocation) {
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
                      Icons.location_searching,
                      size: 36,
                      color: colorScheme.primary.withValues(alpha: 0.7),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      AppStrings.locationUnavailable,
                      textAlign: TextAlign.center,
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w600,
                        color: colorScheme.onSurface,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      AppStrings.trackMapEmptyHint,
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

    final markerColor =
        widget.stale ? AppColors.warning : colorScheme.primary;

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
                initialCenter: _point,
                initialZoom: 15,
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
                MarkerLayer(
                  markers: [
                    Marker(
                      point: _point,
                      width: 44,
                      height: 44,
                      alignment: Alignment.center,
                      child: _DriverMarker(color: markerColor),
                    ),
                  ],
                ),
              ],
            ),
            if (widget.label != null && widget.label!.isNotEmpty)
              Positioned(
                left: 12,
                top: 12,
                child: Material(
                  color: colorScheme.surface.withValues(alpha: 0.94),
                  borderRadius: BorderRadius.circular(20),
                  elevation: 2,
                  shadowColor: Colors.black.withValues(alpha: 0.12),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 8,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.directions_bus_filled_rounded,
                          size: 16,
                          color: markerColor,
                        ),
                        const SizedBox(width: 6),
                        ConstrainedBox(
                          constraints: const BoxConstraints(maxWidth: 180),
                          child: Text(
                            widget.label!,
                            overflow: TextOverflow.ellipsis,
                            style: theme.textTheme.labelLarge?.copyWith(
                              fontWeight: FontWeight.w700,
                              color: colorScheme.onSurface,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            if (widget.stale)
              Positioned(
                left: 12,
                bottom: 12,
                child: Material(
                  color: AppColors.warning.withValues(alpha: 0.95),
                  borderRadius: BorderRadius.circular(10),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 6,
                    ),
                    child: Text(
                      AppStrings.locationMayBeOutdated,
                      style: theme.textTheme.labelSmall?.copyWith(
                        color: Colors.black87,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
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

class _DriverMarker extends StatelessWidget {
  const _DriverMarker({required this.color});

  final Color color;

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
      child: const Icon(Icons.directions_bus, color: Colors.white, size: 22),
    );
  }
}
