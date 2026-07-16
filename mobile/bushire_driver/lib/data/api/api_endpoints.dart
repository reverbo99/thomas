/// Special Hire Driver API paths (relative to [ApiConfig.driverBaseUrl]).
abstract final class ApiEndpoints {
  static const login = '/login';
  static const logout = '/logout';
  static const profile = '/profile';
  static const coaster = '/coaster';

  static const orders = '/orders';
  static String order(int id) => '/orders/$id';
  static String orderStatus(int id) => '/orders/$id/status';

  static const hireRequests = '/hire-requests';
  static String acceptHireRequest(int id) => '/hire-requests/$id/accept';
  static String declineHireRequest(int id) => '/hire-requests/$id/decline';

  static const history = '/history';
  static const schedule = '/schedule';
  static const location = '/location';
}

/// Base URL configuration via `--dart-define=API_BASE_URL=...`.
///
/// Defaults to production. Override for local WAMP/emulator if needed:
/// - Android emulator: `http://10.0.2.2/thomas/public`
/// - iOS simulator / desktop: `http://127.0.0.1/thomas/public`
abstract final class ApiConfig {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://ticket.hisgc.net',
  );

  /// Full prefix for driver Special Hire routes.
  static const String driverApiPath = '/api/special-hire/driver';

  static String get driverBaseUrl {
    final base = apiBaseUrl.endsWith('/')
        ? apiBaseUrl.substring(0, apiBaseUrl.length - 1)
        : apiBaseUrl;
    return '$base$driverApiPath';
  }
}
