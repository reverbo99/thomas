import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';

import 'app.dart';
import 'core/notifications/push_service.dart';
import 'data/repositories/auth_repository.dart';
import 'firebase_options.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Firebase + push notifications. Failures here must not block app start
  // (e.g. missing Google Play services on some devices/emulators).
  try {
    await Firebase.initializeApp(
      options: DefaultFirebaseOptions.currentPlatform,
    );
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
    await PushService.instance.initialize();
  } catch (e) {
    debugPrint('Firebase init failed: $e');
  }

  runApp(BushireDriverApp(authRepository: AuthRepository()));
}
