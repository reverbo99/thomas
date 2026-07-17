import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

/// Firebase config for Bushire Driver.
///
/// Values mirror `android/app/google-services.json` (project highlink-b410f).
/// Regenerate with the FlutterFire CLI (`flutterfire configure`) if you add
/// iOS/web or rotate the project.
class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError(
        'Bushire Driver Firebase is configured for Android only.',
      );
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      default:
        throw UnsupportedError(
          'Bushire Driver Firebase is configured for Android only '
          '(platform: $defaultTargetPlatform).',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyA9wvJtVaMDCaH0PgfegoeYeU8X-x4goQI',
    appId: '1:717968965047:android:1efa6d7d57e59fec9c7c32',
    messagingSenderId: '717968965047',
    projectId: 'highlink-b410f',
    storageBucket: 'highlink-b410f.firebasestorage.app',
  );
}
