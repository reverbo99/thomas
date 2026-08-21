/// Special hire order returned by customer booking endpoints.
class BookingModel {
  const BookingModel({
    required this.id,
    this.orderCode,
    this.coasterId,
    this.customerName,
    this.customerPhone,
    this.customerEmail,
    this.pickupLocation,
    this.pickupLatitude,
    this.pickupLongitude,
    this.dropoffLocation,
    this.dropoffLatitude,
    this.dropoffLongitude,
    this.hireDate,
    this.hireTime,
    this.returnDate,
    this.returnTime,
    this.passengersCount,
    this.purpose,
    this.notes,
    this.distanceKm,
    this.basePrice,
    this.pricePerKm,
    this.kmAmount,
    this.surchargePercent,
    this.surchargeAmount,
    this.totalAmount,
    this.depositAmount,
    this.balanceAmount,
    this.depositPaidAt,
    this.balancePaidAt,
    this.ownerAcceptedAt,
    this.orderStatus,
    this.paymentStatus,
    this.paymentMethod,
    this.hireNextStep,
    this.coasterName,
    this.coasterPlate,
    this.coasterCapacity,
    this.passengerSeats,
  });

  final int id;
  final String? orderCode;
  final int? coasterId;
  final String? customerName;
  final String? customerPhone;
  final String? customerEmail;
  final String? pickupLocation;
  final double? pickupLatitude;
  final double? pickupLongitude;
  final String? dropoffLocation;
  final double? dropoffLatitude;
  final double? dropoffLongitude;
  final String? hireDate;
  final String? hireTime;
  final String? returnDate;
  final String? returnTime;
  final int? passengersCount;
  final String? purpose;
  final String? notes;
  final num? distanceKm;
  final num? basePrice;
  final num? pricePerKm;
  final num? kmAmount;
  final num? surchargePercent;
  final num? surchargeAmount;
  final num? totalAmount;
  final num? depositAmount;
  final num? balanceAmount;
  final String? depositPaidAt;
  final String? balancePaidAt;
  final String? ownerAcceptedAt;
  final String? orderStatus;
  final String? paymentStatus;
  final String? paymentMethod;

  /// `pay_deposit` | `wait_owner` | `pay_balance` | `enter_passengers` | `done` | `legacy_pending`
  final String? hireNextStep;
  final String? coasterName;
  final String? coasterPlate;
  final int? coasterCapacity;
  final List<String>? passengerSeats;

  bool get canCancel {
    final s = orderStatus?.toLowerCase();
    if (s == null || s == 'completed' || s == 'cancelled') return false;
    if (balancePaidAt != null) return false;
    return s == 'pending' || s == 'confirmed' || s == 'in_progress';
  }

  bool get canTrack => orderStatus?.toLowerCase() == 'in_progress';

  /// Any past / existing trip can be used as a reorder source.
  bool get canReorder => id > 0;

  bool get canTransfer {
    final s = orderStatus?.toLowerCase();
    if (s == null) return false;
    return s != 'completed' && s != 'cancelled';
  }

  bool get canRequestRefund {
    final pay = paymentStatus?.toLowerCase();
    if (pay == 'refunded' || pay == 'refund_pending') return false;
    if (pay == 'paid' || pay == 'deposit_paid') return true;
    return depositPaidAt != null || balancePaidAt != null;
  }

  bool get canDownloadReceipt {
    final pay = paymentStatus?.toLowerCase();
    if (pay == 'paid' || pay == 'refund_pending') return true;
    return depositPaidAt != null || balancePaidAt != null;
  }

  bool get needsDeposit => hireNextStep == 'pay_deposit';
  bool get needsBalance => hireNextStep == 'pay_balance';
  bool get needsPassengers => hireNextStep == 'enter_passengers';
  bool get waitingOwner => hireNextStep == 'wait_owner';

