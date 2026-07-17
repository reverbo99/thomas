import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import 'api_endpoints.dart';
import 'api_exception.dart';

typedef TokenReader = Future<String?> Function();
typedef UnauthorizedHandler = FutureOr<void> Function();

/// Thin HTTP client for `/api/special-hire/driver`.
///
/// Attaches Bearer token when available; maps Laravel envelopes to
/// [ApiException] (including `success: false` on non-2xx or odd 200s).
class ApiClient {
  ApiClient({
    http.Client? httpClient,
    String? baseUrl,
    this._tokenReader,
    this.onUnauthorized,
  })  : _http = httpClient ?? http.Client(),
        baseUrl = baseUrl ?? ApiConfig.driverBaseUrl;

  final http.Client _http;
  final String baseUrl;
  final TokenReader? _tokenReader;

  /// Invoked on HTTP 401 so the app can clear session / navigate to login.
  UnauthorizedHandler? onUnauthorized;

  Uri _uri(String path, [Map<String, String>? query]) {
    final normalized = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$baseUrl$normalized').replace(queryParameters: query);
  }

  Future<Map<String, String>> _headers({bool auth = true}) async {
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    if (auth && _tokenReader != null) {
      final token = await _tokenReader();
      if (token != null && token.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }
    }
    return headers;
  }

  Future<dynamic> get(
    String path, {
    Map<String, String>? query,
    bool auth = true,
  }) {
    return _send(() async {
      return _http.get(
        _uri(path, query),
        headers: await _headers(auth: auth),
      );
    });
  }

  Future<dynamic> post(
    String path, {
    Map<String, dynamic>? body,
    bool auth = true,
  }) {
    return _send(() async {
      return _http.post(
        _uri(path),
        headers: await _headers(auth: auth),
        body: body == null ? null : jsonEncode(body),
      );
    });
  }

  Future<dynamic> put(
    String path, {
    Map<String, dynamic>? body,
    bool auth = true,
  }) {
    return _send(() async {
      return _http.put(
        _uri(path),
        headers: await _headers(auth: auth),
        body: body == null ? null : jsonEncode(body),
      );
    });
  }

  Future<dynamic> delete(
    String path, {
    Map<String, dynamic>? body,
    bool auth = true,
  }) {
    return _send(() async {
      return _http.delete(
        _uri(path),
        headers: await _headers(auth: auth),
        body: body == null ? null : jsonEncode(body),
      );
    });
  }

  Future<dynamic> _send(Future<http.Response> Function() request) async {
    late http.Response response;
    try {
      response = await request();
    } on TimeoutException {
      throw ApiException(message: 'Request timed out');
    } catch (e) {
      throw ApiException(message: 'Network error: $e');
    }

    return _parseResponse(response);
  }

  Future<dynamic> _parseResponse(http.Response response) async {
    final status = response.statusCode;
    dynamic decoded;
    if (response.body.isNotEmpty) {
      try {
        decoded = jsonDecode(response.body);
      } catch (_) {
        throw ApiException(
          message: 'Invalid server response',
          statusCode: status,
        );
      }
    }

    final map = decoded is Map<String, dynamic> ? decoded : null;
    final success = map?['success'];
    final message = map?['message']?.toString();
    final errors = _parseErrors(map?['errors']);

    if (status == 401) {
      await onUnauthorized?.call();
      throw ApiException(
        message: message ?? 'Unauthorized',
        statusCode: status,
        errors: errors,
      );
    }

    if (status == 403) {
      throw ApiException(
        message: message ?? 'Forbidden',
        statusCode: status,
        errors: errors,
      );
    }

    if (status == 422) {
      throw ApiException(
        message: message ?? 'Validation failed',
        statusCode: status,
        errors: errors,
      );
    }

    final okHttp = status >= 200 && status < 300;
    if (!okHttp || success == false) {
      throw ApiException(
        message: message ?? 'Request failed',
        statusCode: status,
        errors: errors,
      );
    }

    // Prefer `data` when present; otherwise return full map / decoded body.
    if (map != null && map.containsKey('data')) {
      return map['data'];
    }
    return decoded;
  }

  static Map<String, List<String>>? _parseErrors(dynamic raw) {
    if (raw is! Map) return null;
    final out = <String, List<String>>{};
    raw.forEach((key, value) {
      if (value is List) {
        out[key.toString()] =
            value.map((e) => e.toString()).toList(growable: false);
      } else if (value != null) {
        out[key.toString()] = [value.toString()];
      }
    });
    return out.isEmpty ? null : out;
  }

  void close() => _http.close();
}
