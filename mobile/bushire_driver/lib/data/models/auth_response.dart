import 'user_model.dart';

class AuthResponse {
  const AuthResponse({
    required this.user,
    required this.token,
  });

  final UserModel user;
  final String token;

  factory AuthResponse.fromJson(Map<String, dynamic> json) {
    final userJson = json['user'];
    if (userJson is! Map) {
      throw FormatException('Auth response missing user object');
    }
    final token = json['token']?.toString();
    if (token == null || token.isEmpty) {
      throw FormatException('Auth response missing token');
    }
    return AuthResponse(
      user: UserModel.fromJson(Map<String, dynamic>.from(userJson)),
      token: token,
    );
  }
}
