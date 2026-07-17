import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';

import '../../data/repositories/location_repository.dart';

/// Continuous GPS tracking for the assigned coaster.
///
/// While the driver is signed in, location is streamed to the backend roughly
/// every [interval]. On Android a **foreground service** (persistent
/// notification) keeps updates flowing when the app is backgrounded — screen
/// off or other apps open.
///
/// Limitation: if the user fully swipes the app away (activity destroyed),
/// Android stops the foreground service too. Surviving a full kill requires a
/// dedicated background-service package; this tracker covers open + backgrounded.
class LocationTracker {
  LocationTracker({
    required LocationRepository locationRepository,
    this.interval = const Duration(seconds: 30),
  }) : _repo = locationRepository;

  final LocationRepository _repo;
  final Duration interval;

  StreamSubscription<Position>? _sub;
  bool _running = false;
  bool _pingInFlight = false;

  /// Last error message from permission or POST (for UI snackbars).
  String? lastError;

  bool get isRunning => _running;

  /// Start streaming location. Idempotent if already running.
  Future<void> start() async {
    if (_running) return;
    final ok = await ensurePermission();
    if (!ok) return;

    _running = true;
    lastError = null;

    // Send an immediate fix so the coaster shows on the map right away.
    await _pingOnce();

    _sub?.cancel();
    _sub = Geolocator.getPositionStream(locationSettings: _settings()).listen(
      _onPosition,
      onError: (Object e) {
        lastError = e.toString();
        debugPrint('LocationTracker stream error: $e');
      },
    );
  }

  /// Stop streaming (logout, dispose).
  void stop() {
    _sub?.cancel();
    _sub = null;
    _running = false;
  }

  void dispose() => stop();

  LocationSettings _settings() {
    if (defaultTargetPlatform == TargetPlatform.android) {
      return AndroidSettings(
        accuracy: LocationAccuracy.high,
        // Report at least every [interval]; distanceFilter 0 keeps sending
        // even while parked so the live map stays fresh.
        intervalDuration: interval,
        distanceFilter: 0,
        foregroundNotificationConfig: const ForegroundNotificationConfig(
          notificationTitle: 'Sharing location',
          notificationText: 'Bushire Driver is sharing your coaster location.',
          notificationChannelName: 'Location sharing',
          enableWakeLock: true,
          setOngoing: true,
        ),
      );
    }
    return LocationSettings(
      accuracy: LocationAccuracy.high,
      distanceFilter: 0,
      timeLimit: interval * 2,
    );
  }

  void _onPosition(Position position) {
    _send(position.latitude, position.longitude);
  }

  Future<void> _pingOnce() async {
    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 15),
        ),
      );
      await _send(position.latitude, position.longitude);
    } catch (e) {
      lastError = e.toString();
      debugPrint('LocationTracker initial ping failed: $e');
    }
  }

  Future<void> _send(double latitude, double longitude) async {
    if (_pingInFlight) return;
    _pingInFlight = true;
    try {
      await _repo.updateLocation(latitude: latitude, longitude: longitude);
      lastError = null;
    } catch (e) {
      lastError = e.toString();
      debugPrint('LocationTracker update failed: $e');
    } finally {
      _pingInFlight = false;
    }
  }

  /// Request location permission, escalating to background ("Allow all the
  /// time") when possible. Returns false only if foreground access is denied
  /// or location services are off — background is best-effort.
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

      // Foreground granted (whileInUse). Try to escalate to background so the
      // foreground service can keep tracking when minimised. On Android 11+
      // the OS may require the user to grant "Allow all the time" in Settings;
      // we don't block on it — whileInUse still works while the app is open.
      if (permission == LocationPermission.whileInUse) {
        try {
          await Geolocator.requestPermission();
        } catch (_) {
          // Ignore — background escalation is best-effort.
        }
      }

      return true;
    } catch (e) {
      lastError = e.toString();
      return false;
    }
  }
}