  factory BookingModel.fromJson(Map<String, dynamic> json) {
    final coaster = json['coaster'];
    Map<String, dynamic>? coasterMap;
    if (coaster is Map) coasterMap = Map<String, dynamic>.from(coaster);

    List<String>? seats;
    final seatsRaw = json['passenger_seats'];
    if (seatsRaw is List) {
      seats = seatsRaw.map((e) => e.toString()).toList();
    }

    return BookingModel(
      id: _asInt(json['id']),
      orderCode: json['order_code']?.toString(),
      coasterId: _asIntOrNull(json['coaster_id'] ?? coasterMap?['id']),
      customerName: json['customer_name']?.toString(),
      customerPhone: json['customer_phone']?.toString(),
      customerEmail: json['customer_email']?.toString(),
      pickupLocation: json['pickup_location']?.toString(),
      pickupLatitude: _asDoubleOrNull(json['pickup_latitude']),
      pickupLongitude: _asDoubleOrNull(json['pickup_longitude']),
      dropoffLocation: json['dropoff_location']?.toString(),
      dropoffLatitude: _asDoubleOrNull(json['dropoff_latitude']),
      dropoffLongitude: _asDoubleOrNull(json['dropoff_longitude']),
      hireDate: _dateOnly(json['hire_date']),
      hireTime: _timeHm(json['hire_time']),
      returnDate: _dateOnly(json['return_date']),
      returnTime: _timeHm(json['return_time']),
      passengersCount: _asIntOrNull(json['passengers_count']),
      purpose: json['purpose']?.toString(),
      notes: json['notes']?.toString(),
      distanceKm: _asNumOrNull(json['distance_km']),
      basePrice: _asNumOrNull(json['base_price']),
      pricePerKm: _asNumOrNull(json['price_per_km']),
      kmAmount: _asNumOrNull(json['km_amount']),
      surchargePercent: _asNumOrNull(json['surcharge_percent']),
      surchargeAmount: _asNumOrNull(json['surcharge_amount']),
      totalAmount: _asNumOrNull(json['total_amount']),
      depositAmount: _asNumOrNull(json['deposit_amount']),
      balanceAmount: _asNumOrNull(json['balance_amount']),
      depositPaidAt: json['deposit_paid_at']?.toString(),
      balancePaidAt: json['balance_paid_at']?.toString(),
      ownerAcceptedAt: json['owner_accepted_at']?.toString(),
      orderStatus: json['order_status']?.toString(),
      paymentStatus: json['payment_status']?.toString(),
      paymentMethod: json['payment_method']?.toString(),
      hireNextStep: json['hire_next_step']?.toString(),
      coasterName: coasterMap?['name']?.toString(),
      coasterPlate: coasterMap?['plate_number']?.toString(),
      coasterCapacity: _asIntOrNull(coasterMap?['capacity']),
      passengerSeats: seats,
    );
  }
}

/// Live track payload from `GET /bookings/{id}/track`.
class TrackInfo {
  const TrackInfo({
    required this.orderId,
    this.orderCode,
    this.orderStatus,
    this.coasterId,
    this.coasterName,
    this.plateNumber,
    this.latitude,
    this.longitude,
    this.lastLocationUpdate,
  });

  final int orderId;
  final String? orderCode;
  final String? orderStatus;
  final int? coasterId;
  final String? coasterName;
  final String? plateNumber;
  final double? latitude;
  final double? longitude;
  final String? lastLocationUpdate;

  bool get hasLocation => latitude != null && longitude != null;

  factory TrackInfo.fromJson(Map<String, dynamic> json) {
    final coaster = json['coaster'];
    Map<String, dynamic>? c;
    if (coaster is Map) c = Map<String, dynamic>.from(coaster);
    return TrackInfo(
      orderId: _asInt(json['order_id'] ?? json['id']),
      orderCode: json['order_code']?.toString(),
      orderStatus: json['order_status']?.toString(),
      coasterId: _asIntOrNull(c?['id']),
      coasterName: c?['name']?.toString(),
      plateNumber: c?['plate_number']?.toString(),
      latitude: _asDoubleOrNull(c?['latitude']),
      longitude: _asDoubleOrNull(c?['longitude']),
      lastLocationUpdate: c?['last_location_update']?.toString(),
    );
  }
}

/// USSD / ClickPesa initiate response from prepare-payment / pay-deposit / pay-balance.
class PaymentInitResult {
  const PaymentInitResult({
    this.message,
    this.orderReference,
    this.clickpesa,
    this.intentId,
    this.amount,
    this.booking,
  });

  final String? message;
  final String? orderReference;
  final dynamic clickpesa;
  final int? intentId;
  final num? amount;
  final BookingModel? booking;

  factory PaymentInitResult.fromJson(
    Map<String, dynamic> json, {
    String? message,
  }) {
    return PaymentInitResult(
      message: message ?? json['message']?.toString(),
      orderReference: json['order_reference']?.toString(),
      clickpesa: json['clickpesa'],
      intentId: _asIntOrNull(json['intent_id']),
      amount: _asNumOrNull(json['amount']),
      booking: json['booking'] is Map
          ? BookingModel.fromJson(Map<String, dynamic>.from(json['booking']))
          : null,
    );
  }
}

int _asInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _asIntOrNull(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse(value.toString());
}

num? _asNumOrNull(dynamic value) {
  if (value == null) return null;
  if (value is num) return value;
  return num.tryParse(value.toString());
}

double? _asDoubleOrNull(dynamic value) {
  if (value == null) return null;
  if (value is double) return value;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString());
}

String? _dateOnly(dynamic value) {
  if (value == null) return null;
  final s = value.toString();
  if (s.length >= 10) return s.substring(0, 10);
  return s;
}

String? _timeHm(dynamic value) {
  if (value == null) return null;
  final s = value.toString();
  if (s.length >= 5) return s.substring(0, 5);
  return s;
}
