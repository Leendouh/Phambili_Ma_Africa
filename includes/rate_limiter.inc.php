<?php

// Rate Limiting Functions

/**
 * Check if rate limit has been exceeded
 * @param PDO $pdo - Database connection
 * @param string $identifier - Unique identifier (IP address, email, etc.)
 * @param string $action - Action type (login, otp, password_reset, etc.)
 * @param int $maxAttempts - Maximum number of attempts allowed
 * @param int $timeWindow - Time window in seconds
 * @return array - ['allowed' => bool, 'remaining' => int, 'retry_after' => int|null]
 */
function checkRateLimit($pdo, $identifier, $action, $maxAttempts = 5, $timeWindow = 900) {
    // Clean up old rate limit records
    cleanupRateLimits($pdo);

    // Check current attempts (all positional ? placeholders — no mixing with :named)
    // Also check for records without prefix for backward compatibility
    $identifierWithoutPrefix = str_replace(['ip:', 'email:', 'session:'], '', $identifier);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts,
               TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()) as seconds_since_last
        FROM rate_limits
        WHERE (identifier = ? OR identifier = ?)
        AND action = ?
        AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$identifier, $identifierWithoutPrefix, $action, $timeWindow]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $attempts = $result['attempts'] ?? 0;
    $remaining = max(0, $maxAttempts - $attempts);

    if ($attempts >= $maxAttempts) {
        // Calculate retry after time entirely using MySQL's consistent time
        $secondsSinceLast = (int)($result['seconds_since_last'] ?? 0);
        $retryAfter = $timeWindow - $secondsSinceLast;

        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => max(0, $retryAfter)
        ];
    }

    return [
        'allowed' => true,
        'remaining' => $remaining,
        'retry_after' => null
    ];
}

/**
 * Record a rate limit attempt
 * @param PDO $pdo - Database connection
 * @param string $identifier - Unique identifier (IP address, email, etc.)
 * @param string $action - Action type (login, otp, password_reset, etc.)
 * @return bool - True if recorded successfully
 */
function recordRateLimitAttempt($pdo, $identifier, $action) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO rate_limits (identifier, action, created_at)
            VALUES (?, ?, NOW())
        ");
        return $stmt->execute([$identifier, $action]);
    } catch (PDOException $e) {
        error_log("Rate limit record failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up old rate limit records
 * @param PDO $pdo - Database connection
 * @return bool - True if cleaned successfully
 */
function cleanupRateLimits($pdo) {
    $stmt = $pdo->prepare("
        DELETE FROM rate_limits 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    return $stmt->execute();
}

/**
 * Get client IP address
 * @return string - Client IP address
 */
function getClientIP() {
    $ip = '';
    
    // Check for proxy headers in order of preference
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        // Cloudflare
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // HTTP_X_FORWARDED_FOR can contain a comma-separated list of IPs.
        // The first IP is the original client IP.
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    // Validate IP address
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

/**
 * Check rate limit and return error if exceeded (does NOT record an attempt).
 * Call recordRateLimitAttempt() manually after a failed attempt.
 * @param PDO $pdo - Database connection
 * @param string $identifier - Unique identifier
 * @param string $action - Action type
 * @param int $maxAttempts - Maximum attempts
 * @param int $timeWindow - Time window in seconds
 * @return array|null - Error array if exceeded, null if allowed
 */
function enforceRateLimit($pdo, $identifier, $action, $maxAttempts = 5, $timeWindow = 900) {
    $result = checkRateLimit($pdo, $identifier, $action, $maxAttempts, $timeWindow);

    if (!$result['allowed']) {
        $retryAfter = $result['retry_after'];
        $minutes = ceil($retryAfter / 60);

        return [
            'success'     => false,
            'message'     => "Too many login attempts. Please try again in {$minutes} minute(s).",
            'retry_after' => $retryAfter
        ];
    }

    return null; // Allowed - caller must call recordRateLimitAttempt() on failure
}

/**
 * Clear all rate limit attempts for an identifier/action (call on successful login).
 * @param PDO $pdo - Database connection
 * @param string $identifier - Unique identifier (e.g. IP address)
 * @param string $action - Action type (e.g. 'login')
 * @return bool - True if cleared successfully
 */
function clearRateLimitAttempts($pdo, $identifier, $action) {
    $stmt = $pdo->prepare("
        DELETE FROM rate_limits
        WHERE identifier = ? AND action = ?
    ");
    return $stmt->execute([$identifier, $action]);
}
