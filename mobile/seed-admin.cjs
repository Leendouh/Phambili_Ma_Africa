// One-time setup: creates the admin/staff account in Firebase Auth + Firestore.
// Run from mobile/: node seed-admin.cjs
// Override with env vars: USER_EMAIL / USER_PASSWORD
//
// NOTE: Firestore rules only allow a user to self-create a 'customer' doc,
// so if the users/{uid} write is denied, create it in the Firebase console
// (Firestore → users → Add document → ID = the UID printed below) with:
//   role: "staff", firstName: "Admin", lastName: "User",
//   email: <same email>, phone: "", isAvailable: false,
//   dateRegistered: <today's ISO date>
// The admins/{uid} record is created automatically on first login
// (first staff member bootstraps as main_admin).
const fs = require('fs');
const { initializeApp, deleteApp } = require('firebase/app');
const {
  getAuth,
  createUserWithEmailAndPassword,
  sendPasswordResetEmail,
} = require('firebase/auth');
const { getFirestore, doc, setDoc } = require('firebase/firestore');

const envPath = fs.existsSync('.env') ? '.env' : '../.env';
const env = Object.fromEntries(
  fs
    .readFileSync(envPath, 'utf8')
    .split('\n')
    .filter((l) => l && !l.startsWith('#') && l.includes('='))
    .map((l) => l.split('=', 2))
    .map(([k, v]) => [k.trim(), (v || '').trim()])
);

const app = initializeApp({
  apiKey: env.EXPO_PUBLIC_FIREBASE_API_KEY,
  authDomain: env.EXPO_PUBLIC_FIREBASE_AUTH_DOMAIN,
  projectId: env.EXPO_PUBLIC_FIREBASE_PROJECT_ID,
  appId: env.EXPO_PUBLIC_FIREBASE_APP_ID,
});

const EMAIL = process.env.USER_EMAIL || 'admin@phambilima-africa.co.za';
const PASSWORD =
  process.env.USER_PASSWORD ||
  `Admin${Math.random().toString(36).slice(2, 10)}!${Math.floor(Math.random() * 90 + 10)}`;

const PROFILE = {
  role: 'staff',
  firstName: 'Admin',
  lastName: 'User',
  email: EMAIL,
  phone: '',
  isAvailable: false,
  dateRegistered: new Date().toISOString(),
};

async function run() {
  const auth = getAuth(app);
  const db = getFirestore(app);

  let uid = null;
  try {
    const cred = await createUserWithEmailAndPassword(auth, EMAIL, PASSWORD);
    uid = cred.user.uid;
    console.log(`Auth account created: ${EMAIL}`);
    console.log(`UID: ${uid}`);
    console.log(`Temporary password: ${PASSWORD}`);
  } catch (e) {
    if (e.code === 'auth/email-already-in-use') {
      console.log(`Auth account already exists for ${EMAIL} (check the console for its UID).`);
    } else {
      throw e;
    }
  }

  try {
    await sendPasswordResetEmail(auth, EMAIL);
    console.log('Password-reset email sent — use it to set your own password.');
  } catch (e) {
    console.log('Could not send reset email:', e.code || e.message);
  }

  try {
    await setDoc(doc(db, 'users', uid || 'PASTE_UID_FROM_CONSOLE'), PROFILE);
    console.log('Firestore users doc created with role "staff".');
  } catch (e) {
    console.log('\nFirestore write denied (expected — rules block self-created staff).');
    console.log('Manually create: Firestore → users → Add document');
    console.log(`  Document ID: <the UID above / from Authentication console>`);
    console.log('  Fields:', JSON.stringify(PROFILE, null, 2));
  }

  await deleteApp(app);
  process.exit(0);
}

run().catch((err) => {
  console.error('Failed:', err.code || err.message);
  process.exit(1);
});
