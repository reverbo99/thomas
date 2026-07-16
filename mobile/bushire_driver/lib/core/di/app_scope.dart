import 'package:flutter/material.dart';

import '../../data/repositories/auth_repository.dart';
import '../../data/repositories/coaster_repository.dart';
import '../../data/repositories/location_repository.dart';
import '../../data/repositories/order_repository.dart';
import '../../features/location/location_tracker.dart';

/// Shared repositories + location tracker for the authenticated shell.
class AppServices {
  AppServices({required this.authRepository})
    : coasterRepository = CoasterRepository(
        apiClient: authRepository.apiClient,
      ),
      orderRepository = OrderRepository(apiClient: authRepository.apiClient),
      locationRepository = LocationRepository(
        apiClient: authRepository.apiClient,
      ) {
    locationTracker = LocationTracker(locationRepository: locationRepository);
  }

  final AuthRepository authRepository;
  final CoasterRepository coasterRepository;
  final OrderRepository orderRepository;
  final LocationRepository locationRepository;
  late final LocationTracker locationTracker;

  /// Badge count for hire-requests tab.
  final ValueNotifier<int> pendingHireCount = ValueNotifier<int>(0);

  void setPendingHireCount(int count) {
    if (pendingHireCount.value != count) {
      pendingHireCount.value = count;
    }
  }

  /// Sync badge from profile and start/stop GPS based on active trips.
  Future<void> refreshSessionSideEffects() async {
    try {
      final profile = await authRepository.getProfile();
      setPendingHireCount(profile.pendingHireRequests);
    } catch (_) {
      // Keep last badge value.
    }
    await syncLocationTracking();
  }

  /// Start ~30s GPS while any order is `in_progress`; stop otherwise.
  Future<void> syncLocationTracking() async {
    try {
      final active = await orderRepository.hasActiveTrip();
      if (active) {
        await locationTracker.start();
      } else {
        locationTracker.stop();
      }
    } catch (_) {
      // Leave tracker state unchanged on transient errors.
    }
  }

  void stopAll() {
    locationTracker.stop();
  }

  void dispose() {
    stopAll();
    pendingHireCount.dispose();
    locationTracker.dispose();
  }
}

/// Inherited lookup for [AppServices].
class AppScope extends InheritedWidget {
  const AppScope({super.key, required this.services, required super.child});

  final AppServices services;

  static AppServices of(BuildContext context) {
    final scope = context.dependOnInheritedWidgetOfExactType<AppScope>();
    assert(scope != null, 'AppScope not found in widget tree');
    return scope!.services;
  }

  static AppServices? maybeOf(BuildContext context) {
    return context.dependOnInheritedWidgetOfExactType<AppScope>()?.services;
  }

  @override
  bool updateShouldNotify(AppScope oldWidget) => services != oldWidget.services;
}
