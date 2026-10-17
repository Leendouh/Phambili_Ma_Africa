// Firebase bootstrap for the Phambili Ma-Africa website.
//
// SETUP: copy the four values below from your Firebase project
// (Firebase Console → Project Settings → Your Apps → Web App).
// They are the same values used in mobile/.env and admin/.env.
// These keys are safe to expose in the browser — access is controlled
// by Firestore security rules, not by keeping the config secret.

import { initializeApp } from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-app.js';
import { getAuth } from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-auth.js';
import { getFirestore } from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-firestore.js';

const firebaseConfig = {
    apiKey: 'PASTE_API_KEY_HERE',
    authDomain: 'PASTE_PROJECT_ID.firebaseapp.com',
    projectId: 'PASTE_PROJECT_ID',
    appId: 'PASTE_APP_ID',
};

// Allow overriding via window.CONFIG.FIREBASE (set in js/config.js) if preferred.
if (window.CONFIG && window.CONFIG.FIREBASE && window.CONFIG.FIREBASE.apiKey) {
    Object.assign(firebaseConfig, window.CONFIG.FIREBASE);
}

const app = initializeApp(firebaseConfig);

export const auth = getAuth(app);
export const db = getFirestore(app);
