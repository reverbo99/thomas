import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/user_model.dart';

/// Persists Sanctum token + cached user.
///
/// Production uses [FlutterSecureStorage]. Tests can use [TokenStore.memory].
class TokenStore {
  TokenStore({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage(),
        _memory = null;

  /// In-memory store (no platform channels) for widget / unit tests.
  TokenStore.memory()
      : _storage = null,
        _memory = <String, String>{};

  static const _tokenKey = 'bushire_driver_token';
  static const _userKey = 'bushire_driver_user';

  final FlutterSecureStorage? _storage;
  final Map<String, String>? _memory;

  Future<void> saveToken(String token) async {
    final memory = _memory;
    if (memory != null) {
      memory[_tokenKey] = token;
      return;
    }
    await _storage!.write(key: _tokenKey, value: token);
  }

  Future<String?> readToken() async {
    final memory = _memory;
    if (memory != null) return memory[_tokenKey];
    return _storage!.read(key: _tokenKey);
  }

  Future<void> saveUser(UserModel user) async {
    final encoded = jsonEncode(user.toJson());
    final memory = _memory;
    if (memory != null) {
      memory[_userKey] = encoded;
      return;
    }
    await _storage!.write(key: _userKey, value: encoded);
  }

  Future<UserModel?> readUser() async {
    final memory = _memory;
    final raw = memory != null
        ? memory[_userKey]
        : await _storage!.read(key: _userKey);
    if (raw == null || raw.isEmpty) return null;
    try {
      final map = jsonDecode(raw);
      if (map is Map<String, dynamic>) {
        return UserModel.fromJson(map);
      }
      if (map is Map) {
        return UserModel.fromJson(Map<String, dynamic>.from(map));
      }
    } catch (_) {
      // Corrupt cache — ignore.
    }
    return null;
  }

  Future<void> saveSession({
    required String token,
    required UserModel user,
  }) async {
    await Future.wait([
      saveToken(token),
      saveUser(user),
    ]);
  }

  Future<void> clear() async {
    final memory = _memory;
    if (memory != null) {
      memory.remove(_tokenKey);
      memory.remove(_userKey);
      return;
    }
    final storage = _storage!;
    await Future.wait([
      storage.delete(key: _tokenKey),
      storage.delete(key: _userKey),
    ]);
  }

  Future<bool> hasToken() async {
    final token = await readToken();
    return token != null && token.isNotEmpty;
  }
}
