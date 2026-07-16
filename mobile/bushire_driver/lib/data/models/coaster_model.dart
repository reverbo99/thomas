class CoasterPricing {
  const CoasterPricing({
    this.id,
    this.coasterId,
    this.basePrice = 0,
    this.pricePerKm = 0,
    this.minKm = 0,
  });

  final int? id;
  final int? coasterId;
  final num basePrice;
  final num pricePerKm;
  final num minKm;

  factory CoasterPricing.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return const CoasterPricing();
    }
    return CoasterPricing(
      id: _asIntOrNull(json['id']),
      coasterId: _asIntOrNull(json['coaster_id']),
      basePrice: _asNum(json['base_price']),
      pricePerKm: _asNum(json['price_per_km']),
      minKm: _asNum(json['min_km']),
    );
  }
}

/// Assigned coaster returned by driver `/profile` and `/coaster`.
class CoasterModel {
  const CoasterModel({
    required this.id,
    required this.name,
    this.userId,
    this.driverUserId,
    this.plateNumber,
    this.capacity,
    this.model,
    this.color,
    this.features,
    this.imageUrl,
    this.status,
    this.latitude,
    this.longitude,
    this.lastLocationUpdate,
    this.pricing,
  });

  final int id;
  final String name;
  final int? userId;
  final int? driverUserId;
  final String? plateNumber;
  final int? capacity;
  final String? model;
  final String? color;
  final String? features;
  final String? imageUrl;
  final String? status;
  final double? latitude;
  final double? longitude;
  final String? lastLocationUpdate;
  final CoasterPricing? pricing;

  bool get isAvailable => status?.toLowerCase() == 'available';
  bool get isOnHire => status?.toLowerCase() == 'on_hire';

  factory CoasterModel.fromJson(Map<String, dynamic> json) {
    final pricingRaw = json['pricing'];
    return CoasterModel(
      id: _asInt(json['id']),
      name: json['name']?.toString() ?? 'Coaster',
      userId: _asIntOrNull(json['user_id']),
      driverUserId: _asIntOrNull(json['driver_user_id']),
      plateNumber: json['plate_number']?.toString(),
      capacity: _asIntOrNull(json['capacity']),
      model: json['model']?.toString(),
      color: json['color']?.toString(),
      features: json['features']?.toString(),
      imageUrl: json['image_url']?.toString(),
      status: json['status']?.toString(),
      latitude: _asDoubleOrNull(json['latitude']),
      longitude: _asDoubleOrNull(json['longitude']),
      lastLocationUpdate: json['last_location_update']?.toString(),
      pricing: pricingRaw is Map
          ? CoasterPricing.fromJson(Map<String, dynamic>.from(pricingRaw))
          : null,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'user_id': userId,
        'driver_user_id': driverUserId,
        'plate_number': plateNumber,
        'capacity': capacity,
        'model': model,
        'color': color,
        'features': features,
        'image_url': imageUrl,
        'status': status,
        'latitude': latitude,
        'longitude': longitude,
        'last_location_update': lastLocationUpdate,
      };
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
