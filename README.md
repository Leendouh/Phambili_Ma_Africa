# Phambili Ma Africa - Service Request Management System

A Firebase-based service request management system with mobile apps for customers/employees and a web admin dashboard for staff.

## Tech Stack

- **Database & Auth**: Firebase (Firestore + Authentication)
- **Mobile App**: Expo React Native (TypeScript)
- **Admin Dashboard**: React + Vite (TypeScript)
- **CI/CD**: GitHub Actions

## Project Structure

```
.
├── mobile/              # Expo React Native mobile app
│   ├── src/
│   │   ├── context/     # AuthContext for Firebase auth
│   │   ├── screens/     # Customer and employee screens
│   │   ├── navigation/  # React Navigation setup
│   │   ├── utils/       # Validators and error handlers
│   │   ├── types/       # TypeScript definitions
│   │   └── firebase.ts  # Firebase configuration
│   └── .env.example     # Environment variables template
├── admin/               # React admin dashboard (scaffolded)
│   ├── src/
│   │   └── firebase.ts  # Firebase configuration
│   └── .env.example     # Environment variables template
├── database/            # Firebase setup files
│   ├── firestore.rules  # Security rules
│   ├── seed.js          # Seed data script
│   └── README.md        # Database setup instructions
└── docs/                # Documentation
```

## Firebase Setup (Required First Step)

### 1. Create Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Add project"
3. Name it "phambili-ma-africa"
4. Enable Google Analytics (optional)
5. Create project

### 2. Enable Authentication

1. Go to "Authentication" → "Sign-in method"
2. Enable "Email/Password"
3. **Important**: Turn off "Confirm email" for easier testing

### 3. Create Firestore Database

1. Go to "Firestore Database" → "Create database"
2. Choose location (e.g., `us-central1`)
3. Start in "Test mode"
4. Click "Create"

### 4. Add Security Rules

1. Go to "Firestore Database" → "Rules"
2. Copy the contents of `database/firestore.rules` and paste them
3. Click "Publish"

### 5. Create Admin and Employee Accounts

**Important**: These must be created manually in Firebase Console first:

1. Go to "Authentication" → "Users"
2. Click "Add user" for each account:
   - **Staff (Admin)**: admin@phambili-ma-africa.com
   - **Employees**: john@phambili-ma-africa.com, jane@phambili-ma-africa.com, mike@phambili-ma-africa.com
3. Note the UIDs from the user list

### 6. Add User Documents to Firestore

1. Go to "Firestore Database"
2. Create a collection called `users`
3. Add documents for each user with their UIDs as document IDs:

**Staff Document:**
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

**Employee Documents:**
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

### 7. Add Services

1. Create a collection called `services`
2. Add the 5 services from `database/seed.js`

### 8. Get Firebase Config

1. Go to Project Settings (gear icon)
2. Scroll to "Your apps"
3. Add a Web app
4. Copy the config values

## Mobile App Setup

### 1. Install Dependencies

```bash
cd mobile
npm install
```

### 2. Configure Environment Variables

1. Copy `.env.example` to `.env`
2. Fill in your Firebase config values:
```
EXPO_PUBLIC_FIREBASE_API_KEY=your_api_key
EXPO_PUBLIC_FIREBASE_AUTH_DOMAIN=your_project_id.firebaseapp.com
EXPO_PUBLIC_FIREBASE_PROJECT_ID=your_project_id
EXPO_PUBLIC_FIREBASE_APP_ID=your_app_id
```

### 3. Run the App

```bash
npx expo start
```

- Press `i` to run on iOS simulator
- Press `a` to run on Android emulator
- Scan QR code with Expo Go app on physical device

## Admin Dashboard Setup

### 1. Install Dependencies

```bash
cd admin
npm install
```

### 2. Configure Environment Variables

