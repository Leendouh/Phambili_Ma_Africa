This is an Expo/React Native mobile application. Prioritize mobile-first patterns, performance, and cross-platform compatibility.

## Expo has changed — do not trust your training data

Expo ships breaking changes every SDK release. APIs you remember are likely renamed, moved, or removed. Before writing any code that touches an Expo, EAS, or React Native API:

1. Read the major version of the `expo` package in `package.json`.
2. Fetch the matching versioned docs: `https://docs.expo.dev/versions/v<major>.0.0/`
3. For anything else, fetch https://docs.expo.dev/llms.txt — an index of all Expo docs with corrections to common LLM misconceptions. Follow its links to the specific page you need; never answer from memory.

## Commands

Use `bunx` instead of `npx` if the project uses bun (`bun.lock` present).

```bash
npx expo install <package>  # ALWAYS use instead of npm/yarn/pnpm/bun add — resolves SDK-compatible versions
npx expo start              # start the dev server
npx expo lint               # lint
npx tsc --noEmit            # typecheck
npx expo-doctor             # diagnose dependency and config issues
npx expo install --fix      # fix incompatible package versions
```

Run lint and typecheck before declaring any task done.

## Navigation & Routing

- Use **Expo Router** for all navigation. Routes live in `src/app/` — every file there is a screen, `_layout.tsx` files define navigators. Keep non-route code (components, hooks, utils) outside `src/app/`.
- Import `Link`, `router`, and `useLocalSearchParams` from `expo-router`.
- Docs: https://docs.expo.dev/router/introduction.md

## Building with EAS

Use EAS to build, sign, and submit the app in the cloud (`eas build`, `eas submit`) and to ship over-the-air updates (`eas update`) — no local Xcode or Android Studio required. Run EAS CLI as `bunx eas-cli <command>` in Bun projects, or `npx eas-cli@latest <command>` otherwise; substitute that for bare `eas` in docs examples.
Docs: https://docs.expo.dev/eas/index.md

## Rules

- If `ios/` and `android/` directories do not exist, they are generated (Continuous Native Generation). Never create or edit them by hand — configure native behavior in `app.json` and config plugins.
- Expo Go only includes its bundled native modules. After adding a library with native code, the app needs a development build: `npx expo run:ios|android` locally, or `eas build --profile development`.
- Prefer recommended Expo modules over third-party libraries, and check your available skills before adding dependencies. Docs: https://docs.expo.dev/versions/latest/index.md

## Local development environment (this Mac)

- Use Android Studio's bundled JDK (system Java is JDK 11, too old):
  `export JAVA_HOME="/Applications/Android Studio.app/Contents/jbr/Contents/Home"`
  `export ANDROID_HOME="$HOME/Library/Android/sdk"`
- Emulator AVD: `Medium_Phone_API_36.1` (API 36, arm64). Start with
  `~/Library/Android/sdk/emulator/emulator -avd Medium_Phone_API_36.1`.
- First `npx expo run:android` build took ~50 min (downloads Gradle/NDK/CMake/Maven); later builds are incremental. Day-to-day: keep `npx expo start --dev-client` running — JS changes hot-reload without native rebuilds.
- If the emulator system UI ANRs, it's host CPU saturation — wait it out or cold-boot the AVD.
- Firebase project: `phambili-ma-africa2`. `.env` holds `EXPO_PUBLIC_FIREBASE_*` keys (gitignored via `*.env`).
- Seed data (5 services, demo customer/employee/staff accounts, demo requests in every status) was loaded once and the one-time seed scripts were removed. Demo accounts `customer@`, `employee@`, `staff@test.com` all use `Test123!`. Request refs `PMA-DEMO01` to `PMA-DEMO05` cover every status.
- Queries must not combine `where` with `orderBy` on different fields (needs a composite index); sort client-side instead.
- TypeScript 6 does not auto-load `@types/*`; test globals are declared via `"types": ["jest"]` in `tsconfig.json`.
- Security rules are deployed to `phambili-ma-africa2` (`database/firestore.rules`; redeploy with `firebase deploy --only firestore:rules --project phambili-ma-africa2`). 59 emulator tests live in `database/rules-tests/` and run in CI. New staff/employee accounts must be created in the Firebase Console — the rules block self-registering non-customer roles.
- The emulator reaches a host API at `http://10.0.2.2` (already handled in `src/api/client.ts`).

## Design system

- Colors/typography/spacing/radii/shadows live in `src/theme/` (light palette is the source of truth; deep navy `#0A2540` + gold `#D9A03F`).
- Shared screen primitives live in `src/components/ui.tsx` (`AppHeader`, `AppButton`, `AppInput`, `Card`, `SectionTitle`, `StatusBadge`, `EmptyState`, `InfoRow`, `LoadingScreen`); screens must not hardcode hex colors.
- Fonts: `Poppins_700Bold`/`Poppins_600SemiBold` for headings, `OpenSans_400Regular`/`OpenSans_600SemiBold` for body (see `fontFamily` in `src/theme/typography.ts`).
- ESLint is configured (eslint-config-expo); run `npx expo lint` and `npx tsc --noEmit` before finishing UI work.
