// Shared notification-bell wiring for website pages.
// Replaces the per-page inline scripts that POSTed to
// api/notification_actions.php.
//
// Expected markup (already present in the page headers):
//   .notification-bell-customer          wrapper (data-auth="role:customer")
//   .bell-btn-customer                   toggle button
//   #notificationDropdownCustomer        dropdown panel
//   .notification-dropdown-content       list container (filled by JS)
//   .mark-all-read-btn                   mark-all button

import { onAuth } from './auth.js';
import {
    watchNotifications,
    markNotificationRead,
    markAllNotificationsRead,
} from './data.js';

let unsubscribe = null;

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (isNaN(d)) return '';
    return d.toLocaleString('en-ZA', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit',
    });
}

function esc(s) {
    const div = document.createElement('div');
    div.textContent = s ?? '';
    return div.innerHTML;
}

function render(items, unreadCount) {
    const content = document.querySelector('.notification-dropdown-content');
    const bell = document.querySelector('.bell-btn-customer');
    if (!content) return;

    if (!items.length) {
        content.innerHTML = `
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <p>No new notifications</p>
            </div>`;
    } else {
        content.innerHTML = items.map((n) => `
            <div class="notification-dropdown-item ${n.is_read ? '' : 'unread'}" data-id="${esc(n.ID)}">
                <div class="notif-content">
                    <strong>${esc(n.title || 'Notification')}</strong>
                    <p>${esc(n.message)}</p>
                    <small>${formatDate(n.created_at)}</small>
                </div>
                <button class="notif-close" data-notif-id="${esc(n.ID)}" aria-label="Mark read">
                    <i class="fas fa-times"></i>
                </button>
            </div>`).join('');
    }

    if (bell) {
        let badge = bell.querySelector('.notification-badge-customer');
        if (unreadCount > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge-customer';
                bell.appendChild(badge);
            }
            badge.textContent = unreadCount;
        } else if (badge) {
            badge.remove();
        }
    }
}

// Toggle + outside-click close (global for inline onclick handlers)
window.toggleCustomerNotificationDropdown = function () {
    document.getElementById('notificationDropdownCustomer')?.classList.toggle('active');
};

document.addEventListener('click', (event) => {
    const bell = document.querySelector('.notification-bell-customer');
    if (bell && !bell.contains(event.target)) {
        document.getElementById('notificationDropdownCustomer')?.classList.remove('active');
    }
});

window.markCustomerNotificationRead = async function (id) {
    try {
        await markNotificationRead(id);
    } catch (e) {
        console.error('Error marking notification read:', e);
    }
};

window.markAllCustomerNotificationsRead = async function () {
    const { user } = await import('./auth.js').then((m) => m.whenAuthReady());
    if (!user) return;
    // Mark everything currently rendered as read
    const ids = [...document.querySelectorAll('.notification-dropdown-item')]
        .map((el) => el.dataset.id)
        .filter(Boolean);
    try {
        await markAllNotificationsRead(ids);
    } catch (e) {
        console.error('Error marking all read:', e);
    }
};

// Delegate clicks on per-item close buttons (JS-rendered)
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.notif-close[data-notif-id]');
    if (btn) {
        e.preventDefault();
        window.markCustomerNotificationRead(btn.dataset.notifId);
    }
});

onAuth(({ user }) => {
    if (unsubscribe) {
        unsubscribe();
        unsubscribe = null;
    }
    if (user) {
        unsubscribe = watchNotifications(user.uid, render, 10);
    } else {
        render([], 0);
    }
});
