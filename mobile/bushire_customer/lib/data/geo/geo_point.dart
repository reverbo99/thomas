/// Simple WGS84 coordinate used by geo helpers (keeps data layer free of
/// flutter_map / latlong2 types).
class GeoPoint {
  const GeoPoint({required this.latitude, required this.longitude});

  final double latitude;
  final double longitude;
}
