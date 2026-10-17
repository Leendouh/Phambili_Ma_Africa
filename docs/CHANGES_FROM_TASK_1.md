# Changes from Task 1 to Firebase Implementation

## Overview

This document explains the architectural changes made when migrating from the original Task 1 design (SQL Server + ASP.NET API) to the Firebase implementation.

## Technology Stack Changes

### Database
- **Task 1**: SQL Server (relational database)
- **Current**: Firebase Firestore (cloud NoSQL database)
- **Reasoning**: Firebase provides a free tier with built-in auth and automatic API generation, reducing infrastructure complexity

### Backend API
- **Task 1**: Custom ASP.NET REST API
- **Current**: Firebase SDK + Firestore security rules
- **Reasoning**: Firebase generates a secure API automatically from the database, with client SDKs handling all communication

### Staff Desktop Application
- **Task 1**: Native Windows desktop application
- **Current**: React web dashboard (runs in browser)
- **Reasoning**: Web dashboard works on any Windows PC, can be hosted free on Vercel/Netlify, and shares code with the mobile app (React)
- **Note**: If a true desktop app is required, the same React project can be wrapped in Electron with minimal additional work

### Notifications
- **Task 1**: SMS, push notifications, and email
- **Current**: In-app notifications stored in Firestore
- **Reasoning**: Reduces complexity and cost; in-app notifications provide immediate feedback
- **Future Enhancement**: SMS and push notifications can be added later

### SQL Injection
- **Task 1**: Addressed with parameterised queries
- **Current**: Not applicable (NoSQL database)
- **Mitigation**: Input validation on all forms, Firestore security rules enforce data integrity

## Database Schema Mapping

The ERD from Task 1 is maintained as the logical design, mapped to Firestore collections:

| ERD Table | Firestore Collection | Key Changes |
|-----------|---------------------|-------------|
| Customer | `users` (role='customer') | Merged with Employee/Staff into single collection with role field |
| Employee | `users` (role='employee') | Merged with Customer/Staff into single collection with role field |
| Staff | `users` (role='staff') | Merged with Customer/Employee into single collection with role field |
| Service | `services` | Direct mapping, auto-generated IDs |
| ServiceRequest | `requests` | Reference number generated client-side, employeeName copied for performance |
| Feedback | `feedback` | Document ID = request ID enforces one feedback per request |
| Notification | `notifications` | Direct mapping |

### Key Design Decisions

1. **Single `users` collection**: All user types share one collection with a `role` field. This simplifies queries and authentication flow.

2. **Reference numbers**: Generated client-side as `'PMA-' + Date.now().toString(36).toUpperCase()` to show customers immediately without waiting for database ID.

3. **Denormalised data**: `serviceName` and `employeeName` are copied into requests to avoid extra lookups in the mobile app.

4. **Feedback enforcement**: Using the request ID as the feedback document ID ensures only one feedback per request at the database level.

## Security Changes

### Authentication
- **Task 1**: Custom password hashing in ASP.NET
- **Current**: Firebase Auth handles password hashing, rate limiting, and session management

### Access Control
- **Task 1**: Server-side role checks in API
- **Current**: Firestore security rules enforce role-based access at the database level
- **Benefit**: Security rules hold even if someone bypasses the app

### Two-Factor Authentication
- **Task 1**: Planned feature
<<<<<<< HEAD
- **Current**: Not implemented (requires paid Firebase Identity Platform)
- **Mitigation**: Other security measures in place (rules, rate limiting, strong password requirements, role separation)
- **Future Enhancement**: Can be added with Identity Platform upgrade or simple email verification step
=======
- **Current**: Partial. After the password, staff must open a one-time sign-in link emailed to them (Firebase email-link sign-in, free plan) before any admin page opens. The step is valid for 12 hours per browser and every admin page is behind a staff-only route guard. Tested end to end in a real browser against the Firebase emulators, including expired or reused links and links opened without a session
- **Limitation**: Firebase's built-in multi-factor authentication needs the paid Identity Platform upgrade, so the second step is enforced by the dashboard and not by Firestore. Someone with a staff password who talks to Firestore directly would bypass it
- **Mitigation**: Firestore rules, rate limiting by Firebase Auth, password rules, role separation, and the fact that staff accounts cannot be self-registered
- **Future Enhancement**: Server-enforced MFA with Identity Platform
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548

