import '../data/api/api_exception.dart';

String formatUiError(Object e) {
  if (e is ApiException) return e.message;
  if (e is Exception) {
    return e.toString().replaceFirst('Exception: ', '');
  }
  return e.toString();
}
