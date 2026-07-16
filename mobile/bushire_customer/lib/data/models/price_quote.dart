/// Nested breakdown under [PriceQuote.breakdown].
class PriceBreakdown {
  const PriceBreakdown({
    this.basePrice = 0,
    this.pricePerKm = 0,
    this.kmAmount = 0,
    this.surchargePercent = 0,
    this.surchargeAmount = 0,
    this.surchargeLabels = const [],
  });

  final num basePrice;
  final num pricePerKm;
  final num kmAmount;
  final num surchargePercent;
  final num surchargeAmount;
  final List<String> surchargeLabels;

  factory PriceBreakdown.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const PriceBreakdown();
    final labelsRaw = json['surcharge_labels'];
    final labels = <String>[];
    if (labelsRaw is List) {
      for (final item in labelsRaw) {
        labels.add(item.toString());
      }
    }
    return PriceBreakdown(
      basePrice: _asNum(json['base_price']),
      pricePerKm: _asNum(json['price_per_km']),
      kmAmount: _asNum(json['km_amount']),
      surchargePercent: _asNum(json['surcharge_percent']),
      surchargeAmount: _asNum(json['surcharge_amount']),
      surchargeLabels: labels,
    );
  }
}

/// Result of `POST /calculate-price` (pricing + optional platform commission).
class PriceQuote {
  const PriceQuote({
    required this.distanceKm,
    required this.billableKm,
    required this.totalAmount,
    this.currency = 'TZS',
    this.breakdown = const PriceBreakdown(),
    this.pricePerKm,
    this.kmAmount,
    this.surchargePercent,
    this.surchargeAmount,
    this.surchargeLabels = const [],
    this.coasterName,
    this.coasterId,
    this.coasterCapacity,
    this.platformCommissionPercent,
    this.platformCommissionAmount,
    this.operatorNetAfterPlatform,
  });

  final num distanceKm;
  final num billableKm;
  final num totalAmount;
  final String currency;
  final PriceBreakdown breakdown;

  /// Flat fields (optional overrides / local estimates). Prefer [breakdown] from API.
  final num? pricePerKm;
  final num? kmAmount;
  final num? surchargePercent;
  final num? surchargeAmount;
  final List<String> surchargeLabels;

  final String? coasterName;
  final int? coasterId;
  final int? coasterCapacity;
  final num? platformCommissionPercent;
  final num? platformCommissionAmount;
  final num? operatorNetAfterPlatform;

  /// Effective price/km for UI (flat field or breakdown).
  num get effectivePricePerKm => pricePerKm ?? breakdown.pricePerKm;

  /// Effective km amount for UI.
  num get effectiveKmAmount => kmAmount ?? breakdown.kmAmount;

  /// Effective surcharge labels for UI.
  List<String> get effectiveSurchargeLabels => surchargeLabels.isNotEmpty
      ? surchargeLabels
      : breakdown.surchargeLabels;

  /// Effective surcharge amount for UI.
  num get effectiveSurchargeAmount =>
      surchargeAmount ?? breakdown.surchargeAmount;

  factory PriceQuote.fromJson(Map<String, dynamic> json) {
    final coaster = json['coaster'];
    Map<String, dynamic>? coasterMap;
    if (coaster is Map) {
      coasterMap = Map<String, dynamic>.from(coaster);
    }

    final breakdownRaw = json['breakdown'];
    final breakdown = breakdownRaw is Map
        ? PriceBreakdown.fromJson(Map<String, dynamic>.from(breakdownRaw))
        : PriceBreakdown(
            basePrice: _asNum(json['base_price']),
            pricePerKm: _asNum(json['price_per_km']),
            kmAmount: _asNum(json['km_amount']),
            surchargePercent: _asNum(json['surcharge_percent']),
            surchargeAmount: _asNum(json['surcharge_amount']),
            surchargeLabels: _labels(json['surcharge_labels']),
          );

    final flatLabels = _labels(json['surcharge_labels']);

    return PriceQuote(
      distanceKm: _asNum(json['distance_km']),
      billableKm: _asNum(json['billable_km']),
      totalAmount: _asNum(json['total_amount']),
      currency: json['currency']?.toString() ?? 'TZS',
      breakdown: breakdown,
      pricePerKm: _asNumOrNull(json['price_per_km']) ?? breakdown.pricePerKm,
      kmAmount: _asNumOrNull(json['km_amount']) ?? breakdown.kmAmount,
      surchargePercent:
          _asNumOrNull(json['surcharge_percent']) ?? breakdown.surchargePercent,
      surchargeAmount:
          _asNumOrNull(json['surcharge_amount']) ?? breakdown.surchargeAmount,
      surchargeLabels:
          flatLabels.isNotEmpty ? flatLabels : breakdown.surchargeLabels,
      coasterName: coasterMap?['name']?.toString(),
      coasterId: _asIntOrNull(coasterMap?['id']),
      coasterCapacity: _asIntOrNull(coasterMap?['capacity']),
      platformCommissionPercent:
          _asNumOrNull(json['platform_commission_percent']),
      platformCommissionAmount:
          _asNumOrNull(json['platform_commission_amount']),
      operatorNetAfterPlatform:
          _asNumOrNull(json['operator_net_after_platform']),
    );
  }

  static List<String> _labels(dynamic raw) {
    if (raw is! List) return const [];
    return raw.map((e) => e.toString()).toList(growable: false);
  }
}

num _asNum(dynamic value) {
  if (value is num) return value;
  return num.tryParse(value?.toString() ?? '') ?? 0;
}

num? _asNumOrNull(dynamic value) {
  if (value == null) return null;
  if (value is num) return value;
  return num.tryParse(value.toString());
}

int? _asIntOrNull(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse(value.toString());
}
