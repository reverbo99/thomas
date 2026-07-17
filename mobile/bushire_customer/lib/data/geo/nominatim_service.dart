import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import 'place_result.dart';

/// OpenStreetMap Nominatim geocoding (search + reverse).
///
/// Respects Nominatim usage policy: identifiable User-Agent and light traffic.
class NominatimService {
  NominatimService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  static const _base = 'https://nominatim.openstreetmap.org';
  static const _userAgent = 'BushireCustomer/1.0 (special-hire; contact@bushire.app)';

  /// Forward search. Prefer Tanzania results via [countryCodes].
  Future<List<PlaceResult>> search(
    String query, {
    String countryCodes = 'tz',
    int limit = 6,
  }) async {
    final q = query.trim();
    if (q.length < 3) return const [];

    final uri = Uri.parse('$_base/search').replace(queryParameters: {
      'q': q,
      'format': 'json',
      'addressdetails': '0',
      'limit': '$limit',
      if (countryCodes.isNotEmpty) 'countrycodes': countryCodes,
    });

    final response = await _client.get(
      uri,
      headers: {
        'User-Agent': _userAgent,
        'Accept': 'application/json',
      },
    ).timeout(const Duration(seconds: 12));

    if (response.statusCode != 200) return const [];

    final decoded = jsonDecode(response.body);
    if (decoded is! List) return const [];

    return decoded
        .whereType<Map>()
        .map(_fromJson)
        .whereType<PlaceResult>()
        .toList(growable: false);
  }

  /// Reverse geocode a GPS point into a display name.
  Future<PlaceResult?> reverse(double latitude, double longitude) async {
    final uri = Uri.parse('$_base/reverse').replace(queryParameters: {
      'lat': latitude.toString(),
      'lon': longitude.toString(),
      'format': 'json',
      'zoom': '18',
      'addressdetails': '0',
    });

    final response = await _client.get(
      uri,
      headers: {
        'User-Agent': _userAgent,
        'Accept': 'application/json',
      },
    ).timeout(const Duration(seconds: 12));

    if (response.statusCode != 200) return null;

    final decoded = jsonDecode(response.body);
    if (decoded is! Map) return null;
    return _fromJson(decoded);
  }

  PlaceResult? _fromJson(Map raw) {
    final lat = double.tryParse('${raw['lat']}');
    final lon = double.tryParse('${raw['lon']}');
    final name = (raw['display_name'] ?? '').toString().trim();
    if (lat == null || lon == null || name.isEmpty) return null;
    return PlaceResult(
      displayName: name,
      latitude: lat,
      longitude: lon,
      placeId: raw['place_id']?.toString(),
    );
  }
}

/// Debounced wrapper so typing does not hammer Nominatim.
class DebouncedNominatimSearch {
  DebouncedNominatimSearch({
    NominatimService? service,
    this.delay = const Duration(milliseconds: 450),
  }) : _service = service ?? NominatimService();

  final NominatimService _service;
  final Duration delay;
  Timer? _timer;
  int _generation = 0;

  Future<List<PlaceResult>> search(String query) {
    final completer = Completer<List<PlaceResult>>();
    _timer?.cancel();
    final gen = ++_generation;
    _timer = Timer(delay, () async {
      try {
        final results = await _service.search(query);
        if (gen != _generation) {
          if (!completer.isCompleted) completer.complete(const []);
          return;
        }
        if (!completer.isCompleted) completer.complete(results);
      } catch (_) {
        if (!completer.isCompleted) completer.complete(const []);
      }
    });
    return completer.future;
  }

  void dispose() {
    _timer?.cancel();
  }
}
