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
    this.hasLocation,
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
  /// From API `has_location`, or inferred from non-null lat/lng.
  final bool? hasLocation;
  final bool isAvailable;
  final String? availabilityStatus;
  final String? status;
  final CoasterPricing? pricing;
  final CoasterDriver? driver;

  bool get showsOnMap =>
      hasLocation == true || (latitude != null && longitude != null);

  /// List responses include [isAvailable] / [availabilityStatus].
  /// Detail `GET /coasters/{id}` omits those and only sends [status].
  bool get canBook {
    final avail = availabilityStatus?.toLowerCase();
    if (avail == 'available') return true;
    if (avail == 'busy') return false;
    if (isAvailable) return true;
    final s = status?.toLowerCase();
    if (s == 'on_hire' || s == 'maintenance' || s == 'busy') return false;
    if (s == 'available' || s == 'idle' || s == 'active') return true;
    return false;
  }

  /// Prefer detail fields; keep list availability + map coords when detail omits them.
  CoasterModel mergedWithPreview(CoasterModel? preview) {
    if (preview == null) return this;
    return CoasterModel(
      id: id,
      name: name,
      plateNumber: plateNumber ?? preview.plateNumber,
      capacity: capacity ?? preview.capacity,
      model: model ?? preview.model,
      color: color ?? preview.color,
      features: features ?? preview.features,
      imageUrl: imageUrl ?? preview.imageUrl,
      latitude: latitude ?? preview.latitude,
      longitude: longitude ?? preview.longitude,
      hasLocation: hasLocation ?? preview.hasLocation,
      isAvailable: availabilityStatus != null || isAvailable
          ? isAvailable
          : preview.isAvailable,
      availabilityStatus: availabilityStatus ?? preview.availabilityStatus,
      status: status ?? preview.status,
      pricing: pricing ?? preview.pricing,
      driver: (driver != null && driver!.hasInfo) ? driver : preview.driver,
    );
  }

  factory CoasterModel.fromJson(Map<String, dynamic> json) {
    final pricingRaw = json['pricing'];
    final driverRaw = json['driver'];
    final lat = _asDoubleOrNull(json['latitude']);
    final lng = _asDoubleOrNull(json['longitude']);
    final hasLocationRaw = json['has_location'];
    final bool? hasLocation = hasLocationRaw == null
        ? null
        : hasLocationRaw == true ||
            hasLocationRaw == 1 ||
            hasLocationRaw.toString() == '1';
    return CoasterModel(
      id: _asInt(json['id']),
      name: json['name']?.toString() ?? 'Coaster',
      plateNumber: json['plate_number']?.toString(),
      capacity: _asIntOrNull(json['capacity']),
      model: json['model']?.toString(),
      color: json['color']?.toString(),
      features: json['features']?.toString(),
      imageUrl: json['image_url']?.toString(),
      latitude: lat,
      longitude: lng,
      hasLocation: hasLocation ?? (lat != null && lng != null),
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
