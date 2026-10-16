// One-time setup: creates a test customer account in Firebase Auth + Firestore.
// Run with: node seed-user.cjs
const fs = require('fs');
const { initializeApp, deleteApp } = require('firebase/app');
const { getAuth, createUserWithEmailAndPassword } = require('firebase/auth');
const { getFirestore, doc, setDoc } = require('firebase/firestore');

const env = Object.fromEntries(
  fs.readFileSync('.env', 'utf8')
    .split('\n')
    .filter((l) => l && !l.startsWith('#') && l.includes('='))
    .map((l) => l.split('=', 2))
);

const app = initializeApp({
  apiKey: env.EXPO_PUBLIC_FIREBASE_API_KEY,
  authDomain: env.EXPO_PUBLIC_FIREBASE_AUTH_DOMAIN,
  projectId: env.EXPO_PUBLIC_FIREBASE_PROJECT_ID,
  appId: env.EXPO_PUBLIC_FIREBASE_APP_ID,
});

const EMAIL = process.env.USER_EMAIL || 'employee@test.com';
const PASSWORD = process.env.USER_PASSWORD || 'Test123!';
const PROFILE = {
  role: 'employee',
  firstName: 'Thabo',
  lastName: 'Mokoena',
  email: EMAIL,
  phone: '+27000000001',
  isAvailable: true,
  dateRegistered: new Date().toISOString(),
};

async function run() {
  const auth = getAuth(app);
  const db = getFirestore(app);
  const cred = await createUserWithEmailAndPassword(auth, EMAIL, PASSWORD);
  await setDoc(doc(db, 'users', cred.user.uid), PROFILE);
  console.log(`Created customer ${EMAIL} / ${PASSWORD} (uid ${cred.user.uid})`);
  await deleteApp(app);
  process.exit(0);
}

run().catch((err) => {
  console.error('Failed:', err.code || err.message);
  process.exit(1);
});
