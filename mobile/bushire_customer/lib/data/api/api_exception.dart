/// Typed failure from the Special Hire Customer API.
class ApiException implements Exception {
  ApiException({
    required this.message,
    this.statusCode,
    this.errors,
  });

  final String message;
  final int? statusCode;

  /// Field → messages map from Laravel 422 validation responses.
  final Map<String, List<String>>? errors;

  bool get isUnauthorized => statusCode == 401;
  bool get isForbidden => statusCode == 403;
  bool get isValidation => statusCode == 422;
  bool get isNotFound => statusCode == 404;

  /// First validation message for [field], if any.
  String? fieldError(String field) {
    final list = errors?[field];
    if (list == null || list.isEmpty) return null;
    return list.first;
  }

  @override
  String toString() => 'ApiException($statusCode): $message';
}