1. Copy `.env.example` to `.env`
2. Fill in your Firebase config values:
```
VITE_FIREBASE_API_KEY=your_api_key
VITE_FIREBASE_AUTH_DOMAIN=your_project_id.firebaseapp.com
VITE_FIREBASE_PROJECT_ID=your_project_id
VITE_FIREBASE_APP_ID=your_app_id
```

### 3. Run the Admin Dashboard

```bash
npm run dev
```

## Mobile App Features (Completed)

### Customer Features
- ✅ Splash and login, registration with validation
- ✅ Service catalogue with prices
- ✅ New request (address pre-filled from profile, date and time validated)
- ✅ Confirmation screen with reference number
- ✅ My Requests with colour-coded status and Active / History filter, real-time updates
- ✅ Request detail with status timeline
- ✅ Feedback (stars and comment) for Completed requests only, once per request
- ✅ Notifications with unread count (tap to open the request)
- ✅ Profile and logout

### Employee Features
- ✅ View assigned jobs
- ✅ Start jobs (change status to "In Progress")
- ✅ Complete jobs (change status to "Completed")
- ✅ Notifications
- ✅ Profile management

## Admin Dashboard Features

Pages live in `admin/src/pages/`:

- Login with staff role check (non-staff accounts are rejected)
- Dashboard with status counts
- Requests table with Approve / Reject / Assign (blocks double-booking an employee on the same date)
- Employees: availability toggle and schedules
- Services: add, edit, deactivate
- Customers: view and edit
- Feedback and reports with CSV export

## Database Schema

### Collections

| Collection | Description |
|-----------|-------------|
| `users` | All users (customers, employees, staff) with role field |
| `services` | Available services with pricing |
| `requests` | Service requests with status tracking |
| `feedback` | Customer feedback (doc ID = request ID) |
| `notifications` | In-app notifications |

### Request Status Flow

```
Pending → Approved → In Progress → Completed
              ↓
           Rejected
```

## Security

- Passwords hashed by Firebase Auth
- Role-based access control via Firestore security rules
- SQL injection not applicable (NoSQL database)
- Input validation on all forms
- Never commit `service_role` keys to GitHub

## Testing

### Test Accounts

Demo accounts for the `phambili-ma-africa2` Firebase project (change the passwords before any public deployment):

| Role | Email | Password |
|------|-------|----------|
| Customer | customer@test.com | Test123! |
| Employee | employee@test.com | Test123! |
| Staff (admin dashboard) | staff@test.com | Test123! |

New customers can also register through the mobile app. Employee and staff accounts must be created in Firebase Console (Authentication, then a matching `users/<uid>` document with the right `role`), because the security rules only allow self-registration as a customer.

### Automated tests

```bash
cd mobile
npm test          # Jest: validators and error-message mapping
npm run typecheck
npm run lint
```

## Future Enhancements

- SMS and push notifications
- Offline request queuing
- Two-factor authentication for staff (requires paid Firebase plan)
- Real-time websockets (already implemented via Firestore onSnapshot)

## Changes from Task 1

| Area | Task 1 | Current Implementation |
|------|--------|----------------------|
| Database | SQL Server (relational) | Firebase Firestore (NoSQL) |
| API | Custom REST API | Firebase SDK with security rules |
| Staff App | Windows desktop app | React web dashboard |
| Notifications | SMS/push/email | In-app notifications in Firestore |
| SQL Injection | Parameterised queries | Not applicable (NoSQL) |

## CI/CD

`.github/workflows/ci.yml` runs on every push to `main` and every pull request:
- **mobile**: `npm ci`, `npm run typecheck`, `npm run lint`, `npm test`
- **admin**: `npm ci`, `npm run build` (includes `tsc -b`)

## Troubleshooting

### Firebase Free Tier
- Projects pause after ~1 week of inactivity
- Open Firebase dashboard and resume before testing/demos

### Expo Build Limits
- Free tier has build limits and queues
- Use Expo Go for demo, APK is optional

### Email Confirmation
- Turn off "Confirm email" in Auth settings for testing

## License

This project is for educational purposes.
