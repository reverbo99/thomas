/// Special Hire Customer API paths (relative to [ApiConfig.customerBaseUrl]).
abstract final class ApiEndpoints {
  static const login = '/login';
  static const register = '/register';
  static const logout = '/logout';
  static const profile = '/profile';

  static const coasters = '/coasters';
  static String coaster(int id) => '/coasters/$id';

  static const calculatePrice = '/calculate-price';

  static const bookings = '/bookings';
  /// Pay-first: ClickPesa without creating hire.
  static const preparePayment = '/bookings/prepare-payment';
  static String syncIntentPayment(int intentId) =>
      '/bookings/payment-intents/$intentId/sync';
  static String booking(int id) => '/bookings/$id';
  static String cancelBooking(int id) => '/bookings/$id/cancel';
  static String trackBooking(int id) => '/bookings/$id/track';
  static String payDeposit(int id) => '/bookings/$id/pay-deposit';
  static String payBalance(int id) => '/bookings/$id/pay-balance';
  static String passengers(int id) => '/bookings/$id/passengers';
  static String syncPayment(int id) => '/bookings/$id/sync-payment';
}

/// Base URL configuration via `--dart-define=API_BASE_URL=...`.
///
/// Defaults to production (`https://ticket.hisgc.net`).
/// Override for local WAMP/emulator if needed:
/// - Android emulator → WAMP: `http://10.0.2.2/thomas/public`
/// - Physical device → LAN IP: `http://192.168.x.x/thomas/public`
/// - iOS simulator / desktop: `http://127.0.0.1/thomas/public`
///
/// Example:
/// `flutter run --dart-define=API_BASE_URL=http://10.0.2.2/thomas/public`
abstract final class ApiConfig {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://ticket.hisgc.net',
  );

  /// Full prefix for customer Special Hire routes.
  static const String customerApiPath = '/api/special-hire/customer';

  static String get customerBaseUrl {
    final base = apiBaseUrl.endsWith('/')
        ? apiBaseUrl.substring(0, apiBaseUrl.length - 1)
        : apiBaseUrl;
    return '$base$customerApiPath';
  }
}
