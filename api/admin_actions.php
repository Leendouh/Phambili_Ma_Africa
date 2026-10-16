<?php
require_once __DIR__ . '/../includes/api_bootstrap.inc.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'contact-customer':
        contactCustomer($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function contactCustomer($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $booking_id = $data['booking_id'] ?? 0;

    if (!$booking_id) {
        echo json_encode(['success' => false, 'message' => 'Booking ID required']);
        exit;
    }

    try {
        // Get booking and customer info
        $stmt = $pdo->prepare("
            SELECT b.*, u.full_name as Customer_Name, u.email as Customer_Email 
            FROM bookings b 
            LEFT JOIN customers u ON b.Customer_ID = u.ID 
            WHERE b.ID = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            exit;
        }

        // Update status to contacted
        $updateStmt = $pdo->prepare("UPDATE bookings SET Status = 'contacted' WHERE ID = ?");
        $updateStmt->execute([$booking_id]);

        // Create notification for customer using helper function
        require_once '../includes/notifications_model.inc.php';
        create_notification(
            $pdo,
            $booking['Customer_ID'],
            'customer',
            'Admin Wants to Contact You',
            'The admin from Phambili Ma-Africa would like to contact you regarding your booking #' . $booking_id . '. Please check your email or phone for a message from us.',
            'contact'
        );

        echo json_encode(['success' => true, 'message' => 'Contact notification sent']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
