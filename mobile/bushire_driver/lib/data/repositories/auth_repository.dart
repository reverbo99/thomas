import '../api/api_client.dart';
import '../api/api_endpoints.dart';
import '../api/api_exception.dart';
import '../local/token_store.dart';
import '../models/auth_response.dart';
import '../models/coaster_model.dart';
import '../models/driver_profile.dart';
import '../models/user_model.dart';

/// Auth + profile against Special Hire Driver API.
///
/// Drivers cannot register — accounts are created by coaster admins.
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
  CoasterModel? _cachedCoaster;
  String? _cachedToken;
  int _pendingHireRequests = 0;

  ApiClient get apiClient => _api;
  TokenStore get tokenStore => _tokenStore;

  UserModel? get currentUser => _cachedUser;
  CoasterModel? get currentCoaster => _cachedCoaster;
  String? get token => _cachedToken;
  int get pendingHireRequests => _pendingHireRequests;

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
      _cachedCoaster = null;
      _pendingHireRequests = 0;
      return null;
    }
    return _cachedUser;
  }

  /// POST `/login` — no register endpoint for drivers.
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

    if (data is! Map) {
      throw ApiException(message: 'Unexpected login response');
    }

    final auth = AuthResponse.fromJson(Map<String, dynamic>.from(data));
    await _persistSession(auth);
    return auth;
  }

  /// GET `/profile` — user + assigned coaster + pending hire count.
  Future<DriverProfile> getProfile() async {
    final data = await _api.get(ApiEndpoints.profile);
    if (data is! Map) {
      throw ApiException(message: 'Unexpected profile response');
    }

    final profile = DriverProfile.fromJson(Map<String, dynamic>.from(data));
    _cachedUser = profile.user;
    _cachedCoaster = profile.coaster;
    _pendingHireRequests = profile.pendingHireRequests;
    await _tokenStore.saveUser(profile.user);
    return profile;
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
    _cachedCoaster = null;
    _pendingHireRequests = 0;
    await _tokenStore.clear();
  }

  Future<void> _persistSession(AuthResponse auth) async {
    _cachedToken = auth.token;
    _cachedUser = auth.user;
    _cachedCoaster = null;
    _pendingHireRequests = 0;
    await _tokenStore.saveSession(token: auth.token, user: auth.user);
  }
}
