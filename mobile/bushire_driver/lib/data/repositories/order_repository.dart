import '../api/api_client.dart';
import '../api/api_endpoints.dart';
import '../api/api_exception.dart';
import '../models/order_model.dart';

/// Orders, hire-requests, schedule, and history for the driver API.
///
/// Inject the same [ApiClient] as [AuthRepository.apiClient].
class OrderRepository {
  OrderRepository({required ApiClient apiClient}) : _api = apiClient;

  static const allowedDriverStatuses = {'in_progress', 'completed'};

  final ApiClient _api;

  /// GET `/orders` — optional [status], [date] (YYYY-MM-DD), and pagination.
  Future<OrderPage> getOrders({
    String? status,
    String? date,
    int? perPage,
    int? page,
  }) async {
    final query = <String, String>{};
    if (status != null && status.isNotEmpty) query['status'] = status;
    if (date != null && date.isNotEmpty) query['date'] = date;
    if (perPage != null) query['per_page'] = '$perPage';
    if (page != null) query['page'] = '$page';

    final data = await _api.get(
      ApiEndpoints.orders,
      query: query.isEmpty ? null : query,
    );
    return _mapPage(data, 'orders');
  }

  /// GET `/orders/{id}`
  Future<OrderModel> getOrder(int id) async {
    final data = await _api.get(ApiEndpoints.order(id));
    if (data is! Map) {
      throw ApiException(message: 'Unexpected order response');
    }
    return OrderModel.fromJson(Map<String, dynamic>.from(data));
  }

  /// PUT `/orders/{id}/status` — only `in_progress` or `completed`.
  Future<OrderModel> updateOrderStatus(int id, String orderStatus) async {
    if (!allowedDriverStatuses.contains(orderStatus)) {
      throw ApiException(
        message: 'Driver order status must be in_progress or completed',
      );
    }
    final data = await _api.put(
      ApiEndpoints.orderStatus(id),
      body: {'order_status': orderStatus},
    );
    if (data is! Map) {
      throw ApiException(message: 'Unexpected status update response');
    }
    return OrderModel.fromJson(Map<String, dynamic>.from(data));
  }

  Future<OrderModel> startTrip(int id) => updateOrderStatus(id, 'in_progress');

  Future<OrderModel> completeTrip(int id) => updateOrderStatus(id, 'completed');

  /// GET `/hire-requests` — pending hires awaiting accept/decline.
  ///
  Future<OrderPage> getHireRequests({int? perPage, int? page}) async {
    final query = _paginationQuery(perPage: perPage, page: page);
    final data = await _api.get(
      ApiEndpoints.hireRequests,
      query: query.isEmpty ? null : query,
    );
    return _mapPage(data, 'hire requests');
  }

  /// POST `/hire-requests/{id}/accept`
  Future<OrderModel> acceptHireRequest(int id) async {
    final data = await _api.post(ApiEndpoints.acceptHireRequest(id));
    if (data is! Map) {
      throw ApiException(message: 'Unexpected accept response');
    }
    return OrderModel.fromJson(Map<String, dynamic>.from(data));
  }

  /// POST `/hire-requests/{id}/decline`
  Future<OrderModel> declineHireRequest(int id) async {
    final data = await _api.post(ApiEndpoints.declineHireRequest(id));
    if (data is! Map) {
      throw ApiException(message: 'Unexpected decline response');
    }
    return OrderModel.fromJson(Map<String, dynamic>.from(data));
  }

  /// GET `/schedule` — upcoming confirmed/pending.
  Future<OrderPage> getSchedule({int? perPage, int? page}) async {
    final query = _paginationQuery(perPage: perPage, page: page);
    final data = await _api.get(
      ApiEndpoints.schedule,
      query: query.isEmpty ? null : query,
    );
    return _mapPage(data, 'schedule');
  }

  /// GET `/history` — completed/cancelled.
  Future<OrderPage> getHistory({int? perPage, int? page}) async {
    final query = _paginationQuery(perPage: perPage, page: page);
    final data = await _api.get(
      ApiEndpoints.history,
      query: query.isEmpty ? null : query,
    );
    return _mapPage(data, 'history');
  }

  /// True when any order is currently `in_progress` (for location pings).
  Future<bool> hasActiveTrip() async {
    final active = await getOrders(status: 'in_progress', perPage: 1);
    return active.isNotEmpty;
  }

  OrderPage _mapPage(dynamic data, String responseName) {
    if (data is! Map) {
      throw ApiException(message: 'Unexpected $responseName response');
    }
    return OrderPage.fromJson(Map<String, dynamic>.from(data));
  }

  Map<String, String> _paginationQuery({int? perPage, int? page}) {
    final query = <String, String>{};
    if (perPage != null) query['per_page'] = '$perPage';
    if (page != null) query['page'] = '$page';
    return query;
  }
}
