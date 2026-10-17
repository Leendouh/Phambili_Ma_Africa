// Auth helpers for the Phambili Ma-Africa website.
// Replaces the PHP session layer: Firebase Auth holds the signed-in
// user in IndexedDB, and the Firestore `users/{uid}` doc holds the
// profile (role, name, phone, address) — same shape as the mobile
// app and admin dashboard.

import {
    onAuthStateChanged,
    signInWithEmailAndPassword,
    createUserWithEmailAndPassword,
    signOut,
    sendPasswordResetEmail,
    sendEmailVerification,
    setPersistence,
    browserLocalPersistence,
    browserSessionPersistence,
    updatePassword,
    EmailAuthProvider,
    reauthenticateWithCredential,
} from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-auth.js';
import {
    doc,
    getDoc,
    setDoc,
} from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-firestore.js';
import { auth, db } from './app.js';

// Where staff/admin users are sent after login (Firebase admin dashboard).
const ADMIN_APP_URL = (window.CONFIG && window.CONFIG.ADMIN_APP_URL) || './admin_dashboard.php';

let currentUser = null;
let currentProfile = null;
let authResolved = false;
const waiters = [];

onAuthStateChanged(auth, async (user) => {
    currentUser = user;
    currentProfile = user ? await fetchProfile(user.uid) : null;
    authResolved = true;
    document.body.classList.remove('pma-auth-loading');
    document.body.classList.add(user ? 'pma-authenticated' : 'pma-anonymous');
    document.dispatchEvent(
        new CustomEvent('pma:auth', { detail: { user, profile: currentProfile } })
    );
    waiters.splice(0).forEach((resolve) => resolve({ user, profile: currentProfile }));
});

async function fetchProfile(uid) {
    try {
        const snap = await getDoc(doc(db, 'users', uid));
        if (snap.exists()) return { uid, ...snap.data() };
    } catch (e) {
        console.error('Failed to load user profile:', e);
    }
    return null;
}

// Resolves once with { user, profile } after the first auth state is known.
export function whenAuthReady() {
    if (authResolved) return Promise.resolve({ user: currentUser, profile: currentProfile });
    return new Promise((resolve) => waiters.push(resolve));
}

// Calls cb({ user, profile }) now and on every auth change.
export function onAuth(cb) {
    document.addEventListener('pma:auth', (e) => cb(e.detail));
    whenAuthReady().then(cb);
}

export function getAuthState() {
    return { user: currentUser, profile: currentProfile };
}

// Redirects to login.php when there is no signed-in user.
// Pass the page to return to after login, e.g. requireAuth('booking.php').
export async function requireAuth(returnTo) {
    const { user, profile } = await whenAuthReady();
    if (!user) {
        const back = returnTo ? `?redirect=${encodeURIComponent(returnTo)}` : '';
        window.location.href = `login.php${back}`;
        return null;
    }
    return { user, profile };
}

// Redirects away if the user IS signed in (used on login.php).
export async function redirectIfSignedIn(defaultTarget) {
    const { user } = await whenAuthReady();
    if (user) {
        const params = new URLSearchParams(window.location.search);
        const target = params.get('redirect')
            || sessionStorage.getItem('pma_redirect_after_login')
            || defaultTarget || 'index.php';
        sessionStorage.removeItem('pma_redirect_after_login');
        window.location.href = target;
        return true;
    }
    return false;
}

export async function pmaSignIn(email, password, remember) {
    // "Remember me" → keep the session across browser restarts;
    // otherwise it is cleared when the tab/browser closes.
    await setPersistence(
        auth,
        remember ? browserLocalPersistence : browserSessionPersistence
    );
    const cred = await signInWithEmailAndPassword(auth, email, password);
    const profile = await fetchProfile(cred.user.uid);
    return { user: cred.user, profile };
}

// Sends the user to the right home page after login based on role.
export function redirectForRole(profile, fallbackTarget) {
    const params = new URLSearchParams(window.location.search);
    const stored = params.get('redirect') || sessionStorage.getItem('pma_redirect_after_login');
    sessionStorage.removeItem('pma_redirect_after_login');

    const role = (profile?.role || 'customer').toLowerCase();
    if (role === 'staff' || role === 'admin' || role === 'main_admin' || role === 'super_admin') {
        window.location.href = ADMIN_APP_URL;
        return;
    }
    window.location.href = stored || fallbackTarget || 'index.php';
}

export async function pmaRegister({ fullName, email, password }) {
    const cred = await createUserWithEmailAndPassword(auth, email, password);

    // Split "Full Name" into the firstName/lastName fields used by
    // the mobile app and admin dashboard user docs.
    const parts = fullName.trim().split(/\s+/);
    const firstName = parts[0] || '';
    const lastName = parts.slice(1).join(' ');

    await setDoc(doc(db, 'users', cred.user.uid), {
        role: 'customer',
        firstName,
        lastName,
        email,
        phone: '',
        address: '',
        dateRegistered: new Date().toISOString(),
    });

    try {
        await sendEmailVerification(cred.user);
    } catch (e) {
        console.warn('Could not send verification email:', e);
    }
    return cred.user;
}

export async function pmaSignOut() {
    await signOut(auth);
    window.location.href = 'index.php';
}

export async function pmaSendPasswordReset(email) {
    await sendPasswordResetEmail(auth, email);
}

// Re-authenticates with the current password, then updates it.
// Mirrors the old PHP "current password required" check.
export async function pmaChangePassword(currentPassword, newPassword) {
    const user = auth.currentUser;
    if (!user || !user.email) throw new Error('Not signed in.');
    const cred = EmailAuthProvider.credential(user.email, currentPassword);
    await reauthenticateWithCredential(user, cred);
    await updatePassword(user, newPassword);
}

// Friendly messages for common Firebase Auth errors.
export function authErrorMessage(error) {
    const code = error && error.code ? error.code : '';
    switch (code) {
        case 'auth/invalid-email':
            return 'Please enter a valid email address.';
        case 'auth/user-not-found':
        case 'auth/wrong-password':
        case 'auth/invalid-credential':
            return 'Incorrect email or password.';
        case 'auth/email-already-in-use':
            return 'An account with this email already exists.';
        case 'auth/weak-password':
            return 'Password is too weak. Use at least 8 characters.';
        case 'auth/too-many-requests':
            return 'Too many attempts. Please wait a moment and try again.';
        case 'auth/requires-recent-login':
            return 'Please log out and back in, then try again.';
        case 'auth/network-request-failed':
            return 'Network error. Please check your connection.';
        default:
            return (error && error.message) || 'Something went wrong. Please try again.';
    }
}
