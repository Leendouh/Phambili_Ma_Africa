<?php

// Load environment variables
require_once __DIR__ . '/env_loader.inc.php';

// Email Helper - Handles sending emails including OTP

/**
 * Email Helper Functions with PHPMailer + Mailtrap SMTP
 *
 * SETUP INSTRUCTIONS:
 * 1. Install PHPMailer via Composer:
 *    composer require phpmailer/phpmailer
 *
 *    OR download manually from: https://github.com/PHPMailer/PHPMailer/releases
 *    Extract to: vendor/phpmailer/phpmailer/src/ or src/
 *
 * 2. Get Mailtrap credentials from: https://mailtrap.io/
 *    - Sign up for free account
 *    - Go to SMTP Settings -> Integration
 *    - Copy host, port, username, password
 *
 * 3. Update credentials in .env file
 */

// Mailtrap SMTP Configuration (Test Environment)
define('MAILTRAP_HOST', env('MAILTRAP_HOST'));
define('MAILTRAP_PORT', env('MAILTRAP_PORT'));
define('MAILTRAP_USERNAME', env('MAILTRAP_USERNAME'));
define('MAILTRAP_PASSWORD', env('MAILTRAP_PASSWORD'));

// For production, replace with your actual SMTP settings
define('SMTP_HOST', env('SMTP_HOST'));
define('SMTP_PORT', env('SMTP_PORT'));
define('SMTP_USERNAME', env('SMTP_USERNAME'));
define('SMTP_PASSWORD', env('SMTP_PASSWORD'));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME'));

// Load PHPMailer (adjust path based on installation method)
$phpmailerAvailable = false;
$phpmailerLoaded = false; // Flag to prevent multiple loading attempts

// Check if PHPMailer is available
if (!$phpmailerLoaded) {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $phpmailerAvailable = true;
            error_log("PHPMailer loaded via Composer autoload");
        }
    } elseif (file_exists(__DIR__ . '/../src/PHPMailer.php')) {
        // PHPMailer is in src directory
        require_once __DIR__ . '/../src/PHPMailer.php';
        require_once __DIR__ . '/../src/SMTP.php';
        require_once __DIR__ . '/../src/Exception.php';
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $phpmailerAvailable = true;
            error_log("PHPMailer loaded from src directory");
        } else {
            error_log("PHPMailer class not found after loading from src");
        }
    } elseif (file_exists(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
        // PHPMailer is in vendor/phpmailer directory
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $phpmailerAvailable = true;
            error_log("PHPMailer loaded from vendor/phpmailer directory");
        }
    }
    $phpmailerLoaded = true;
}

if (!$phpmailerAvailable) {
    error_log("PHPMailer is NOT available - will use fallback mail() function");
}

/**
 * Send OTP email using PHPMailer or fallback to mail()
 * @param string $email - Recipient email
 * @param string $otpCode - OTP code
 * @param string $otpType - Type of OTP (email_verification, password_reset, contact_verification)
 * @return bool - True if sent successfully, false otherwise
 */
