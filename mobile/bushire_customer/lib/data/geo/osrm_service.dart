import 'dart:convert';

import 'package:http/http.dart' as http;

import 'geo_point.dart';

/// Road distance + geometry via the public OSRM demo server (OpenStreetMap).
class OsrmService {
  OsrmService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  static const _base = 'https://router.project-osrm.org';

  /// Driving distance in kilometers, or null if routing fails.
  Future<double?> routeDistanceKm({
    required double fromLat,
    required double fromLng,
    required double toLat,
    required double toLng,
  }) async {
    final result = await route(
      fromLat: fromLat,
      fromLng: fromLng,
      toLat: toLat,
      toLng: toLng,
      includeGeometry: false,
    );
    return result?.distanceKm;
  }

  /// Full route with optional GeoJSON geometry for map polylines.
  Future<OsrmRouteResult?> route({
    required double fromLat,
    required double fromLng,
    required double toLat,
    required double toLng,
    bool includeGeometry = true,
  }) async {
    final overview = includeGeometry ? 'full' : 'false';
    final uri = Uri.parse(
      '$_base/route/v1/driving/'
      '$fromLng,$fromLat;$toLng,$toLat'
      '?overview=$overview&geometries=geojson'
      '&alternatives=false&steps=false',
    );

    try {
      final response = await _client
          .get(uri, headers: {'Accept': 'application/json'})
          .timeout(const Duration(seconds: 12));
      if (response.statusCode != 200) return null;

      final decoded = jsonDecode(response.body);
      if (decoded is! Map) return null;
      if (decoded['code']?.toString() != 'Ok') return null;

      final routes = decoded['routes'];
      if (routes is! List || routes.isEmpty) return null;
      final first = routes.first;
      if (first is! Map) return null;
      final meters = (first['distance'] as num?)?.toDouble();
      if (meters == null || meters <= 0) return null;

      final points = <GeoPoint>[];
      if (includeGeometry) {
        final geometry = first['geometry'];
        if (geometry is Map && geometry['coordinates'] is List) {
          for (final c in geometry['coordinates'] as List) {
            if (c is! List || c.length < 2) continue;
            final lon = (c[0] as num?)?.toDouble();
            final lat = (c[1] as num?)?.toDouble();
            if (lat == null || lon == null) continue;
            points.add(GeoPoint(latitude: lat, longitude: lon));
          }
        }
      }

      return OsrmRouteResult(
        distanceKm: meters / 1000.0,
        geometry: points,
      );
    } catch (_) {
      return null;
    }
  }
}

/// OSRM driving route summary for map preview + pricing distance.
class OsrmRouteResult {
  const OsrmRouteResult({
    required this.distanceKm,
    this.geometry = const [],
  });

  final double distanceKm;

  /// Ordered WGS84 points along the road (may be empty when overview=false).
  final List<GeoPoint> geometry;
}
