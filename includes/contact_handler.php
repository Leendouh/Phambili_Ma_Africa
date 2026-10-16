<?php
// Start output buffering to catch any errors
ob_start();

// Set error handling to prevent HTML error output
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once 'config_session.inc.php';
require_once 'dbh.inc.php';
require_once 'email_helper.inc.php';

// Function to return JSON and exit
function returnJson($data) {
    // Clear any previous output
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Check if AJAX request (either via X-Requested-With header or Accept header)
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
              (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    // Validation
    if (empty($name) || empty($email) || empty($message)) {
        if ($isAjax) {
            returnJson(['success' => false, 'message' => 'Please fill in all required fields.']);
        }
        $_SESSION['contact_error'] = 'Please fill in all required fields.';
        header('Location: /contact.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($isAjax) {
            returnJson(['success' => false, 'message' => 'Please enter a valid email address.']);
        }
        $_SESSION['contact_error'] = 'Please enter a valid email address.';
        header('Location: /contact.php');
        exit;
    }

    if (strlen($message) < 10) {
        if ($isAjax) {
            returnJson(['success' => false, 'message' => 'Message must be at least 10 characters long.']);
        }
        $_SESSION['contact_error'] = 'Message must be at least 10 characters long.';
        header('Location: /contact.php');
        exit;
    }

    // Send email via PHP backend (optional since EmailJS handles it)
    $emailSent = sendContactEmail($name, $email, $phone, $message);

    if ($emailSent) {
        $successMsg = 'Your message has been saved successfully!';
    } else {
        $successMsg = 'Your message was received but there was an issue saving it to our database.';
    }

    if ($isAjax) {
        returnJson(['success' => true, 'message' => $successMsg]);
    }

    $_SESSION['contact_success'] = 'Your message has been sent successfully! We will get back to you soon.';
    header('Location: /contact.php');
    exit;
} else {
    header('Location: /contact.php');
    exit;
}
