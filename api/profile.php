<?php

require_once __DIR__ . '/../includes/api_bootstrap.inc.php';
require_once __DIR__ . '/../includes/notifications_model.inc.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in as a customer.']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? $action;

try {
    if ($method === 'GET') {
        echo json_encode(pma_get_profile($pdo, $userId));
        exit();
    }

    if ($method !== 'POST' && $method !== 'PUT' && $method !== 'PATCH') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit();
    }

    switch ($action) {
        case 'update_profile':
            echo json_encode(pma_update_profile($pdo, $userId, $input));
            break;
        case 'update_address':
            echo json_encode(pma_update_address($pdo, $userId, $input));
            break;
        case 'change_password':
            echo json_encode(pma_change_password($pdo, $userId, $input));
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (Throwable $e) {
    error_log('Profile API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to process request.']);
}

function pma_get_profile(object $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT ID, Full_Name, Email, Phone, Address, createdAt, updatedAt FROM customers WHERE ID = ?');
    $stmt->execute([$userId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) {
        return ['success' => false, 'message' => 'Customer not found.'];
    }

    $address = $customer['Address'] ?? '';
    $parts = array_map('trim', explode(',', $address));

    return [
        'success' => true,
        'customer' => $customer,
        'address' => [
            'street' => $parts[0] ?? '',
            'city' => $parts[1] ?? '',
            'state' => $parts[2] ?? '',
            'postal' => $parts[3] ?? '',
        ],
        'notifications' => get_notifications($pdo, $userId, 'customer', false, 20),
        'unread_count' => get_unread_notification_count($pdo, $userId, 'customer'),
    ];
}

function pma_update_profile(object $pdo, int $userId, array $input): array
{
    $fullname = trim($input['fullname'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if ($fullname === '' || strlen($fullname) < 2) {
        return ['success' => false, 'message' => 'Full name must be at least 2 characters.'];
    }
    if (!preg_match("/^[a-zA-Z\s\-']+$/", $fullname)) {
        return ['success' => false, 'message' => 'Full name can only contain letters, spaces, hyphens, and apostrophes.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }
    $cleanPhone = preg_replace('/\D/', '', $phone);
    if (!preg_match('/^0[0-9]{9}$/', $cleanPhone)) {
        return ['success' => false, 'message' => 'Phone number must start with 0 followed by 9 digits.'];
    }

    $stmt = $pdo->prepare('SELECT ID FROM customers WHERE Email = ? AND ID != ?');
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email address is already in use by another account.'];
    }

    $stmt = $pdo->prepare('UPDATE customers SET Full_Name = ?, Email = ?, Phone = ?, updatedAt = NOW() WHERE ID = ?');
    $stmt->execute([$fullname, $email, $cleanPhone, $userId]);

    $_SESSION['user_name'] = $fullname;
    $_SESSION['user_email'] = $email;

    return ['success' => true, 'message' => 'Profile updated successfully!'];
}

function pma_update_address(object $pdo, int $userId, array $input): array
{
    $street = trim($input['address_street'] ?? '');
    $city = trim($input['address_city'] ?? '');
    $state = trim($input['address_state'] ?? '');
    $zip = trim($input['address_zip'] ?? '');

    if ($street === '' || $city === '' || $state === '' || $zip === '') {
        return ['success' => false, 'message' => 'All address fields are required.'];
    }

    $postal = preg_replace('/\D/', '', $zip);
    if (!ctype_digit($postal) || strlen($postal) !== 4) {
        return ['success' => false, 'message' => 'Postal code must be exactly 4 digits.'];
    }

    $newAddress = implode(', ', [$street, $city, $state, $postal]);
    $stmt = $pdo->prepare('UPDATE customers SET Address = ?, updatedAt = NOW() WHERE ID = ?');
    $stmt->execute([$newAddress, $userId]);

    return ['success' => true, 'message' => 'Address updated successfully!', 'address' => $newAddress];
}

function pma_change_password(object $pdo, int $userId, array $input): array
{
    $current = $input['current_password'] ?? '';
    $new = $input['new_password'] ?? '';
    $confirm = $input['confirm_password'] ?? '';

    if ($current === '' || $new === '') {
        return ['success' => false, 'message' => 'Current and new password are required.'];
    }
    if (strlen($new) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters long.'];
    }
    if ($new !== $confirm) {
        return ['success' => false, 'message' => 'New passwords do not match.'];
    }

    $stmt = $pdo->prepare('SELECT Password FROM customers WHERE ID = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($current, $user['Password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }

    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE customers SET Password = ? WHERE ID = ?');
    $stmt->execute([$hashed, $userId]);
    delete_all_remember_me_tokens($pdo, $userId, 'customer');

    return ['success' => true, 'message' => 'Password changed successfully!'];
}
