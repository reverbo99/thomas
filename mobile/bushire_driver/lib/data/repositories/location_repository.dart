import '../api/api_client.dart';
import '../api/api_endpoints.dart';
import '../api/api_exception.dart';

/// Result of POST `/location`.
class LocationUpdateResult {
  const LocationUpdateResult({
    this.coasterId,
    this.latitude,
    this.longitude,
    this.lastUpdated,
  });

  final int? coasterId;
  final double? latitude;
  final double? longitude;
  final String? lastUpdated;

  factory LocationUpdateResult.fromJson(Map<String, dynamic> json) {
    return LocationUpdateResult(
      coasterId: _asIntOrNull(json['coaster_id']),
      latitude: _asDoubleOrNull(json['latitude']),
      longitude: _asDoubleOrNull(json['longitude']),
      lastUpdated: json['last_updated']?.toString(),
    );
  }
}

/// GPS pings for the assigned coaster while a trip is in progress.
class LocationRepository {
  LocationRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  /// POST `/location` — body `{ latitude, longitude }`.
  Future<LocationUpdateResult> updateLocation({
    required double latitude,
    required double longitude,
  }) async {
    final data = await _api.post(
      ApiEndpoints.location,
      body: {'latitude': latitude, 'longitude': longitude},
    );
    if (data is! Map) {
      throw ApiException(message: 'Unexpected location response');
    }
    return LocationUpdateResult.fromJson(Map<String, dynamic>.from(data));
  }
}

int? _asIntOrNull(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse(value.toString());
}

double? _asDoubleOrNull(dynamic value) {
  if (value == null) return null;
  if (value is double) return value;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString());
}
