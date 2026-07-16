import 'package:flutter/material.dart';

import '../../data/repositories/auth_repository.dart';
import '../../data/repositories/booking_repository.dart';
import '../../data/repositories/coaster_repository.dart';

/// Shared repositories for the authenticated shell (one [ApiClient]).
class AppServices {
  AppServices({required this.authRepository})
      : coasterRepository = CoasterRepository(
          apiClient: authRepository.apiClient,
        ),
        bookingRepository = BookingRepository(
          apiClient: authRepository.apiClient,
        );

  final AuthRepository authRepository;
  final CoasterRepository coasterRepository;
  final BookingRepository bookingRepository;
}

/// Inherited lookup for [AppServices].
class AppScope extends InheritedWidget {
  const AppScope({
    super.key,
    required this.services,
    required super.child,
  });

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
  bool updateShouldNotify(AppScope oldWidget) =>
      services != oldWidget.services;
}