## Error Handling

### Database-Level Integrity
- **Task 1**: CHECK constraints, UNIQUE constraints, foreign keys
<<<<<<< HEAD
- **Current**: Firestore security rules + app-side validation
- **Limitation**: Firestore has no CHECK constraints or foreign keys
- **Mitigation**: Security rules enforce business logic (status transitions, feedback rules, role changes)
=======
- **Current**: Firestore security rules + app-side validation. The rules are the equivalent of CHECK constraints: they validate field formats (date, time, address length, reference number), rating 1 to 5, comment length, and immutable fields (customer and reference number)
- **Limitation**: Firestore has no foreign keys
- **Mitigation**: Security rules enforce business logic (status transitions, feedback rules, role changes) and are verified by 59 automated emulator tests that run in CI. Deletes are blocked everywhere
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548

### Status Transition Rules
- **Task 1**: Database trigger enforces valid status changes
- **Current**: Firestore security rules allow only valid transitions
- **Rule**: Pending → Approved/Rejected, Approved → In Progress, In Progress → Completed

### Notifications
- **Task 1**: Database trigger creates notifications on status change
- **Current**: App creates notification when updating status
- **Limitation**: If status is updated directly in Firebase Console (bypassing app), no notification is created
<<<<<<< HEAD
- **Mitigation**: Staff should only use the admin dashboard to update requests
=======
- **Mitigation**: Staff should only use the admin dashboard to update requests. The rules also stop employees notifying anyone except the customer of their own job, and stop customers creating notifications
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548

## Connectivity

### API Usage
- **Task 1**: REST API with hard-coded data in some places
- **Current**: Firebase SDK used exclusively throughout both apps
- **Benefit**: No hard-coded data, all data comes from Firebase

### Real-Time Updates
- **Task 1**: Polling or websockets
- **Current**: Firestore `onSnapshot` provides true real-time updates
- **Benefit**: Better than Task 1 implementation, no polling needed

## Free Tier Considerations

### Firebase Free Tier Limitations
1. **Project pausing**: Free projects pause after ~1 week of inactivity
   - **Mitigation**: Open Firebase dashboard before testing, submitting, and demo

2. **Email confirmation limits**: Free tier limits confirmation emails
   - **Mitigation**: Turn off "Confirm email" in Auth settings for testing

3. **Expo build limits**: Free cloud builds have limits and queues
   - **Mitigation**: Use Expo Go for demo, APK is optional

4. **No Cloud Functions**: Requires paid Blaze plan
   - **Impact**: No server-side triggers for notifications (handled in app instead)

5. **No 2FA with TOTP**: Requires Identity Platform upgrade (billing)
   - **Impact**: 2FA not implemented, listed as future enhancement

## Benefits of Firebase Implementation

1. **Single language**: JavaScript/TypeScript throughout, easier for team collaboration
2. **Free hosting**: All components can be hosted free (Firebase, Vercel, Netlify)
3. **Automatic API**: No custom backend to maintain
4. **Built-in auth**: Professional authentication with rate limiting
5. **Real-time**: Better real-time support than polling
6. **Cross-platform**: Mobile app works on iOS and Android

## Trade-offs

1. **Less database-level integrity**: No CHECK constraints or foreign keys in Firestore
   - **Mitigation**: Security rules + app validation

2. **No server-side triggers**: Notifications created by app instead of database
   - **Mitigation**: Staff use admin dashboard only

3. **2FA not included**: Would require paid plan
   - **Mitigation**: Documented as future enhancement, other security measures in place

4. **Web dashboard instead of desktop**: May not meet lecturer's expectation
   - **Mitigation**: Can wrap in Electron if needed

## Conclusion

The Firebase implementation maintains the core functionality and requirements from Task 1 while simplifying the architecture and reducing costs. The main trade-offs are in database-level integrity (moved to security rules) and 2FA (future enhancement). The lecturer should be consulted early to confirm that a web-based admin dashboard is acceptable for the "desktop application" requirement.
