<?php

require_once __DIR__ . '/../includes/api_bootstrap.inc.php';
require_once __DIR__ . '/../includes/email_helper.inc.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$message = trim($input['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

if (strlen($message) < 10) {
    echo json_encode(['success' => false, 'message' => 'Message must be at least 10 characters long.']);
    exit();
}

$emailSent = sendContactEmail($name, $email, $phone, $message);
echo json_encode([
    'success' => true,
    'message' => $emailSent
        ? 'Your message has been saved successfully!'
        : 'Your message was received but there was an issue saving it.',
]);