function sendOTPEmail($email, $otpCode, $otpType) {
    global $phpmailerAvailable;
    
    error_log("sendOTPEmail called - PHPMailer available: " . ($phpmailerAvailable ? 'YES' : 'NO'));
    
    $subject = '';
    $message = '';
    
    switch ($otpType) {
        case 'email_verification':
            $subject = 'Verify Your Email - Phambili Ma-Africa';
            $message = "
                <html>
                <head>
                    <title>Email Verification</title>
                </head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color: #2563eb;'>Verify Your Email Address</h2>
                        <p>Thank you for registering with Phambili Ma-Africa!</p>
                        <p>Your verification code is:</p>
                        <div style='background: #f3f4f6; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                            {$otpCode}
                        </div>
                        <p>This code will expire in 10 minutes.</p>
                        <p>If you didn't request this code, please ignore this email.</p>
                        <p>Best regards,<br>Phambili Ma-Africa Team</p>
                    </div>
                </body>
                </html>
            ";
            break;
            
        case 'password_reset':
            $subject = 'Password Reset Code - Phambili Ma-Africa';
            $message = "
                <html>
                <head>
                    <title>Password Reset</title>
                </head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color: #2563eb;'>Reset Your Password</h2>
                        <p>We received a request to reset your password.</p>
                        <p>Your password reset code is:</p>
                        <div style='background: #f3f4f6; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                            {$otpCode}
                        </div>
                        <p>This code will expire in 10 minutes.</p>
                        <p>If you didn't request this code, please ignore this email.</p>
                        <p>Best regards,<br>Phambili Ma-Africa Team</p>
                    </div>
                </body>
                </html>
            ";
            break;
            
        case 'contact_verification':
            $subject = 'Verification Code - Phambili Ma-Africa';
            $message = "
                <html>
                <head>
                    <title>Verification Code</title>
                </head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color: #2563eb;'>Verification Code</h2>
                        <p>Your verification code is:</p>
                        <div style='background: #f3f4f6; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                            {$otpCode}
                        </div>
                        <p>This code will expire in 10 minutes.</p>
                        <p>Best regards,<br>Phambili Ma-Africa Team</p>
                    </div>
                </body>
                </html>
            ";
            break;
    }
    
    // Try PHPMailer if available
    if ($phpmailerAvailable) {
        try {
            error_log("Attempting to send via PHPMailer");
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAILTRAP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAILTRAP_USERNAME;
            $mail->Password   = MAILTRAP_PASSWORD;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAILTRAP_PORT;
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
            error_log("PHPMailer: Email sent successfully to $email");
            return true;
        } catch (\Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage() . " - Falling back to mail()");
            error_log("PHPMailer Error Details: " . $e->getTraceAsString());
        }
    }
    
    // Fallback to native PHP mail()
    error_log("Attempting to send via native mail() function");
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Phambili Ma-Africa <noreply@phambili-ma-africa.co.za>\r\n";
    
    $result = mail($email, $subject, $message, $headers);
    error_log("Native mail(): Email " . ($result ? "sent successfully" : "failed") . " to $email");
    return $result;
}

/**
 * Send contact form email to admin using PHPMailer or fallback to mail()
 * @param string $name - Sender name
 * @param string $email - Sender email
 * @param string $phone - Sender phone (optional)
 * @param string $message - Message content
 * @return bool - True if sent successfully, false otherwise
 */
function sendContactEmail($name, $email, $phone, $message) {
    global $phpmailerAvailable;
    
    $subject = 'New Contact Form Submission - Phambili Ma-Africa';
    $body = "
        <html>
        <head>
            <title>Contact Form Submission</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>New Contact Form Submission</h2>
                <p><strong>Name:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Phone:</strong> {$phone}</p>
                <p><strong>Message:</strong></p>
                <p style='background: #f3f4f6; padding: 15px;'>{$message}</p>
            </div>
        </body>
        </html>
    ";
    
    // Try PHPMailer if available
    if ($phpmailerAvailable) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAILTRAP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAILTRAP_USERNAME;
            $mail->Password   = MAILTRAP_PASSWORD;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAILTRAP_PORT;
            $mail->setFrom($email, $name);
            $mail->addAddress('admin@phambili-ma-africa.co.za', 'Admin');
            $mail->addReplyTo($email, $name);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            error_log("PHPMailer: Contact email sent successfully from $email");
            return true;
        } catch (\Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage() . " - Falling back to mail()");
        }
    }
    
    // Fallback to native PHP mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$name} <{$email}>\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    
    $adminEmail = 'admin@phambili-ma-africa.co.za';
    $result = mail($adminEmail, $subject, $body, $headers);
    error_log("Native mail(): Contact email " . ($result ? "sent successfully" : "failed") . " from $email");
    return $result;
}
