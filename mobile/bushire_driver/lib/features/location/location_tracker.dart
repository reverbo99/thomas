import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';

import '../../data/repositories/location_repository.dart';

/// Foreground GPS pings every ~30s while a trip is in progress.
///
/// Does **not** continue when the app process is killed (background GPS stubbed).
class LocationTracker {
  LocationTracker({
    required LocationRepository locationRepository,
    this.interval = const Duration(seconds: 30),
  }) : _repo = locationRepository;

  final LocationRepository _repo;
  final Duration interval;

  Timer? _timer;
  bool _running = false;
  bool _pingInFlight = false;

  /// Last error message from permission or POST (for UI snackbars).
  String? lastError;

  bool get isRunning => _running;

  /// Start periodic pings. Idempotent if already running.
  Future<void> start() async {
    if (_running) return;
    final ok = await ensurePermission();
    if (!ok) return;

    _running = true;
    lastError = null;
    await _pingOnce();
    _timer?.cancel();
    _timer = Timer.periodic(interval, (_) => _pingOnce());
  }

  /// Stop pings (complete trip, logout, dispose).
  void stop() {
    _timer?.cancel();
    _timer = null;
    _running = false;
  }

  void dispose() => stop();

  /// Request location permission; returns false if denied / service off.
  Future<bool> ensurePermission() async {
    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        lastError = 'Location services are disabled';
        return false;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied) {
        lastError = 'Location permission denied';
        return false;
      }
      if (permission == LocationPermission.deniedForever) {
        lastError = 'Location permission permanently denied';
        return false;
      }
      return true;
    } catch (e) {
      lastError = e.toString();
      return false;
    }
  }

  Future<void> _pingOnce() async {
    if (!_running || _pingInFlight) return;
    _pingInFlight = true;
    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 15),
        ),
      );
      await _repo.updateLocation(
        latitude: position.latitude,
        longitude: position.longitude,
      );
      lastError = null;
    } catch (e) {
      lastError = e.toString();
      debugPrint('LocationTracker ping failed: $e');
    } finally {
      _pingInFlight = false;
    }
  }
}
