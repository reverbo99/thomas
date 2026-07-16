import '../api/api_client.dart';
import '../api/api_endpoints.dart';
import '../api/api_exception.dart';
import '../local/token_store.dart';
import '../models/auth_response.dart';
import '../models/user_model.dart';

/// Auth + profile against Special Hire Customer API.
class AuthRepository {
  AuthRepository({
    ApiClient? apiClient,
    TokenStore? tokenStore,
  }) : _tokenStore = tokenStore ?? TokenStore() {
    _api = apiClient ??
        ApiClient(
          tokenReader: _tokenStore.readToken,
          onUnauthorized: clearLocalSession,
        );
    _api.onUnauthorized ??= clearLocalSession;
  }

  final TokenStore _tokenStore;
  late final ApiClient _api;

  UserModel? _cachedUser;
  String? _cachedToken;

  ApiClient get apiClient => _api;
  TokenStore get tokenStore => _tokenStore;

  UserModel? get currentUser => _cachedUser;
  String? get token => _cachedToken;

  Future<bool> isLoggedIn() async {
    final t = _cachedToken ?? await _tokenStore.readToken();
    return t != null && t.isNotEmpty;
  }

  /// Loads token + cached user from secure storage (call on app start).
  Future<UserModel?> restoreSession() async {
    _cachedToken = await _tokenStore.readToken();
    _cachedUser = await _tokenStore.readUser();
    if (_cachedToken == null || _cachedToken!.isEmpty) {
      _cachedUser = null;
      return null;
    }
    return _cachedUser;
  }

  /// POST `/login`
  Future<AuthResponse> login({
    required String email,
    required String password,
  }) async {
    final data = await _api.post(
      ApiEndpoints.login,
      body: {
        'email': email.trim(),
        'password': password,
      },
      auth: false,
    );

    if (data is! Map<String, dynamic>) {
      throw ApiException(message: 'Unexpected login response');
    }

    final auth = AuthResponse.fromJson(data);
    await _persistSession(auth);
    return auth;
  }

  /// POST `/register`
  Future<AuthResponse> register({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    final data = await _api.post(
      ApiEndpoints.register,
      body: {
        'name': name.trim(),
        'email': email.trim(),
        'phone': phone.trim(),
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
      auth: false,
    );

    if (data is! Map<String, dynamic>) {
      throw ApiException(message: 'Unexpected register response');
    }

    final auth = AuthResponse.fromJson(data);
    await _persistSession(auth);
    return auth;
  }

  /// GET `/profile`
  Future<UserModel> getProfile() async {
    final data = await _api.get(ApiEndpoints.profile);
    if (data is! Map<String, dynamic>) {
      throw ApiException(message: 'Unexpected profile response');
    }
    final user = UserModel.fromJson(data);
    _cachedUser = user;
    await _tokenStore.saveUser(user);
    return user;
  }

  /// PUT `/profile` — optional [name], [phone], [password] (+ confirmation).
  Future<UserModel> updateProfile({
    String? name,
    String? phone,
    String? password,
    String? passwordConfirmation,
  }) async {
    final body = <String, dynamic>{};
    if (name != null) body['name'] = name.trim();
    if (phone != null) body['phone'] = phone.trim();
    if (password != null && password.isNotEmpty) {
      body['password'] = password;
      if (passwordConfirmation != null) {
        body['password_confirmation'] = passwordConfirmation;
      }
    }

    final data = await _api.put(ApiEndpoints.profile, body: body);
    if (data is! Map) {
      throw ApiException(message: 'Unexpected profile update response');
    }
    final map = Map<String, dynamic>.from(data);
    // Response omits `role`; keep cached role when present.
    if (_cachedUser?.role != null && map['role'] == null) {
      map['role'] = _cachedUser!.role;
    }
    final user = UserModel.fromJson(map);
    _cachedUser = user;
    await _tokenStore.saveUser(user);
    return user;
  }

  /// POST `/logout` then clear local session.
  Future<void> logout() async {
    try {
      await _api.post(ApiEndpoints.logout);
    } on ApiException {
      // Still clear local credentials if the network/API call fails.
    } finally {
      await clearLocalSession();
    }
  }

  Future<void> clearLocalSession() async {
    _cachedToken = null;
    _cachedUser = null;
    await _tokenStore.clear();
  }

  Future<void> _persistSession(AuthResponse auth) async {
    _cachedToken = auth.token;
    _cachedUser = auth.user;
    await _tokenStore.saveSession(token: auth.token, user: auth.user);
  }
}
