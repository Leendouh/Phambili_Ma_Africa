<?php
require_once __DIR__ . '/../includes/api_bootstrap.inc.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../includes/dbh.inc.php';
require_once '../includes/notifications_model.inc.php';

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_role'] ?? 'customer';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'mark_read':
            $notificationId = $input['notification_id'] ?? 0;
            if ($notificationId > 0) {
                mark_notification_read($pdo, $notificationId);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
            }
            break;

        case 'mark_all_read':
            mark_all_notifications_read($pdo, $userId, $userType);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $notificationId = $input['notification_id'] ?? 0;
            if ($notificationId > 0) {
                delete_notification($pdo, $notificationId);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
            }
            break;

        case 'get_unread_count':
            $count = get_unread_notification_count($pdo, $userId, $userType);
            echo json_encode(['success' => true, 'count' => $count]);
            break;

        case 'get_notifications':
            $unreadOnly = $input['unread_only'] ?? false;
            $limit = $input['limit'] ?? 10;
            $notifications = get_notifications($pdo, $userId, $userType, $unreadOnly, $limit);
            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
