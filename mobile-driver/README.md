# Colombo Cargo Connect — Driver App (Flutter)

Field app used by lorry drivers and delivery riders. Login → today's trip → scan parcels through the 10-stage lifecycle → capture delivery proof (NIC + signature + photo).

> **Status:** Folder reserved during Phase 0. The Flutter project is scaffolded in **Phase 3** of `../ROADMAP.md`.

## Stack
- Flutter 3.x, Dart 3
- State: Provider
- HTTP: dio
- Camera/QR: mobile_scanner
- Storage: flutter_secure_storage (auth tokens), sqflite (offline scan buffer)
- Signature: signature_pad

## Local development (once Phase 3 begins)

```powershell
flutter pub get
copy config.example.dart lib\config.dart
flutter run
```

## See also
- `../ROADMAP.md` — phased plan
- `../backend/` — API the app talks to
