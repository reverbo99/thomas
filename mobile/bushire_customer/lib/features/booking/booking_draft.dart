import '../../data/models/coaster_model.dart';
import '../../data/models/price_quote.dart';

/// In-memory draft for form → price → confirm.
class BookingDraft {
  BookingDraft({
    required this.coaster,
    required this.pickupLocation,
    required this.dropoffLocation,
    required this.hireDate,
    required this.hireTime,
    required this.passengersCount,
    this.pickupLatitude,
    this.pickupLongitude,
    this.dropoffLatitude,
    this.dropoffLongitude,
    this.distanceKm,
    this.returnDate,
    this.returnTime,
    this.purpose,
    this.notes,
    this.quote,
  });

  final CoasterModel coaster;
  final String pickupLocation;
  final String dropoffLocation;
  final String hireDate;
  final String hireTime;
  final int passengersCount;
  final double? pickupLatitude;
  final double? pickupLongitude;
  final double? dropoffLatitude;
  final double? dropoffLongitude;
  final num? distanceKm;
  final String? returnDate;
  final String? returnTime;
  final String? purpose;
  final String? notes;
  PriceQuote? quote;
}
