# Phambili Ma-Africa — Service Management Platform

Phambili Ma-Africa (Trading Enterprise) is a professional cleaning company. This repository contains its complete digital platform: a **customer website**, a **cross-platform mobile app** for customers and employees, and a **staff administration dashboard** — all backed by a single shared **Firebase** project (`phambili-ma-africa2`).

| Component | Folder | Technology | Runs on |
|-----------|--------|------------|---------|
| Customer website | `website/` | PHP + vanilla JS + Firebase JS SDK | InfinityFree (https://phambilimaafrica.infinityfreeapp.com/index.php) |
| Mobile app | `mobile/` | Expo React Native (TypeScript) | Android / iOS (Theres a folder for APK can view and download to your phone|
| Staff dashboard | `admin/` | React + Vite + Tailwind (TypeScript) | Web (desktop) |
| Backend | `database/` | Firebase Firestore + Auth + security rules | Google Cloud |

## Table of Contents

- [Project Structure](#project-structure)
- [How the Platform Fits Together](#how-the-platform-fits-together)
- [Firebase Setup](#firebase-setup-required-first-step)
- [Website Setup (InfinityFree)](#website-setup-infinityfree)
- [Mobile App Setup](#mobile-app-setup)
- [Staff Dashboard Setup](#staff-dashboard-setup)
- [Features](#features)
- [Database Schema](#database-schema)
- [Security](#security)
- [Testing](#testing)
- [CI/CD](#cicd)
- [Troubleshooting](#troubleshooting)
- [Changes from Task 1](#changes-from-task-1)
- [Future Enhancements](#future-enhancements)

## Project Structure

```
.
├── website/                # PHP customer website (deploy to InfinityFree)
│   ├── index.php           # Landing page
│   ├── about.php           # About the company
│   ├── services.php        # Service catalogue + Request Quotation entry point
│   ├── booking.php         # Quotation form (calendar, property type, frequency)
│   ├── bookings.php        # Customer's bookings/quotations list
│   ├── products.php        # Products catalogue with cart
│   ├── contact.php         # Contact form (EmailJS)
│   ├── login.php           # Login / registration (Firebase Auth)
│   ├── password_reset.php  # OTP password reset (EmailJS)
│   ├── profile.php         # Customer profile + order history
│   ├── admin_dashboard.php # Staff dashboard (requests, quotes, employees, reports)
│   ├── admin_bookings.php  # Staff bookings view
│   ├── admin_products.php  # Staff product management
│   ├── admin_services.php  # Staff service management
│   ├── css/                # Stylesheets
│   ├── js/                 # Site scripts
│   │   ├── config.js       # Firebase keys, EmailJS keys, admin URL
│   │   └── firebase/       # Shared Firebase layer (app, auth, data,
│   │                       #   notifications, quotePdf, ui)
│   ├── images/             # Logos and site imagery
│   └── db.sql              # Legacy MySQL schema (reference only — migrated to Firestore)
│
├── mobile/                 # Expo React Native app
│   ├── src/
│   │   ├── context/        # AuthContext — Firebase auth + user profile
│   │   ├── screens/
│   │   │   ├── auth/       # Splash, Login, Register, ForcePassword
│   │   │   ├── customer/   # Services, NewRequest, Quotation, Requests,
│   │   │                   #   RequestDetail, Confirmation, Feedback,
│   │   │                   #   Notifications, Profile
│   │   │   └── employee/   # Jobs, JobDetail, availability
│   │   ├── navigation/     # React Navigation (RootNavigator, AuthNavigator)
│   │   ├── components/     # Shared UI primitives (ui.tsx)
│   │   ├── theme/          # Navy + gold design system
│   │   ├── utils/          # Validators, error handlers
│   │   ├── types/          # TypeScript definitions
│   │   └── firebase.ts     # Firebase configuration
│   ├── assets/             # App icon, splash, adaptive icons
│   ├── app.json            # Expo config (package com.phambili.maafrica)
│   └── .env.example        # Environment variables template
│
├── admin/                  # React staff dashboard (Vite + Tailwind)
│   ├── src/
│   │   ├── pages/          # Login, Verify, Dashboard, Requests, Employees,
│   │   │                   #   Services, Customers, Reports
│   │   ├── components/     # RequireStaff route guard
│   │   ├── context/        # AuthContext with email-link second step
│   │   └── firebase.ts     # Firebase configuration
│   └── .env.example        # Environment variables template
│
├── database/               # Shared backend configuration
│   ├── firestore.rules     # Security rules (deployed to the live project)
│   ├── rules-tests/        # 58 emulator tests covering every role
│   ├── seed.js             # Seed data script (services + demo accounts)
│   └── README.md           # Database setup notes
│
├── docs/                   # Additional documentation
├── firebase.json           # Firestore rules path + emulator ports
├── .firebaserc             # Default project: phambili-ma-africa2
└── .github/workflows/      # CI (mobile, admin, rules jobs)
```

## How the Platform Fits Together

```
                 ┌──────────────────────────────┐
                 │   Firebase  (phambili-ma-    │
                 │        africa2)              │
                 │  Auth ─ Firestore ─ Rules    │
                 └────────┬──────────┬──────────┘
                          │          │
        ┌─────────────────┼──────────┼──────────────────┐
        │                 │          │                  │
  ┌─────▼─────┐    ┌──────▼─────┐    │           ┌──────▼──────┐
  │  Website  │    │ Mobile app │    │           │   Staff     │
  │ (PHP site │    │ customers +│    │           │  dashboards │
  │ customers)│    │ employees  │    │           │ admin/ and  │
  └───────────┘    └────────────┘    │           │ admin_*.php │
                                     │           └─────────────┘
                          One shared Firestore database:
                users · services · requests · feedback ·
                notifications · products · contact_messages
```

- **Customers** can book from the website *or* the mobile app — both write to the same `requests` collection, so a request made on the website appears in the app and vice versa.
- **Employees** use the mobile app to see assigned jobs and update their status.
- **Staff** use `website/admin_dashboard.php` (linked from the website after staff login) or the React dashboard in `admin/` to approve requests, assign employees, manage services, and view reports.
- The website's quotation flow uses additional statuses (`Requested → Contacted → Quoted → Confirmed`) that coexist with the app's `Pending → Approved → In Progress → Completed` lifecycle — the Firestore rules allow both.

## Firebase Setup (Required First Step)

### 1. Create Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Add project" → name it (ours is `phambili-ma-africa2`)
3. Google Analytics optional → Create project

### 2. Enable Authentication

1. Go to **Authentication → Sign-in method**
2. Enable **Email/Password**
3. Enable **Email link (passwordless sign-in)** — the staff dashboards use it as a second verification step
4. Turn **off** "Confirm email" for easier testing

### 3. Create Firestore Database

1. Go to **Firestore Database → Create database**
2. Choose a location → start in test mode → Create

### 4. Deploy Security Rules

Either paste `database/firestore.rules` into **Firestore Database → Rules → Publish**, or deploy from the command line:

```bash
firebase deploy --only firestore:rules --project phambili-ma-africa2
```

The rules are covered by 58 emulator tests (see [Testing](#testing)). **Never leave the database in test mode.**

### 5. Create Staff and Employee Accounts

These **must** be created manually — the security rules only allow self-registration as a customer:

1. **Authentication → Users → Add user** for each account
2. Note each user's UID
3. **Firestore → `users` collection** — create a document per user, keyed by UID:

**Staff document:**
```json
{
  "role": "staff",
  "firstName": "Admin",
  "lastName": "User",
  "email": "admin@phambili-ma-africa.com",
  "phone": "+27123456789",
  "dateRegistered": "2026-10-16T20:00:00.000Z"
}
```

**Employee document:**
```json
{
  "role": "employee",
  "firstName": "John",
  "lastName": "Doe",
  "email": "john@phambili-ma-africa.com",
  "phone": "+27123456780",
  "isAvailable": true,
  "dateRegistered": "2026-10-16T20:00:00.000Z"
}
```

New staff and employees sign in with a temporary password and are forced to set a new one on first login (`firstLogin` flag → `ForcePasswordScreen` on mobile, equivalent gate on the web).

### 6. Add Services

Create a `services` collection. `database/seed.js` contains the catalogue entries:

- Standard Cleaning, Deep Cleaning, Window Cleaning, Carpet Cleaning
- Office Cleaning, Gardening Cleaning, Fumigation, Pest Control

### 7. Get Firebase Config

**Project Settings → Your apps → Web app** → copy `apiKey`, `authDomain`, `projectId`, `appId`. The same four values feed the website, mobile app and admin dashboard. (Web API keys are public by design — the rules, not the key, protect the data.)

## Website Setup (InfinityFree)

The website is plain PHP + JavaScript — no build step and no server-side framework.

### 1. Configure

Edit `website/js/config.js`:

- `FIREBASE` — paste the four Firebase config values
- `EMAILJS` — public key, service IDs and template IDs (used for OTP password-reset emails and the contact form)
- `ADMIN_APP_URL` — where staff land after login (`./admin_dashboard.php`)

### 2. Deploy to InfinityFree

1. Create/restore your InfinityFree account and domain
2. Open **File Manager** → `htdocs/`
3. Upload the **contents** of `website/` (i.e. `index.php`, `services.php`, `css/`, `js/`, `images/`… directly inside `htdocs`, not inside a `website/` subfolder — otherwise the site will live at `yourdomain/website/`)
4. Visit the domain — the site talks to Firebase directly from the browser, so no PHP extensions or MySQL are needed

> The InfinityFree server only serves the static PHP pages; all data operations go browser → Firebase.

### 3. Optional: Firebase Hosting

The static parts can also be deployed to Firebase Hosting if preferred, but the PHP pages (sessions, `admin_dashboard.php`) require a PHP host such as InfinityFree.

## Mobile App Setup

### 1. Install Dependencies

```bash
cd mobile
npm install
```

Requires Node 20+ and the Android SDK (for emulator builds). On this Mac use Android Studio's bundled JDK:

```bash
export JAVA_HOME="/Applications/Android Studio.app/Contents/jbr/Contents/Home"
export ANDROID_HOME="$HOME/Library/Android/sdk"
```

### 2. Configure Environment Variables

```bash
cp .env.example .env
```

Fill in `.env`:

```
EXPO_PUBLIC_FIREBASE_API_KEY=your_api_key
EXPO_PUBLIC_FIREBASE_AUTH_DOMAIN=your_project_id.firebaseapp.com
EXPO_PUBLIC_FIREBASE_PROJECT_ID=your_project_id
EXPO_PUBLIC_FIREBASE_APP_ID=your_app_id
```

`.env` is git-ignored — never commit it.

### 3. Run in Development

```bash
npx expo start --dev-client
```

- **Emulator**: `~/Library/Android/sdk/emulator/emulator -avd Medium_Phone_API_36.1`, then run `adb reverse tcp:8081 tcp:8081` and launch the `com.phambili.maafrica` dev client. If the dev client was last pointed at a LAN URL, cold-start it with:

  ```bash
  adb shell am force-stop com.phambili.maafrica
  adb shell am start -a android.intent.action.VIEW \
    -d "exp+phambili-ma-africa://expo-development-client/?url=http%3A%2F%2Flocalhost%3A8081" \
    com.phambili.maafrica
  ```

- **Physical phone**: install **Expo Go**, connect to the same Wi-Fi, and open `exp://<your-LAN-IP>:8081`, or install a built APK (below).

The project uses `expo-dev-client`, so native modules are supported. JS changes hot-reload without rebuilding.

### 4. Build an Installable APK

```bash
cd mobile
npx expo prebuild --platform android   # generate android/ if missing
cd android
./gradlew assembleRelease              # ~50 min first time, incremental after
cp app/build/outputs/apk/release/app-release.apk ~/Downloads/PhambiliMaAfrica.apk
```

The release APK is signed with the debug keystore — fine for sideloading onto your own phone; generate a real keystore before publishing anywhere. The launcher icon and splash are baked in at build time (navy + gold PM badge — see `mobile/assets/` and `app.json`).

## Staff Dashboard Setup

Two staff interfaces exist; use either:

**`website/admin_dashboard.php`** — deployed with the website; staff are redirected here after login.

**`admin/`** — standalone React dashboard:

```bash
cd admin
npm install
cp .env.example .env   # fill in VITE_FIREBASE_* values (same Firebase project)
npm run dev            # Vite dev server
npm run build          # production build → admin/dist
```

Extra `.env` option: `VITE_REQUIRE_EMAIL_2FA=false` disables the email second step for local development.

## Features

### Website (customers)

- Service catalogue with the 8 cleaning services, categories, durations and **Custom Quote / Book Now** actions
- **Request Quotation** form: greeting, property type (House / Apartment / Office / Commercial Space), frequency (One-time / Weekly / Bi-weekly / Monthly), availability calendar, preferred time 08:00–17:00, split address, special requirements, contact details — generates a downloadable PDF quote
- Bookings/quotations list and profile management
- Products catalogue with cart
- Contact form and OTP password reset via EmailJS
- Staff side: `admin_dashboard.php` for requests, quotes, employees, services, products and reports

### Mobile app — customers

- Splash screen, registration with validation, login with lockout protection
- Vibrant service catalogue: search, category filters, badges (POPULAR/TRENDING), duration/professional/insured indicators
- **Book Now** — quick request with popup calendar + time-slot pickers (07:00–18:00), address pre-filled from profile
- **Quotation** — the full website-style quotation form inside the app
- Confirmation screen with `PMA-…` reference number
- My Requests — colour-coded statuses, Active/History filter, real-time updates
- Request detail with status timeline; feedback (stars + comment, once per completed request)
- Notifications with unread badge; profile and logout

### Mobile app — employees

- Assigned jobs list, job detail
- Start job (→ In Progress), Complete job (→ Completed)
- Availability toggle, notifications, profile

### Staff dashboards

- Login restricted to `role: staff` + email-link second step
- Dashboard with status counts
- Requests: Approve / Reject / Assign employee (blocks double-booking on the same date)
- Employees: availability and schedules
- Services: add, edit, deactivate (a price set here appears in the app automatically)
- Customers: view and edit
- Feedback and satisfaction reports with CSV export

## Database Schema

| Collection | Description |
|-----------|-------------|
| `users` | All users — `role` is `customer`, `employee` or `staff`; employees carry `isAvailable`/`firstLogin` |
| `services` | Cleaning services: name, description, duration, category, optional price (0/empty = Custom Quote) |
| `requests` | Bookings and quotations — app lifecycle `Pending → Approved → In Progress → Completed / Rejected`, website quote lifecycle `Requested → Contacted → Quoted → Confirmed / Cancelled` |
| `feedback` | One document per request (doc ID = request ID), rating 1–5 + comment |
| `notifications` | In-app notifications for customers, employees and the shared staff feed |
| `products` | Products sold on the website |
| `contact_messages` | Website contact form submissions |
| `admins` | Staff/admin account records for the website |

## Security

- Passwords hashed and rate-limited by Firebase Auth; repeated failed logins trigger a lockout
- Role-based access control and data integrity enforced by Firestore security rules (`database/firestore.rules`), deployed to the live project and covered by 58 emulator tests
- Server-side checks in the rules: ownership of requests, feedback only on Completed requests, one feedback per request, staff-only service/employee management, no self-promotion to staff, no deletions
- Staff two-step verification: after the password, the dashboard emails a one-time sign-in link and blocks every page until it is used (valid 12 hours per browser)
- Honest limit: Firebase's built-in MFA needs a paid upgrade, so the second step is enforced by the dashboards, not by Firestore — real MFA enforcement is under Future Enhancements
- CSV exports escape quotes and neutralise spreadsheet formulas
- POPIA-conscious: customers only ever see their own requests, feedback and notifications
- No secrets in GitHub — `.env` files are git-ignored; Firebase web keys are public by design because the rules protect the data

## Testing

### Test accounts

Demo accounts on `phambili-ma-africa2` (change passwords before any public deployment):

| Role | Email | Password |
|------|-------|----------|
| Customer | customer@test.com | Test123! |
| Employee | employee@test.com | Test123! |
| Staff | staff@test.com | Test123! |

Staff sign-in also needs the email second step (link sent to the staff mailbox). Demo requests `PMA-DEMO01`–`PMA-DEMO05` cover every status. New customers can register in the app or on the website; employees and staff must be created in the Firebase Console.

### Automated tests

```bash
# Mobile: validators and error-message mapping (66 Jest tests)
cd mobile
npm test
npm run typecheck   # tsc --noEmit
npm run lint        # expo lint

# Security rules: 58 emulator tests (needs Java 21 + Firebase CLI)
firebase emulators:exec --only firestore --project demo-phambili \
  "npm test --prefix database/rules-tests"
```

The rules tests act as every role (signed out, customer, employee, staff) and verify e.g. that customers can't approve their own requests, feedback is only allowed on completed jobs, anonymous users can browse services but nothing else, and nobody can delete records.

## CI/CD

`.github/workflows/ci.yml` runs on every push to `main` and every PR:

- **mobile** — `npm ci`, typecheck, lint, Jest
- **admin** — `npm ci`, production build (includes `tsc -b`)
- **rules** — Firestore emulator runs the 58 security-rule tests

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Firebase project paused | Free-tier projects pause after ~1 week idle — open the Firebase console and resume before demos |
| Emulator has no network | Cold-boot the AVD; if it persists update Android Emulator in SDK Manager (the post-reboot breakage was an emulator bug) |
| Dev client shows white screen | It cached a dead Metro URL — cold-start it with the `am start` deep link above |
| `Cannot reach Firestore` on emulator | Check `adb shell ping -c1 8.8.8.8`; the guest needs an IP on `wlan0`/`eth0` |
| Website 404s after upload | You uploaded the `website/` folder itself — move its *contents* into `htdocs/` |
| Staff login loops | Email-link sign-in must be enabled in Firebase Auth, and `ADMIN_APP_URL`/`VITE_*` must point at the right place |

## Changes from Task 1

| Area | Task 1 | Current implementation |
|------|--------|------------------------|
| Database | MySQL on InfinityFree | Firebase Firestore (NoSQL) |
| API | Custom PHP REST API | Firebase SDK + security rules |
| Staff app | PHP admin pages | Firebase-powered `admin_dashboard.php` + React dashboard |
| Notifications | SMS/push/email | In-app Firestore notifications + EmailJS email |
| Auth | PHP sessions | Firebase Authentication (+ email-link second step for staff) |
| SQL injection | Parameterised queries | Not applicable (NoSQL) |

## Future Enhancements

- SMS and push notifications
- Offline request queuing
- Server-enforced MFA for staff (Firebase Identity Platform — paid)
- Real-time websockets (partially here already via Firestore `onSnapshot`)
- Google Maps address lookup on the booking/quotation forms

## License

This project is for educational purposes.
