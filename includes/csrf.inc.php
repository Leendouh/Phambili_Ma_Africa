<?php

// CSRF Protection Functions

/**
 * Generate CSRF token
 * @return string - The CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token - The token to validate
 * @param int $maxAge - Maximum age of token in seconds (default: 3600 = 1 hour)
 * @return bool - True if valid, false otherwise
 */
function validateCSRFToken($token, $maxAge = 3600) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }

    // Check if token has expired
    if (time() - $_SESSION['csrf_token_time'] > $maxAge) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }

    // Validate token
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }

    return true;
}

/**
 * Regenerate CSRF token (call after successful form submission)
 */
function regenerateCSRFToken() {
    unset($_SESSION['csrf_token']);
    unset($_SESSION['csrf_token_time']);
    return generateCSRFToken();
}

/**
 * Get CSRF token for use in forms
 * @return string - The CSRF token
 */
function getCSRFToken() {
    return generateCSRFToken();
}

/**
 * Output CSRF token as hidden input field
 */
function csrfField() {
    $token = getCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from POST request
 * @return bool - True if valid, false otherwise
 */
function validateCSRFRequest() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($token)) {
        return false;
    }
    return validateCSRFToken($token);
}
