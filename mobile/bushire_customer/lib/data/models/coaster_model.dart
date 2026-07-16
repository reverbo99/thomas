class CoasterPricing {
  const CoasterPricing({
    this.id,
    this.coasterId,
    this.basePrice = 0,
    required this.pricePerKm,
    this.minKm = 0,
    this.weekendSurchargePercent = 0,
    this.nightSurchargePercent = 0,
  });

  final int? id;
  final int? coasterId;
  final num basePrice;
  final num pricePerKm;
  final num minKm;
  final num weekendSurchargePercent;
  final num nightSurchargePercent;

  factory CoasterPricing.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return const CoasterPricing(pricePerKm: 0);
    }
    return CoasterPricing(
      id: _asIntOrNull(json['id']),
      coasterId: _asIntOrNull(json['coaster_id']),
      basePrice: _asNum(json['base_price']),
      pricePerKm: _asNum(json['price_per_km']),
      minKm: _asNum(json['min_km']),
      weekendSurchargePercent: _asNum(json['weekend_surcharge_percent']),
      nightSurchargePercent: _asNum(json['night_surcharge_percent']),
    );
  }
}

class CoasterDriver {
  const CoasterDriver({
    this.id,
    required this.name,
    this.phone,
    this.email,
  });

  final int? id;
  final String name;
  final String? phone;
  final String? email;

  factory CoasterDriver.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return const CoasterDriver(name: '');
    }
    return CoasterDriver(
      id: _asIntOrNull(json['id']),
      name: json['name']?.toString() ?? '',
      phone: json['phone']?.toString() ?? json['contact']?.toString(),
      email: json['email']?.toString(),
    );
  }

  bool get hasInfo => name.isNotEmpty || (phone != null && phone!.isNotEmpty);
}

class CoasterModel {
  const CoasterModel({
    required this.id,
    required this.name,
    this.plateNumber,
    this.capacity,
    this.model,
    this.color,
    this.features,
    this.imageUrl,
    this.latitude,
    this.longitude,
    this.isAvailable = false,
    this.availabilityStatus,
    this.status,
    this.pricing,
    this.driver,
  });

  final int id;
  final String name;
  final String? plateNumber;
  final int? capacity;
  final String? model;
  final String? color;
  final String? features;
  final String? imageUrl;
  final double? latitude;
  final double? longitude;
  final bool isAvailable;
  final String? availabilityStatus;
  final String? status;
  final CoasterPricing? pricing;
  final CoasterDriver? driver;

  bool get canBook =>
      isAvailable ||
      (availabilityStatus?.toLowerCase() == 'available');

  factory CoasterModel.fromJson(Map<String, dynamic> json) {
    final pricingRaw = json['pricing'];
    final driverRaw = json['driver'];
    return CoasterModel(
      id: _asInt(json['id']),
      name: json['name']?.toString() ?? 'Coaster',
      plateNumber: json['plate_number']?.toString(),
      capacity: _asIntOrNull(json['capacity']),
      model: json['model']?.toString(),
      color: json['color']?.toString(),
      features: json['features']?.toString(),
      imageUrl: json['image_url']?.toString(),
      latitude: _asDoubleOrNull(json['latitude']),
      longitude: _asDoubleOrNull(json['longitude']),
      isAvailable: json['is_available'] == true ||
          json['is_available'] == 1 ||
          json['is_available']?.toString() == '1',
      availabilityStatus: json['availability_status']?.toString(),
      status: json['status']?.toString(),
      pricing: pricingRaw is Map
          ? CoasterPricing.fromJson(Map<String, dynamic>.from(pricingRaw))
          : null,
      driver: driverRaw is Map
          ? CoasterDriver.fromJson(Map<String, dynamic>.from(driverRaw))
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

num _asNum(dynamic value) {
  if (value is num) return value;
  return num.tryParse(value?.toString() ?? '') ?? 0;
}

double? _asDoubleOrNull(dynamic value) {
  if (value == null) return null;
  if (value is double) return value;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString());
}
