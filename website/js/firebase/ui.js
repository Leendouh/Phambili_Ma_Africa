// Shared auth-aware UI wiring for website pages.
// Include once per page (before </body>):
//   <script type="module" src="js/firebase/ui.js"></script>
//
// Markup contract — replaces the old `isset($_SESSION['user_id'])` blocks:
//   data-auth="in"        element visible only when signed in
//   data-auth="out"       element visible only when signed out
//   data-auth="role:x,y"  element visible only for those roles
//   data-user-name        filled with the user's display name
//   data-user-email       filled with the user's email
//   data-logout           click signs the user out
//
// Auth-dependent elements are hidden until Firebase resolves the
// auth state, so there is no flash of the wrong content.

import { onAuth, pmaSignOut, getAuthState } from './auth.js';

// Hide auth-dependent elements while Firebase resolves (from IndexedDB, ~instant).
const style = document.createElement('style');
style.textContent = `
    body.pma-auth-loading [data-auth] { visibility: hidden !important; }
    [data-auth][hidden] { display: none !important; }
`;
document.head.appendChild(style);
document.body.classList.add('pma-auth-loading');

function applyAuthState({ user, profile }) {
    const signedIn = !!user;
    const role = (profile?.role || '').toLowerCase();
    const displayName = profile
        ? `${profile.firstName || ''} ${profile.lastName || ''}`.trim() || profile.email
        : '';

    document.querySelectorAll('[data-auth]').forEach((el) => {
        const spec = el.getAttribute('data-auth');
        let show;
        if (spec === 'in') {
            show = signedIn;
        } else if (spec === 'out') {
            show = !signedIn;
        } else if (spec.startsWith('role:')) {
            show = signedIn && spec.slice(5).split(',').map((r) => r.trim()).includes(role);
        } else {
            show = true;
        }
        el.hidden = !show;
    });

    document.querySelectorAll('[data-user-name]').forEach((el) => {
        el.textContent = displayName;
    });
    document.querySelectorAll('[data-user-email]').forEach((el) => {
        el.textContent = profile?.email || user?.email || '';
    });
}

document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-logout]');
    if (trigger) {
        e.preventDefault();
        pmaSignOut();
    }
});

onAuth(applyAuthState);

// Point any "Admin Dashboard" links at the Firebase admin dashboard.
const adminAppUrl = (window.CONFIG && window.CONFIG.ADMIN_APP_URL) || './admin_dashboard.php';
document.querySelectorAll('.admin-app-link').forEach((el) => {
    el.setAttribute('href', adminAppUrl);
});

// Renders a Bootstrap-style alert inside `container` (an element or id).
export function showMessage(text, type, container) {
    const el = typeof container === 'string' ? document.getElementById(container) : container;
    if (!el) return;
    const cls = { success: 'alert-success', error: 'alert-danger', info: 'alert-info' }[type] || 'alert-info';
    const icon = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' }[type] || 'fa-info-circle';
    el.innerHTML = `<div class="alert ${cls}"><i class="fas ${icon}"></i> ${text}</div>`;
}

// Expose for non-module scripts on the page.
window.pmaAuth = { getAuthState, onAuth, signOut: pmaSignOut };
