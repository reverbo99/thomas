import '../api/api_client.dart';
import '../api/api_endpoints.dart';
import '../api/api_exception.dart';
import '../models/coaster_model.dart';

/// Assigned coaster for the logged-in driver.
///
/// Prefer sharing [AuthRepository.apiClient] so Bearer auth stays consistent.
/// GET `/coaster` returns 404 when none is assigned — catch [ApiException.isNotFound].
class CoasterRepository {
  CoasterRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  /// GET `/coaster` — assigned coaster, or throws [ApiException] with 404.
  Future<CoasterModel> getCoaster() async {
    final data = await _api.get(ApiEndpoints.coaster);
    if (data is! Map) {
      throw ApiException(message: 'Unexpected coaster response');
    }
    return CoasterModel.fromJson(Map<String, dynamic>.from(data));
  }

  /// Same as [getCoaster], but returns `null` when no coaster is assigned.
  Future<CoasterModel?> getCoasterOrNull() async {
    try {
      return await getCoaster();
    } on ApiException catch (e) {
      if (e.isNotFound) return null;
      rethrow;
    }
  }
}
