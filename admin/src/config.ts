/** Staff two-step verification (email link). Set VITE_REQUIRE_EMAIL_2FA=false to switch it off for local development. */
export const REQUIRE_EMAIL_2FA = import.meta.env.VITE_REQUIRE_EMAIL_2FA !== 'false';

/** localStorage key holding { uid, expires } once a staff member has completed the second step. */
export const MFA_STORAGE_KEY = 'pma_staff_2fa';

/** How long a completed second step stays valid (12 hours). */
export const MFA_TTL_MS = 12 * 60 * 60 * 1000;
