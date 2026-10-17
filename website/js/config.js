// Configuration for Phambili Ma-Africa Website
const CONFIG = {
    // Firebase Configuration (same values as mobile/.env and admin/.env)
    // Firebase Console → Project Settings → Your Apps → Web App
    // (web API keys are public-by-design; access is enforced by firestore.rules)
    FIREBASE: {
        apiKey: 'AIzaSyCfQfCzeSLdZ-NQ_W1_c8W4SxKuZuLFytY',
        authDomain: 'phambili-ma-africa2.firebaseapp.com',
        projectId: 'phambili-ma-africa2',
        appId: '1:462820601375:web:11749bd1d65ad2ff11af2a'
    },

    // Admin dashboard URL — staff/admin users are sent here after login
    // (the Firebase-powered admin_dashboard.php; the React app in admin/ is retired)
    ADMIN_APP_URL: './admin_dashboard.php',

    // API Configuration (legacy PHP API — being phased out)
    API_BASE_URL: './api',

    // EmailJS Configuration
    EMAILJS: {
        PUBLIC_KEY: 'HnXj5Nt1w0sEDXeA2',      // Replace with your EmailJS Public Key
        SERVICE_ID: 'service_7rrn7be',
        FALLBACK_SERVICE_ID: 'service_i42vkxd',     // Replace with your EmailJS Service ID
        TEMPLATE_ID: 'template_byscv9z',     // Template for OTP emails
        CONTACT_TEMPLATE_ID: 'template_fdnmudr'  // Template for contact form (can be same or different)
    },

    // LocalStorage keys (only for cart and theme, auth handled by PHP sessions)
    STORAGE_KEYS: {
        CART: 'pma_cart',
        THEME: 'pma_theme'
    },

    // Site Configuration
    SITE_NAME: 'Phambili Ma-Africa',
    SUPPORT_EMAIL: 'info@phambilimaafrica.co.za',
    SUPPORT_PHONE: '+27 72 662 5951'
};

window.CONFIG = CONFIG;
