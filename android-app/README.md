# 1two1 Android app

A hardened native shell (Capacitor 8) around https://1two1.thesecretbasket.in. The chat itself is the website, so every
website update reaches the app at once; the shell adds protection the browser cannot.

## What the shell does

| Protection | Where |
|---|---|
| No screenshots / screen recording; chat blanked in recent apps | `MainActivity` (`FLAG_SECURE`, release builds only) |
| App lock: fingerprint / face / PIN after 60 s away (native, JavaScript cannot disable it) | `MainActivity` (BiometricPrompt) |
| HTTPS only; only the phone's own CAs trusted (no user-installed certs); key pins with an expiry | `res/xml/network_security_config.xml` |
| No backups to cloud or other phones | manifest + `data_extraction_rules.xml` |
| WebView: no file/content access, no geolocation, safe browsing on, autofill off, debugging off | `MainActivity`, `capacitor.config.json` |
| Navigation limited to our own host; other links open the browser | `capacitor.config.json` (`allowNavigation`) |
| Code shrunk and obfuscated; not debuggable; v2+v3 signatures only | `app/build.gradle` |
| Only permissions: `INTERNET`, `USE_BIOMETRIC` | manifest |

Honest limits: nothing stops someone with a rooted phone from reading their own device, or filming the screen with
another camera. Certificate pins expire on **2027-05-01** (see the XML comment): ship a new version with refreshed pins
before then. Past that date the pins are ignored, not enforced, so users are never locked out.

## Versions

`version.json` is the single source (`versionName` + integer `versionCode`). `scripts/release.mjs` bumps it, writes the
`User-Agent` suffix (`1two1-android/1.0.1 (2)`) the website uses to hide the download button and show "Update available",
and appends to `CHANGELOG.md`. Never reuse or lower a `versionCode`.

## Release

```bash
cd android-app
node scripts/release.mjs patch --notes="What changed"   # or minor / major / exact x.y.z ; add --aab for the Play bundle
```

It syncs, builds a **signed** release, verifies the signature with `apksigner`, and writes to `../public/downloads/`:
`1two1-<version>.apk`, `1two1.apk` (newest) and `android.json` (version, size, SHA-256, signing-cert fingerprint).
Then commit `android.json`, `CHANGELOG.md` and `version.json`, push, and copy the APKs to the server (they are not in git):

```bash
scp public/downloads/1two1*.apk webzynix:apps/1two1/public/downloads/
ssh webzynix 'cd ~/apps/1two1 && git pull --ff-only'
```

## Signing key (read this)

The key lives **outside** the repo at `C:\Users\Admin\.1two1-signing\1two1-release.jks`; `android/keystore.properties`
(git-ignored) points at it. **Back both up somewhere safe.** If the key is lost, installed copies can never be updated
(Android refuses an update signed by a different key). When publishing on Google Play, enrol in *Play App Signing* so
Google holds the app-signing key and this one becomes only the upload key.

## Toolchain

JDK 21 (Android Studio's bundled JBR), Android SDK 36 / build-tools, Gradle 8.14 via the wrapper, Node 24.
`npm install` once, then `node scripts/release.mjs`.

## iPhone later

The same web app can be wrapped with Capacitor iOS (needs a Mac or a cloud build service, and an Apple developer
account). Apple reviews thin web wrappers and random-chat apps strictly, so plan native features (app lock, privacy
screen) and keep the in-app report / end-chat controls.
