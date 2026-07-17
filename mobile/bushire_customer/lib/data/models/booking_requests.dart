/// Request bodies for Special Hire Customer price + booking endpoints.
///
/// Field names match Laravel `CustomerApiController` validation exactly
/// (`pickup_latitude`, not `pickup_lat`; `hire_date`/`hire_time`, not a
/// combined datetime). Place names/addresses from geocoding belong in
/// [CreateBookingRequest.pickupLocation] / [dropoffLocation].

/// `POST /calculate-price`
class CalculatePriceRequest {
  const CalculatePriceRequest({
    required this.coasterId,
    required this.hireDate,
    required this.hireTime,
    this.pickupLatitude,
    this.pickupLongitude,
    this.dropoffLatitude,
    this.dropoffLongitude,
    this.distanceKm,
    this.routedDistanceKm,
    this.distanceMode,
    this.returnDate,
    this.returnTime,
  });

  final int coasterId;

  /// `YYYY-MM-DD`
  final String hireDate;

  /// `HH:MM` (24h)
  final String hireTime;

  final double? pickupLatitude;
  final double? pickupLongitude;
  final double? dropoffLatitude;
  final double? dropoffLongitude;

  /// Client- or OSM-computed distance. Preferred when set (backend no longer
  /// overwrites with haversine if this is present).
  final num? distanceKm;

  /// Optional routed distance with [distanceMode] `route`.
  final num? routedDistanceKm;

  /// `straight` | `route`
  final String? distanceMode;

  /// `YYYY-MM-DD` — used for multi-day availability window.
  final String? returnDate;

  /// `HH:MM` — accepted for parity with create booking (availability is date-based).
  final String? returnTime;

  bool get hasCoordinates =>
      pickupLatitude != null &&
      pickupLongitude != null &&
      dropoffLatitude != null &&
      dropoffLongitude != null;

  /// Builds JSON using backend snake_case keys.
  ///
  /// Sends coordinates when all four are present, plus optional client
  /// `distance_km` (preferred by the API when set).
  Map<String, dynamic> toJson() {
    final body = <String, dynamic>{
      'coaster_id': coasterId,
      'hire_date': hireDate,
      'hire_time': hireTime,
    };

    if (hasCoordinates) {
      body['pickup_latitude'] = pickupLatitude;
      body['pickup_longitude'] = pickupLongitude;
      body['dropoff_latitude'] = dropoffLatitude;
      body['dropoff_longitude'] = dropoffLongitude;
    }

    if (distanceKm != null) {
      body['distance_km'] = distanceKm;
    }
    if (routedDistanceKm != null) {
      body['routed_distance_km'] = routedDistanceKm;
    }
    if (distanceMode != null && distanceMode!.isNotEmpty) {
      body['distance_mode'] = distanceMode;
    }

    if (returnDate != null && returnDate!.isNotEmpty) {
      body['return_date'] = returnDate;
    }
    if (returnTime != null && returnTime!.isNotEmpty) {
      body['return_time'] = returnTime;
    }

    return body;
  }
}

/// `POST /bookings`
class CreateBookingRequest {
  const CreateBookingRequest({
    required this.coasterId,
    required this.pickupLocation,
    required this.dropoffLocation,
    required this.hireDate,
    required this.hireTime,
    required this.passengersCount,
    required this.distanceKm,
    required this.totalAmount,
    this.pickupLatitude,
    this.pickupLongitude,
    this.dropoffLatitude,
    this.dropoffLongitude,
    this.returnDate,
    this.returnTime,
    this.purpose,
    this.notes,
    this.phone,
    this.contact,
  });

  final int coasterId;

  /// Place name / address (from Nominatim or free text).
  final String pickupLocation;
  final String dropoffLocation;

  /// `YYYY-MM-DD`
  final String hireDate;

  /// `HH:MM`
  final String hireTime;

  final int passengersCount;

  /// Required by API — client-calculated (OSM route or manual).
  final num distanceKm;

  /// Required by API — from `POST /calculate-price` quote.
  final num totalAmount;

  final double? pickupLatitude;
  final double? pickupLongitude;
  final double? dropoffLatitude;
  final double? dropoffLongitude;

  /// `YYYY-MM-DD`
  final String? returnDate;

  /// `HH:MM`
  final String? returnTime;

  final String? purpose;
  final String? notes;
  final String? phone;
  final String? contact;

  Map<String, dynamic> toJson() {
    final body = <String, dynamic>{
      'coaster_id': coasterId,
      'pickup_location': pickupLocation,
      'dropoff_location': dropoffLocation,
      'hire_date': hireDate,
      'hire_time': hireTime,
      'passengers_count': passengersCount,
      'distance_km': distanceKm,
      'total_amount': totalAmount,
    };

    if (pickupLatitude != null) body['pickup_latitude'] = pickupLatitude;
    if (pickupLongitude != null) body['pickup_longitude'] = pickupLongitude;
    if (dropoffLatitude != null) body['dropoff_latitude'] = dropoffLatitude;
    if (dropoffLongitude != null) body['dropoff_longitude'] = dropoffLongitude;
    if (returnDate != null && returnDate!.isNotEmpty) {
      body['return_date'] = returnDate;
    }
    if (returnTime != null && returnTime!.isNotEmpty) {
      body['return_time'] = returnTime;
    }
    if (purpose != null && purpose!.isNotEmpty) body['purpose'] = purpose;
    if (notes != null && notes!.isNotEmpty) body['notes'] = notes;
    if (phone != null && phone!.isNotEmpty) body['phone'] = phone;
    if (contact != null && contact!.isNotEmpty) body['contact'] = contact;

    return body;
  }
}

/// Helpers to split [DateTime] into API date/time strings.
abstract final class BookingDateTimeFormat {
  /// `YYYY-MM-DD`
  static String dateOnly(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-'
      '${d.month.toString().padLeft(2, '0')}-'
      '${d.day.toString().padLeft(2, '0')}';

  /// `HH:MM` (24h) from hour/minute.
  static String timeHm(int hour, int minute) =>
      '${hour.toString().padLeft(2, '0')}:'
      '${minute.toString().padLeft(2, '0')}';

  /// `HH:MM` from a [DateTime].
  static String timeFromDateTime(DateTime d) => timeHm(d.hour, d.minute);
}
