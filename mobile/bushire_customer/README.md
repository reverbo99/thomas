# Bushire Customer

Flutter app for Special Hire customers. Talks to the Laravel API under
`/api/special-hire/customer`.

## API base URL

Configured in `lib/data/api/api_endpoints.dart` via compile-time define:

| Define | Default |
|--------|---------|
| `API_BASE_URL` | `https://ticket.hisgc.net` |

Full register URL example:

`{API_BASE_URL}/api/special-hire/customer/register`

Production host is **`https://ticket.hisgc.net`** (not `.co.tz`).

### Production (default)

```bash
flutter run
```

Uses `https://ticket.hisgc.net` (HIGHLINK production). No `--dart-define` needed.

### Local WAMP (Android emulator)

Document root is typically `C:\wamp64\www\thomas\public`:

```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2/thomas/public
```

### Local WAMP (physical device on same Wi‑Fi)

Replace with your PC LAN IP:

```bash
flutter run --dart-define=API_BASE_URL=http://192.168.1.10/thomas/public
```

### iOS simulator / desktop

```bash
flutter run --dart-define=API_BASE_URL=http://127.0.0.1/thomas/public
```

`API_BASE_URL` must be a **compile-time** define — hot reload alone will not
change it; do a full restart / rebuild after changing the value.

## Build release APK

From this directory (`mobile/bushire_customer`):

```bash
flutter pub get
flutter build apk --release
```

Optional: pin production host explicitly (same as the default):

```bash
flutter build apk --release --dart-define=API_BASE_URL=https://ticket.hisgc.net
```

Output APK:

`build/app/outputs/flutter-apk/app-release.apk`

Signing: if `android/key.properties` is present (with `storeFile`, `storePassword`,
`keyAlias`, `keyPassword`), the release build uses that keystore. Otherwise it
signs with the debug keystore so the APK still builds for local install/testing.

## Android networking

- `INTERNET` permission: declared in `android/app/src/main` (all build types),
  plus `debug` and `profile` manifests.
- Cleartext HTTP: enabled only on **debug** and **profile** for local WAMP
  overrides. Release builds use HTTPS to `ticket.hisgc.net` and do not allow
  cleartext by default.
- No custom `networkSecurityConfig` is required for the default HTTPS production host.

## Auth endpoints used by Register / Login

| Action | Method + path |
|--------|----------------|
| Register | `POST /api/special-hire/customer/register` |
| Login | `POST /api/special-hire/customer/login` |

Network failures (including DNS / `SocketException`) are mapped to
`ApiException` in `lib/data/api/api_client.dart` and shown on the Register /
Login screens via `formatUiError`.
