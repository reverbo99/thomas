/// A geocoded place from Nominatim (OpenStreetMap).
class PlaceResult {
  const PlaceResult({
    required this.displayName,
    required this.latitude,
    required this.longitude,
    this.placeId,
  });

  final String displayName;
  final double latitude;
  final double longitude;
  final String? placeId;

  PlaceResult copyWith({
    String? displayName,
    double? latitude,
    double? longitude,
    String? placeId,
  }) {
    return PlaceResult(
      displayName: displayName ?? this.displayName,
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
      placeId: placeId ?? this.placeId,
    );
  }
}
