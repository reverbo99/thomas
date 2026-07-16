import 'coaster_model.dart';
import 'user_model.dart';

/// GET `/profile` payload: user + optional assigned coaster + hire badge count.
class DriverProfile {
  const DriverProfile({
    required this.user,
    this.coaster,
    this.pendingHireRequests = 0,
  });

  final UserModel user;
  final CoasterModel? coaster;
  final int pendingHireRequests;

  bool get hasCoaster => coaster != null;

  factory DriverProfile.fromJson(Map<String, dynamic> json) {
    final userRaw = json['user'];
    if (userRaw is! Map) {
      throw FormatException('Profile response missing user object');
    }

    final coasterRaw = json['coaster'];
    CoasterModel? coaster;
    if (coasterRaw is Map) {
      coaster = CoasterModel.fromJson(Map<String, dynamic>.from(coasterRaw));
    }

    return DriverProfile(
      user: UserModel.fromJson(Map<String, dynamic>.from(userRaw)),
      coaster: coaster,
      pendingHireRequests: _asInt(json['pending_hire_requests']),
    );
  }
}

int _asInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse(value?.toString() ?? '') ?? 0;
}
