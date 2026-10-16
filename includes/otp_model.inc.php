<?php

// OTP Model - Handles OTP generation, storage, and verification

/**
 * Generate a 6-digit OTP code
 * @return string - 6-digit OTP code
 */
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Store OTP in database with 10-minute expiry
 * @param PDO $pdo - Database connection
 * @param string $email - User email
 * @param string $otpType - Type of OTP (email_verification, password_reset, contact_verification)
 * @param int|null $userId - User ID (optional)
 * @return string - The generated OTP code
 */
function storeOTP($pdo, $email, $otpType, $userId = null) {
    $otpCode = generateOTP();

    $stmt = $pdo->prepare("
        INSERT INTO otps (email, otp_code, otp_type, user_id, expires_at, is_used)
        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), FALSE)
    ");
    $stmt->execute([$email, $otpCode, $otpType, $userId]);
    
    return $otpCode;
}

/**
 * Verify OTP code
 */
function verifyOTP($pdo, $email, $otpCode, $otpType) {
    error_log("OTP verification attempt for email: $email, type: $otpType");
    
    $stmt = $pdo->prepare("
        SELECT * FROM otps
        WHERE email = ? AND otp_type = ? AND is_used = FALSE AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$email, $otpType]);
    $otp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otp) {
        error_log("OTP verification failed - No valid OTP found for email: $email, type: $otpType");
        return [
            'valid' => false,
            'message' => 'Invalid or expired OTP code'
        ];
    }
    
    error_log("OTP code comparison for email: $email");

    if ($otp['otp_code'] !== $otpCode) {
        error_log("OTP code mismatch for email: $email");
        return [
            'valid' => false,
            'message' => 'Invalid OTP code'
        ];
    }
    
    // Mark OTP as used
    $updateStmt = $pdo->prepare("UPDATE otps SET is_used = TRUE WHERE ID = ?");
    $updateStmt->execute([$otp['ID']]);
    
    error_log("OTP verified successfully for email: $email");
    
    return [
        'valid' => true,
        'message' => 'OTP verified successfully',
        'user_id' => $otp['user_id']
    ];
}

/**
 * Clean up expired OTPs
 */
function cleanupExpiredOTPs($pdo) {
    $stmt = $pdo->prepare("DELETE FROM otps WHERE expires_at < NOW() OR is_used = TRUE");
    return $stmt->execute();
}

/**
 * Check if email has a pending OTP
 */
function hasPendingOTP($pdo, $email, $otpType) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM otps
        WHERE email = ? AND otp_type = ? AND is_used = FALSE AND expires_at > NOW()
    ");
    $stmt->execute([$email, $otpType]);
    return $stmt->fetchColumn() > 0;
}
