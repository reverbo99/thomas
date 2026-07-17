import 'package:flutter/foundation.dart';

import '../data/api/api_exception.dart';
import 'strings.dart';

/// Maps exceptions to short, user-facing copy for banners / snackbars.
String formatUiError(Object e) {
  if (e is ApiException) {
    return sanitizeUiError(e.message);
  }
  if (e is Exception) {
    return sanitizeUiError(e.toString().replaceFirst('Exception: ', ''));
  }
  return sanitizeUiError(e.toString());
}

/// Replaces raw ClientException / SocketException / DNS text with a short message.
///
/// Keeps the original string in debug logs when sanitizing.
String sanitizeUiError(String message) {
  final lower = message.toLowerCase();
  final isNetworkDump = lower.contains('failed host lookup') ||
      lower.contains('socketexception') ||
      lower.contains('clientexception') ||
      lower.contains('network is unreachable') ||
      lower.contains('connection refused') ||
      lower.contains('connection reset') ||
      lower.contains('connection closed') ||
      lower.startsWith('network error:');

  if (isNetworkDump) {
    debugPrint('UI network error (sanitized): $message');
    return AppStrings.networkUnavailable;
  }

  if (lower.contains('timed out') || lower.contains('timeout')) {
    if (message != AppStrings.requestTimedOut) {
      debugPrint('UI timeout error (sanitized): $message');
    }
    return AppStrings.requestTimedOut;
  }

  return message;
}
