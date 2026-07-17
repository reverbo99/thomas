import '../api/api_client.dart';
import '../api/api_endpoints.dart';
import '../api/api_exception.dart';
import '../models/booking_requests.dart';
import '../models/coaster_model.dart';
import '../models/price_quote.dart';

/// Coaster browse (+ optional price calculator) against Special Hire Customer API.
///
/// Prefer sharing [AuthRepository.apiClient] so Bearer auth stays consistent.
class CoasterRepository {
  CoasterRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  /// GET `/coasters` — optional availability check.
  ///
  /// Backend applies schedule-conflict availability when [date] (`YYYY-MM-DD`)
  /// is present; [time] (`HH:MM`) is optional and sent when non-empty.
  Future<List<CoasterModel>> getCoasters({
    String? date,
    String? time,
  }) async {
    final query = <String, String>{};
    final d = date;
    final t = time;
    if (d != null && d.isNotEmpty) {
      query['date'] = d;
    }
    if (t != null && t.isNotEmpty) {
      query['time'] = t;
    }

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
  /// Provide either all four coordinates OR [distanceKm]. When both are set,
  /// client OSM distance is preferred (see [CalculatePriceRequest.toJson]).
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
    String? returnTime,
  }) async {
    final request = CalculatePriceRequest(
      coasterId: coasterId,
      hireDate: hireDate,
      hireTime: hireTime,
      pickupLatitude: pickupLatitude,
      pickupLongitude: pickupLongitude,
      dropoffLatitude: dropoffLatitude,
      dropoffLongitude: dropoffLongitude,
      distanceKm: distanceKm,
      routedDistanceKm: routedDistanceKm,
      distanceMode: distanceMode,
      returnDate: returnDate,
      returnTime: returnTime,
    );

    if (!request.hasCoordinates && distanceKm == null) {
      throw ApiException(
        message: 'Provide coordinates or distance_km for price calculation',
      );
    }

    final data = await _api.post(
      ApiEndpoints.calculatePrice,
      body: request.toJson(),
    );
    if (data is! Map) {
      throw ApiException(message: 'Unexpected price response');
    }
    return PriceQuote.fromJson(Map<String, dynamic>.from(data));
  }
}
