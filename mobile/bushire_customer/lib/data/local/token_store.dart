import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/user_model.dart';

/// Persists Sanctum token + cached user in secure storage.
class TokenStore {
  TokenStore({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage();

  static const _tokenKey = 'bushire_customer_token';
  static const _userKey = 'bushire_customer_user';

  final FlutterSecureStorage _storage;

  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<void> saveUser(UserModel user) =>
      _storage.write(key: _userKey, value: jsonEncode(user.toJson()));

  Future<UserModel?> readUser() async {
    final raw = await _storage.read(key: _userKey);
    if (raw == null || raw.isEmpty) return null;
    try {
      final map = jsonDecode(raw);
      if (map is Map<String, dynamic>) {
        return UserModel.fromJson(map);
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
    await Future.wait([
      _storage.delete(key: _tokenKey),
      _storage.delete(key: _userKey),
    ]);
  }

  Future<bool> hasToken() async {
    final token = await readToken();
    return token != null && token.isNotEmpty;
  }
}
