// Configuration for Phambili Ma-Africa Website
const CONFIG = {
    // API Configuration
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
