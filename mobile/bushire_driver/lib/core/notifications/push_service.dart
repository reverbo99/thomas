import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../../data/repositories/auth_repository.dart';
import '../../firebase_options.dart';
import '../navigation/main_shell.dart';

/// Background/killed-state message handler.
///
/// Must be a top-level (or static) function annotated with `vm:entry-point`.
/// For messages that carry a `notification` block, Android renders the system
/// tray notification automatically — this handler just ensures Firebase is
/// initialised in the background isolate.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );
}

/// Centralises Firebase Cloud Messaging + local notifications for the driver
/// app: permission, foreground display, token registration, and tap routing.
class PushService {
  PushService._();

  static final PushService instance = PushService._();

  final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _local =
      FlutterLocalNotificationsPlugin();

  static const AndroidNotificationChannel _channel = AndroidNotificationChannel(
    'high_importance_channel',
    'Hire requests & trips',
    description: 'Notifications about new hire requests and trip updates.',
    importance: Importance.high,
  );

  bool _initialised = false;
  String? _token;

  String? get token => _token;

  /// One-time setup — call from `main()` after `Firebase.initializeApp`.
  Future<void> initialize() async {
    if (_initialised) return;
    _initialised = true;

    // Local notifications (used to render messages while in the foreground).
    const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
    await _local.initialize(
      settings: const InitializationSettings(android: androidInit),
      onDidReceiveNotificationResponse: (response) {
        _routeFromData(_decodePayload(response.payload));
      },
    );

    await _local
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(_channel);

    // Ask for permission (Android 13+ shows the system prompt).
    await _messaging.requestPermission();

    // Foreground messages don't show automatically — render them ourselves.
    FirebaseMessaging.onMessage.listen(_showForeground);

    // Tap handling (app in background → foreground).
    FirebaseMessaging.onMessageOpenedApp.listen((m) => _routeFromData(m.data));

    // Cold start via a notification tap.
    final initial = await _messaging.getInitialMessage();
    if (initial != null) {
      _routeFromData(initial.data);
    }

    _token = await _messaging.getToken();
  }

  /// Register the current FCM token with the backend (call after login and on
  /// session restore, once authenticated). Also keeps the backend in sync when
  /// the token rotates.
  Future<void> registerWith(AuthRepository auth) async {
    Future<void> push(String? t) async {
      if (t == null || t.isEmpty) return;
      _token = t;
      try {
        await auth.registerDeviceToken(t);
      } catch (e) {
        debugPrint('Device token registration failed: $e');
      }
    }

    await push(_token ?? await _messaging.getToken());
    _messaging.onTokenRefresh.listen(push);
  }

  /// Remove this device's token from the backend (call on logout).
  Future<void> unregister(AuthRepository auth) async {
    final t = _token;
    if (t == null || t.isEmpty) return;
    try {
      await auth.deleteDeviceToken(t);
    } catch (e) {
      debugPrint('Device token removal failed: $e');
    }
  }

  Future<void> _showForeground(RemoteMessage message) async {
    final notification = message.notification;
    if (notification == null) return;

    await _local.show(
      id: notification.hashCode,
      title: notification.title,
      body: notification.body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          _channel.id,
          _channel.name,
          channelDescription: _channel.description,
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
      ),
      payload: _encodePayload(message.data),
    );
  }

  /// Navigate based on the message's `data.type`.
  void _routeFromData(Map<String, dynamic> data) {
    if (data['type'] == 'hire_request') {
      MainShell.requestTab(MainShell.requestsTab);
    }
  }

  String _encodePayload(Map<String, dynamic> data) {
    return data.entries.map((e) => '${e.key}=${e.value}').join('&');
  }

  Map<String, dynamic> _decodePayload(String? payload) {
    if (payload == null || payload.isEmpty) return {};
    final out = <String, dynamic>{};
    for (final pair in payload.split('&')) {
      final i = pair.indexOf('=');
      if (i > 0) out[pair.substring(0, i)] = pair.substring(i + 1);
    }
    return out;
  }
}
