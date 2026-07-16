import '../api/api_client.dart';
import '../api/api_endpoints.dart';
import '../api/api_exception.dart';
import '../models/coaster_model.dart';
import '../models/price_quote.dart';

/// Coaster browse (+ optional price calculator) against Special Hire Customer API.
///
/// Prefer sharing [AuthRepository.apiClient] so Bearer auth stays consistent.
class CoasterRepository {
  CoasterRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  /// GET `/coasters` — optional [date] (`YYYY-MM-DD`) + [time] (`HH:MM`).
  Future<List<CoasterModel>> getCoasters({
    String? date,
    String? time,
  }) async {
    final query = <String, String>{};
    if (date != null && date.isNotEmpty) query['date'] = date;
    if (time != null && time.isNotEmpty) query['time'] = time;

    final data = await _api.get(
      ApiEndpoints.coasters,
      query: query.isEmpty ? null : query,
    );

    if (data is! List) {
      throw ApiException(message: 'Unexpected coasters response');
    }

    return data
        .whereType<Map>()
        .map((e) => CoasterModel.fromJson(Map<String, dynamic>.from(e)))
        .toList(growable: false);
  }

  /// Alias used by existing UI screens.
  Future<List<CoasterModel>> listCoasters({
    String? date,
    String? time,
  }) =>
      getCoasters(date: date, time: time);

  /// GET `/coasters/{id}`
  Future<CoasterModel> getCoaster(int id) async {
    final data = await _api.get(ApiEndpoints.coaster(id));
    if (data is! Map) {
      throw ApiException(message: 'Unexpected coaster response');
    }
    return CoasterModel.fromJson(Map<String, dynamic>.from(data));
  }

  /// POST `/calculate-price` (also available on [BookingRepository]).
  ///
  /// Provide either all four coordinates OR [distanceKm].
  Future<PriceQuote> calculatePrice({
    required int coasterId,
    required String hireDate,
    required String hireTime,
    double? pickupLatitude,
    double? pickupLongitude,
    double? dropoffLatitude,
    double? dropoffLongitude,
    num? distanceKm,
    num? routedDistanceKm,
    String? distanceMode,
    String? returnDate,
  }) async {
    final body = <String, dynamic>{
      'coaster_id': coasterId,
      'hire_date': hireDate,
      'hire_time': hireTime,
    };

    final hasCoords = pickupLatitude != null &&
        pickupLongitude != null &&
        dropoffLatitude != null &&
        dropoffLongitude != null;

    if (hasCoords) {
      body['pickup_latitude'] = pickupLatitude;
      body['pickup_longitude'] = pickupLongitude;
      body['dropoff_latitude'] = dropoffLatitude;
      body['dropoff_longitude'] = dropoffLongitude;
    }
    if (distanceKm != null) body['distance_km'] = distanceKm;
    if (routedDistanceKm != null) body['routed_distance_km'] = routedDistanceKm;
    if (distanceMode != null) body['distance_mode'] = distanceMode;
    if (returnDate != null && returnDate.isNotEmpty) {
      body['return_date'] = returnDate;
    }

    if (!hasCoords && distanceKm == null) {
      throw ApiException(
        message: 'Provide coordinates or distance_km for price calculation',
      );
    }

    final data = await _api.post(ApiEndpoints.calculatePrice, body: body);
    if (data is! Map) {
      throw ApiException(message: 'Unexpected price response');
    }
    return PriceQuote.fromJson(Map<String, dynamic>.from(data));
  }
}
