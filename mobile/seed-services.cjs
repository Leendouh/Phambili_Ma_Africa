// One-time Firestore seed script: creates the `services` collection.
// Run with: node seed-services.cjs
const fs = require('fs');
const { initializeApp, deleteApp } = require('firebase/app');
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

const db = getFirestore(app);

const services = {
  service1: { name: 'Plumbing Repair', description: 'Fix leaks, unclog drains, and repair pipes', price: 450.0, isActive: true },
  service2: { name: 'Electrical Installation', description: 'Install light fixtures, outlets, and switches', price: 600.0, isActive: true },
  service3: { name: 'Painting Service', description: 'Interior and exterior painting', price: 350.0, isActive: true },
  service4: { name: 'Garden Maintenance', description: 'Lawn mowing, trimming, and general garden care', price: 250.0, isActive: true },
  service5: { name: 'Appliance Repair', description: 'Repair and maintenance of household appliances', price: 400.0, isActive: true },
};

async function seed() {
  for (const [id, data] of Object.entries(services)) {
    await setDoc(doc(db, 'services', id), data);
    console.log(`seeded services/${id} (${data.name})`);
  }
  console.log('Done: 5 services created.');
  await deleteApp(app);
  process.exit(0);
}

seed().catch((err) => {
  console.error('Seed failed:', err.message);
  process.exit(1);
});
