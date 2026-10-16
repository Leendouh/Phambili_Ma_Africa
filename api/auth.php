<?php

require_once __DIR__ . '/../includes/api_bootstrap.inc.php';
require_once __DIR__ . '/../includes/signup_model.inc.php';
require_once __DIR__ . '/../includes/rate_limiter.inc.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && ($action === 'me' || $action === '')) {
    echo json_encode(pma_current_user_payload());
    exit();
}

if ($method === 'GET' && $action === 'csrf') {
    echo json_encode(['success' => true, 'csrf_token' => getCSRFToken()]);
    exit();
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? $action;

try {
    switch ($action) {
        case 'login':
            echo json_encode(pma_api_login($pdo, $input));
            break;
        case 'signup':
            echo json_encode(pma_api_signup($pdo, $input));
            break;
        case 'logout':
            echo json_encode(pma_api_logout($pdo));
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (Throwable $e) {
    error_log('Auth API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to process request.']);
}

function pma_api_login(object $pdo, array $input): array
{
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $remember = !empty($input['remember']);

    $identifiers = ['email:' . strtolower($email)];
    foreach ($identifiers as $identifier) {
        $error = enforceRateLimit($pdo, $identifier, 'login', 5, 900);
        if ($error) {
            http_response_code(429);
            return [
                'success' => false,
                'message' => $error['message'],
                'retry_after' => $error['retry_after'] ?? null,
            ];
        }
    }

    if ($email === '' || $password === '') {
        return ['success' => false, 'message' => 'All fields are required.'];
    }

    $result = get_user_unified($pdo, $email);
    if ($result === false || empty($result)) {
        foreach ($identifiers as $identifier) {
            recordRateLimitAttempt($pdo, $identifier, 'login');
        }
        return ['success' => false, 'message' => 'Incorrect email address.'];
    }

    $storedHash = $result['Password'] ?? $result['password'] ?? null;
    $passwordWrong = empty($storedHash) || !is_string($storedHash) || !password_verify($password, $storedHash);
    if ($passwordWrong) {
        foreach ($identifiers as $identifier) {
            recordRateLimitAttempt($pdo, $identifier, 'login');
        }
        return ['success' => false, 'message' => 'Incorrect password.'];
    }

    foreach ($identifiers as $identifier) {
        clearRateLimitAttempts($pdo, $identifier, 'login');
    }

    $userType = $result['user_type'] ?? 'customer';
    pma_establish_session($result);

    $token = null;
    if ($remember || true) {
        // Always issue a token so Capacitor can authenticate without cookies
        $token = pma_issue_auth_token($pdo, $result, $userType);
        if ($remember) {
            $cookieExpire = time() + (30 * 24 * 60 * 60);
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            setcookie('remember_me', $token, [
                'expires' => $cookieExpire,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    $payload = pma_current_user_payload();
    $payload['message'] = 'Login successful.';
    $payload['token'] = $token;
    $payload['redirect'] = $userType === 'admin' ? '/admin' : '/';
    return $payload;
}

function pma_api_signup(object $pdo, array $input): array
{
    $fullName = trim($input['fullName'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';

    $errors = [];
    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $errors['empty_input'] = 'All fields are required.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['invalid_email'] = 'Please enter a valid email address.';
    }
    if ($fullName !== '' && !preg_match("/^[a-zA-Z\s\-']+$/", $fullName)) {
        $errors['invalid_fullname'] = 'Full name can only contain letters and spaces.';
    }
    if ($password !== $confirmPassword) {
        $errors['password_mismatch'] = 'Passwords do not match.';
    }
    if ($email !== '' && get_email($pdo, $email)) {
        $errors['email_exists'] = 'An account with this email already exists.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'message' => 'Registration failed', 'errors' => $errors];
    }

    set_user($pdo, $fullName, $email, $password);
    $result = get_user_unified($pdo, $email);
    pma_establish_session($result);
    $token = pma_issue_auth_token($pdo, $result, 'customer');

    $payload = pma_current_user_payload();
    $payload['message'] = 'Registration successful';
    $payload['token'] = $token;
    return $payload;
}

function pma_api_logout(object $pdo): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
        $parts = explode(':', $matches[1]);
        if (count($parts) === 2) {
            delete_remember_me_token($pdo, $parts[0]);
        }
    }

    if (isset($_COOKIE['remember_me'])) {
        $parts = explode(':', $_COOKIE['remember_me']);
        if (count($parts) === 2) {
            delete_remember_me_token($pdo, $parts[0]);
        }
        setcookie('remember_me', '', time() - 3600, '/', '', false, true);
    }

    session_unset();
    session_destroy();

    return ['success' => true, 'message' => 'Logged out successfully.'];
}
