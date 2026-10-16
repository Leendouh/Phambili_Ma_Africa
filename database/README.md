# Database Setup - Firebase Firestore

## Instructions

### 1. Create Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Add project"
3. Name it "phambili-ma-africa" (or your preferred name)
4. Enable Google Analytics (optional, can be disabled)
5. Create project

### 2. Enable Authentication

1. In Firebase Console, go to "Authentication" → "Sign-in method"
2. Enable "Email/Password"
3. Save

### 3. Create Firestore Database

1. Go to "Firestore Database" → "Create database"
2. Choose location (e.g., `us-central1`)
3. Start in "Test mode" (we'll add security rules next)
4. Click "Create"

### 4. Add Security Rules

1. Go to "Firestore Database" → "Rules"
2. Copy the contents of `firestore.rules` and paste them
3. Click "Publish"
4. Test the rules in the Rules Playground to verify they work

### 5. Create Admin and Employee Accounts

**Important:** These must be created manually in Firebase Console first:

1. Go to "Authentication" → "Users"
2. Click "Add user"
3. Create the following accounts:

**Staff (Admin):**
- Email: admin@phambili-ma-africa.com
- Password: (choose a strong password)
- Note the UID from the user list

**Employees:**
- Email: john@phambili-ma-africa.com
- Email: jane@phambili-ma-africa.com
- Email: mike@phambili-ma-africa.com
- Note the UIDs from the user list

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
(Repeat for Jane and Mike with their details)

### 7. Add Services

1. Create a collection called `services`
2. Add the 5 services from `seed.js` with auto-generated IDs

### 8. Add Test Data (Optional)

1. Create a test customer account through the mobile app
2. Note the customer's UID
3. Update `seed.js` with real UIDs
4. Import the collections: `requests`, `feedback`, `notifications`

### 9. Get Firebase Config for Apps

1. Go to Project Settings (gear icon)
2. Scroll to "Your apps"
3. Add a Web app → note the config values
4. These go into:
   - Mobile: `mobile/.env` (with `EXPO_PUBLIC_` prefix)
   - Admin: `admin/.env` (with `VITE_` prefix)

## Environment Variables

**Mobile (.env):**
```
EXPO_PUBLIC_FIREBASE_API_KEY=your_api_key
EXPO_PUBLIC_FIREBASE_AUTH_DOMAIN=your_project_id.firebaseapp.com
EXPO_PUBLIC_FIREBASE_PROJECT_ID=your_project_id
EXPO_PUBLIC_FIREBASE_APP_ID=your_app_id
```

**Admin (.env):**
```
VITE_FIREBASE_API_KEY=your_api_key
VITE_FIREBASE_AUTH_DOMAIN=your_project_id.firebaseapp.com
VITE_FIREBASE_PROJECT_ID=your_project_id
VITE_FIREBASE_APP_ID=your_app_id
```

## ERD to Firestore Mapping

| ERD Table | Firestore Collection | Notes |
|-----------|---------------------|-------|
| Customer | `users` (role='customer') | Doc ID = auth UID |
| Employee | `users` (role='employee') | Doc ID = auth UID |
| Staff | `users` (role='staff') | Doc ID = auth UID |
| Service | `services` | Auto-generated ID |
| ServiceRequest | `requests` | Auto-generated ID |
| Feedback | `feedback` | Doc ID = request ID |
| Notification | `notifications` | Auto-generated ID |

## Important Notes

- Never commit Firebase service role keys to GitHub
- The anon/public keys are safe to commit as RLS protects the data
- Customers register themselves through the mobile app
- Staff and employees must be created manually in Firebase Console first
- Turn off "Confirm email" in Auth settings for easier testing
