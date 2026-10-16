<?php
// Ensure clean JSON output: buffer and suppress displayed errors
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

require_once __DIR__ . '/../includes/api_bootstrap.inc.php';
require_once '../includes/otp_model.inc.php';
require_once '../includes/email_helper.inc.php';
require_once '../includes/signup_model.inc.php';
require_once '../includes/rate_limiter.inc.php';

// Clear any accidental output from includes
ob_end_clean();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'send-otp':
        sendOTP($pdo);
        break;
    case 'verify-otp':
        verifyOTPCode($pdo);
        break;
    case 'reset-password':
        resetPassword($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

function sendOTP($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $otpType = $data['otp_type'] ?? '';

    if (empty($email) || empty($otpType)) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP type are required']);
        exit;
    }

    // Apply enhanced rate limiting based on IP address and email
    $clientIP = getClientIP();
    $rateLimitError = enforceRateLimit($pdo, $clientIP, 'otp_' . $otpType, 5, 900); // 5 attempts per 15 minutes
    if ($rateLimitError) {
        echo json_encode([
            'success' => false,
            'message' => $rateLimitError['message']
        ]);
        exit;
    }

    // Rate limiting: Check if an OTP was sent in the last 60 seconds for this email
    $rateLimitStmt = $pdo->prepare("
        SELECT created_at FROM otps
        WHERE email = ? AND otp_type = ?
        AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
        ORDER BY created_at DESC LIMIT 1
    ");
    $rateLimitStmt->execute([$email, $otpType]);
    $recentOTP = $rateLimitStmt->fetch(PDO::FETCH_ASSOC);

    if ($recentOTP) {
        $timeRemaining = 60 - (time() - strtotime($recentOTP['created_at']));
        echo json_encode([
            'success' => false,
            'message' => "Please wait {$timeRemaining} seconds before requesting another code"
        ]);
        exit;
    }
    
    try {
        $userId = null;
        
        // For email verification and password reset, check if email exists
        if ($otpType === 'password_reset') {
            // Check if email exists in customers or admins table
            $stmt = $pdo->prepare("
                SELECT ID FROM customers WHERE Email = ? 
                UNION 
                SELECT ID FROM admins WHERE Email = ?
            ");
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Email address not found']);
                exit;
            }
            
            $userId = $user['ID'];
        } elseif ($otpType === 'email_verification') {
            // For email verification, check if email already exists
            $stmt = $pdo->prepare("
                SELECT ID FROM customers WHERE Email = ? 
                UNION 
                SELECT ID FROM admins WHERE Email = ?
            ");
            $stmt->execute([$email, $email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                echo json_encode(['success' => false, 'message' => 'Email address already registered']);
                exit;
            }
        }
        
        // Delete any existing unused OTPs of the same type for this email
        $deleteStmt = $pdo->prepare("DELETE FROM otps WHERE email = ? AND otp_type = ? AND is_used = FALSE");
        $deleteStmt->execute([$email, $otpType]);
        
        // Generate and store OTP
        $otpCode = storeOTP($pdo, $email, $otpType, $userId);

        // Log OTP generation for debugging (without exposing the code)
        error_log("OTP Generated for $email | OTP Type: $otpType");

        // Return OTP to frontend for EmailJS to send
        // Note: EmailJS will send the email client-side to avoid InfinityFree SMTP restrictions
        echo json_encode([
            'success' => true,
            'message' => 'OTP generated successfully',
            'otp_code' => $otpCode,
            'email' => $email,
            'otp_type' => $otpType
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function verifyOTPCode($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $otpCode = $data['otp_code'] ?? '';
    $otpType = $data['otp_type'] ?? '';
    
    if (empty($email) || empty($otpCode) || empty($otpType)) {
        echo json_encode(['success' => false, 'message' => 'Email, OTP code, and OTP type are required']);
        exit;
    }
    
    try {
        $result = verifyOTP($pdo, $email, $otpCode, $otpType);
        
        if ($result['valid']) {
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'user_id' => $result['user_id']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function resetPassword($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    
    if (empty($email) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Email and new password are required']);
        exit;
    }
    
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        exit;
    }
    
    try {
        // Check if email exists in customers table
        $stmt = $pdo->prepare("SELECT ID FROM customers WHERE Email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            // Update customer password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE customers SET Password = ? WHERE Email = ?");
            $updateStmt->execute([$hashedPassword, $email]);
            
            // Delete remember me tokens
            $deleteTokens = $pdo->prepare("DELETE FROM remember_me_tokens WHERE user_id = ? AND user_type = 'customer'");
            $deleteTokens->execute([$customer['ID']]);
            
            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
            return;
        }
        
        // Check if email exists in admins table
        $stmt = $pdo->prepare("SELECT ID FROM admins WHERE Email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            // Update admin password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE admins SET Password = ? WHERE Email = ?");
            $updateStmt->execute([$hashedPassword, $email]);
            
            // Delete remember me tokens
            $deleteTokens = $pdo->prepare("DELETE FROM remember_me_tokens WHERE user_id = ? AND user_type = 'admin'");
            $deleteTokens->execute([$admin['ID']]);
            
            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
            return;
        }
        
        echo json_encode(['success' => false, 'message' => 'Email address not found']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
