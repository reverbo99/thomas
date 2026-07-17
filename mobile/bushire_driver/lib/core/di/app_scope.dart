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

  /// Sync badge from profile and start location sharing for the assigned coaster.
  Future<void> refreshSessionSideEffects() async {
    var hasCoaster = true;
    try {
      final profile = await authRepository.getProfile();
      setPendingHireCount(profile.pendingHireRequests);
      hasCoaster = profile.coaster != null;
    } catch (_) {
      // Keep last badge value; assume a coaster may be assigned.
    }
    await syncLocationTracking(hasCoaster: hasCoaster);
  }

  /// Continuously share GPS while the driver is signed in with an assigned
  /// coaster (foreground service keeps it alive when backgrounded).
  Future<void> syncLocationTracking({bool hasCoaster = true}) async {
    if (hasCoaster) {
      await locationTracker.start();
    } else {
      locationTracker.stop();
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

  /// Push [page] wrapped in the same [AppScope] as [context].
  ///
  /// Routes pushed under [MaterialApp]'s root navigator are siblings of the
  /// authenticated shell, so they lose [AppScope] unless we re-wrap them.
  static Future<T?> pushScoped<T>(BuildContext context, Widget page) {
    final services = of(context);
    return Navigator.of(context).push<T>(
      MaterialPageRoute(
        builder: (_) => AppScope(services: services, child: page),
      ),
    );
  }

  @override
  bool updateShouldNotify(AppScope oldWidget) => services != oldWidget.services;
}
