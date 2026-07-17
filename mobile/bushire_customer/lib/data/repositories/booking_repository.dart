import '../api/api_client.dart';
import '../api/api_endpoints.dart';
import '../api/api_exception.dart';
import '../models/booking_model.dart';
import '../models/booking_requests.dart';
import '../models/price_quote.dart';

/// Bookings, pricing, payments, passengers, and tracking.
///
/// Inject the same [ApiClient] as [AuthRepository.apiClient].
class BookingRepository {
  BookingRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  /// POST `/calculate-price`
  ///
  /// Backend keys: `pickup_latitude` / `pickup_longitude` /
  /// `dropoff_latitude` / `dropoff_longitude`, `hire_date` / `hire_time`,
  /// optional `return_date` / `return_time`, and `distance_km` (or coords).
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

  /// POST `/bookings` — [distanceKm] and [totalAmount] are required by the API.
  ///
  /// Place names go in [pickupLocation] / [dropoffLocation]; optional lat/lng
  /// use `pickup_latitude` etc. Start = [hireDate]+[hireTime]; return =
  /// [returnDate]+[returnTime].
  Future<BookingModel> createBooking({
    required int coasterId,
    required String pickupLocation,
    required String dropoffLocation,
    required String hireDate,
    required String hireTime,
    required int passengersCount,
    required num distanceKm,
    required num totalAmount,
    double? pickupLatitude,
    double? pickupLongitude,
    double? dropoffLatitude,
    double? dropoffLongitude,
    String? returnDate,
    String? returnTime,
    String? purpose,
    String? notes,
    String? phone,
    String? contact,
  }) async {
    final request = CreateBookingRequest(
      coasterId: coasterId,
      pickupLocation: pickupLocation,
      dropoffLocation: dropoffLocation,
      hireDate: hireDate,
      hireTime: hireTime,
      passengersCount: passengersCount,
      distanceKm: distanceKm,
      totalAmount: totalAmount,
      pickupLatitude: pickupLatitude,
      pickupLongitude: pickupLongitude,
      dropoffLatitude: dropoffLatitude,
      dropoffLongitude: dropoffLongitude,
      returnDate: returnDate,
      returnTime: returnTime,
      purpose: purpose,
      notes: notes,
      phone: phone,
      contact: contact,
    );

    final data = await _api.post(
      ApiEndpoints.bookings,
      body: request.toJson(),
    );
    if (data is! Map) {
      throw ApiException(message: 'Unexpected booking response');
    }
    return BookingModel.fromJson(Map<String, dynamic>.from(data));
  }

  /// GET `/bookings` — optional [status], [perPage].
  Future<List<BookingModel>> getBookings({
    String? status,
    int? perPage,
  }) async {
    final query = <String, String>{};
    if (status != null && status.isNotEmpty) query['status'] = status;
    if (perPage != null) query['per_page'] = '$perPage';

    final data = await _api.get(
      ApiEndpoints.bookings,
      query: query.isEmpty ? null : query,
    );

    final list = _extractList(data);
    return list
        .whereType<Map>()
        .map((e) => BookingModel.fromJson(Map<String, dynamic>.from(e)))
        .toList(growable: false);
  }

  /// Alias used by existing UI screens.
  Future<List<BookingModel>> listBookings({
    String? status,
    int? perPage,
  }) =>
      getBookings(status: status, perPage: perPage);

  /// GET `/bookings/{id}`
  Future<BookingModel> getBooking(int id) async {
    final data = await _api.get(ApiEndpoints.booking(id));
    if (data is! Map) {
      throw ApiException(message: 'Unexpected booking response');
    }
    return BookingModel.fromJson(Map<String, dynamic>.from(data));
  }

  /// POST `/bookings/{id}/cancel`
  Future<BookingModel> cancelBooking(int id) async {
    final data = await _api.post(ApiEndpoints.cancelBooking(id));
    if (data is! Map) {
      throw ApiException(message: 'Unexpected cancel response');
    }
    return BookingModel.fromJson(Map<String, dynamic>.from(data));
  }

  /// GET `/bookings/{id}/track` — single fetch; UI should poll 15–30s while in progress.
  Future<TrackInfo> trackBooking(int id) async {
    final data = await _api.get(ApiEndpoints.trackBooking(id));
    if (data is! Map) {
      throw ApiException(message: 'Unexpected track response');
    }
    return TrackInfo.fromJson(Map<String, dynamic>.from(data));
  }

  /// POST `/bookings/{id}/pay-deposit` — body `{ phone }`
  Future<PaymentInitResult> payDeposit({
    required int bookingId,
    required String phone,
  }) async {
    final data = await _api.post(
      ApiEndpoints.payDeposit(bookingId),
      body: {'phone': phone},
    );
    return _paymentInit(data);
  }

  /// POST `/bookings/{id}/pay-balance` — body `{ phone }`
  Future<PaymentInitResult> payBalance({
    required int bookingId,
    required String phone,
  }) async {
    final data = await _api.post(
      ApiEndpoints.payBalance(bookingId),
      body: {'phone': phone},
    );
    return _paymentInit(data);
  }

  /// POST `/bookings/{id}/sync-payment` — optional `{ reference }`
  Future<BookingModel> syncPayment({
    required int bookingId,
    String? reference,
  }) async {
    final body = <String, dynamic>{};
    if (reference != null && reference.isNotEmpty) {
      body['reference'] = reference;
    }
    final data = await _api.post(
      ApiEndpoints.syncPayment(bookingId),
      body: body.isEmpty ? null : body,
    );
    if (data is! Map) {
      throw ApiException(message: 'Unexpected sync-payment response');
    }
    return BookingModel.fromJson(Map<String, dynamic>.from(data));
  }

  /// POST `/bookings/{id}/passengers` — body `{ seat_names: [...] }`
  Future<BookingModel> submitPassengers({
    required int bookingId,
    required List<String> seatNames,
  }) async {
    final data = await _api.post(
      ApiEndpoints.passengers(bookingId),
      body: {'seat_names': seatNames},
    );
    if (data is! Map) {
      throw ApiException(message: 'Unexpected passengers response');
    }
    return BookingModel.fromJson(Map<String, dynamic>.from(data));
  }

  PaymentInitResult _paymentInit(dynamic data) {
    if (data is Map) {
      return PaymentInitResult.fromJson(Map<String, dynamic>.from(data));
    }
    return const PaymentInitResult();
  }

  List<dynamic> _extractList(dynamic data) {
    if (data is List) return data;
    if (data is Map) {
      final inner = data['data'];
      if (inner is List) return inner;
    }
    return const [];
  }
}
