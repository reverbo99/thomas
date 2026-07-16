import 'dart:collection';

/// Special hire order for the driver API (`/orders`, hire-requests, schedule, history).
class OrderModel {
  const OrderModel({
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
    this.passengerSeats = const [],
    this.paymentMethod,
    this.orderStatus,
    this.paymentStatus,
    this.hireNextStep,
    this.coasterName,
    this.coasterPlate,
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
  final List<String> passengerSeats;
  final String? paymentMethod;
  final String? orderStatus;
  final String? paymentStatus;
  final String? hireNextStep;
  final String? coasterName;
  final String? coasterPlate;

  bool get isPending => orderStatus?.toLowerCase() == 'pending';
  bool get isConfirmed => orderStatus?.toLowerCase() == 'confirmed';
  bool get isInProgress => orderStatus?.toLowerCase() == 'in_progress';
  bool get isCompleted => orderStatus?.toLowerCase() == 'completed';
  bool get isCancelled => orderStatus?.toLowerCase() == 'cancelled';

  /// Driver may start a confirmed trip.
  bool get canStartTrip => isConfirmed;

  /// Driver may complete an in-progress trip.
  bool get canCompleteTrip => isInProgress;

  String get title => (customerName != null && customerName!.trim().isNotEmpty)
      ? customerName!.trim()
      : (orderCode ?? 'Order #$id');

  String get routeSummary {
    final from = pickupLocation?.trim() ?? '';
    final to = dropoffLocation?.trim() ?? '';
    if (from.isEmpty && to.isEmpty) return '';
    if (from.isEmpty) return to;
    if (to.isEmpty) return from;
    return '$from → $to';
  }

  /// Alias used by list cards.
  String get routeLabel => routeSummary;

  String get whenLabel {
    final d = hireDate ?? '';
    final t = hireTime ?? '';
    if (d.isEmpty && t.isEmpty) return '—';
    if (t.isEmpty) return d;
    if (d.isEmpty) return t;
    return '$d · $t';
  }

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    final coaster = json['coaster'];
    Map<String, dynamic>? coasterMap;
    if (coaster is Map) coasterMap = Map<String, dynamic>.from(coaster);

    final customer = json['customer'];
    Map<String, dynamic>? customerMap;
    if (customer is Map) customerMap = Map<String, dynamic>.from(customer);
    final seats = json['passenger_seats'];

    return OrderModel(
      id: _asInt(json['id']),
      orderCode: json['order_code']?.toString(),
      coasterId: _asIntOrNull(json['coaster_id'] ?? coasterMap?['id']),
      customerName:
          json['customer_name']?.toString() ?? customerMap?['name']?.toString(),
      customerPhone:
          json['customer_phone']?.toString() ??
          customerMap?['phone']?.toString(),
      customerEmail:
          json['customer_email']?.toString() ??
          customerMap?['email']?.toString(),
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
      passengerSeats: seats is List
          ? seats.map((value) => value.toString()).toList(growable: false)
          : const [],
      paymentMethod: json['payment_method']?.toString(),
      orderStatus: json['order_status']?.toString(),
      paymentStatus: json['payment_status']?.toString(),
      hireNextStep: json['hire_next_step']?.toString(),
      coasterName: coasterMap?['name']?.toString(),
      coasterPlate: coasterMap?['plate_number']?.toString(),
    );
  }
}

/// Laravel paginator payload that also behaves as a read-only order list.
///
/// The list interface keeps existing screens compatible while exposing paging
/// metadata to flows that need subsequent pages.
class OrderPage extends ListBase<OrderModel> {
  OrderPage({
    required List<OrderModel> items,
    this.currentPage = 1,
    this.perPage = 15,
    this.total = 0,
    this.lastPage = 1,
    this.from,
    this.to,
    this.nextPageUrl,
    this.previousPageUrl,
  }) : _items = List<OrderModel>.unmodifiable(items);

  final List<OrderModel> _items;
  final int currentPage;
  final int perPage;
  final int total;
  final int lastPage;
  final int? from;
  final int? to;
  final String? nextPageUrl;
  final String? previousPageUrl;

  List<OrderModel> get items => _items;
  bool get hasNextPage => currentPage < lastPage;
  bool get hasPreviousPage => currentPage > 1;

  @override
  int get length => _items.length;

  @override
  set length(int value) => throw UnsupportedError('OrderPage is read-only');

  @override
  OrderModel operator [](int index) => _items[index];

  @override
  void operator []=(int index, OrderModel value) =>
      throw UnsupportedError('OrderPage is read-only');

  factory OrderPage.fromJson(Map<String, dynamic> json) {
    final rawItems = json['data'];
    final items = rawItems is List
        ? rawItems
              .whereType<Map>()
              .map(
                (item) => OrderModel.fromJson(Map<String, dynamic>.from(item)),
              )
              .toList(growable: false)
        : const <OrderModel>[];
    final perPage = _asInt(json['per_page']);
    final total = _asInt(json['total']);

    return OrderPage(
      items: items,
      currentPage: _asInt(json['current_page'], fallback: 1),
      perPage: perPage,
      total: total,
      lastPage: _asInt(
        json['last_page'],
        fallback: perPage > 0 ? (total / perPage).ceil() : 1,
      ),
      from: _asIntOrNull(json['from']),
      to: _asIntOrNull(json['to']),
      nextPageUrl: json['next_page_url']?.toString(),
      previousPageUrl: json['prev_page_url']?.toString(),
    );
  }
}

int _asInt(dynamic value, {int fallback = 0}) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse(value?.toString() ?? '') ?? fallback;
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
