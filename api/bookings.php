<?php

// Bookings API Endpoint
// Handles all booking-related requests

require_once __DIR__ . '/../includes/api_bootstrap.inc.php';
require_once '../includes/booking_contr.inc.php';

header('Content-Type: application/json');

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the request path and parameters
$path = $_GET['path'] ?? '';
$id = $_GET['id'] ?? null;
$customerId = $_SESSION['user_id'] ?? null;

// Parse input data
$input = [];
if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }
}

$response = [];

switch ($method) {
    case 'GET':
        if ($id) {
            // Get single booking
            $response = getBookingByIdController($pdo, $id);
        } elseif ($path === 'my-bookings') {
            // Get customer's own bookings
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $response = getCustomerBookingsController($pdo, $customerId, $page, $limit);
        } elseif ($path === 'stats') {
            // Get booking statistics
            $response = getBookingStatsController($pdo);
        } elseif ($path === 'analytics') {
            // Get booking analytics
            $period = $_GET['period'] ?? 'monthly';
            $response = getBookingAnalyticsController($pdo, $period);
        } elseif ($path === 'cards') {
            // Get bookings as cards (for admin dashboard)
            $filters = [
                'status' => $_GET['status'] ?? null,
                'page' => $_GET['page'] ?? 1,
                'limit' => $_GET['limit'] ?? 12
            ];
            $response = getBookingsAsCardsController($pdo, $filters);
        } elseif ($path === 'check-availability') {
            // Check booking availability
            $serviceId = $_GET['service_id'] ?? null;
            $date = $_GET['date'] ?? null;
            $response = checkBookingAvailabilityController($pdo, $customerId, $serviceId, $date);
        } elseif ($_GET['action'] === 'get_availability') {
            // Get availability for calendar
            $serviceId = $_GET['service_id'] ?? null;
            $response = getAvailabilityForCalendar($pdo, $serviceId);
        } else {
            // Get all bookings (admin)
            $filters = [
                'status' => $_GET['status'] ?? null,
                'customer_id' => $_GET['customer_id'] ?? null,
                'service_id' => $_GET['service_id'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => $_GET['page'] ?? 1,
                'limit' => $_GET['limit'] ?? 10
            ];
            $response = getAllBookingsController($pdo, $filters);
        }
        break;
        
    case 'POST':
        if ($path === 'create') {
            // Create new booking
            $response = createBookingController($pdo, $input, $customerId);
        } elseif ($path === 'mark-contacted') {
            // Quick action: Mark as contacted
            $id = $input['id'] ?? null;
            $response = markAsContactedController($pdo, $id, $input, $_SESSION['admin_id'] ?? null);
        } elseif ($path === 'move-in-progress') {
            // Quick action: Move to in progress
            $id = $input['id'] ?? null;
            $response = moveToInProgressController($pdo, $id, $input, $_SESSION['admin_id'] ?? null);
        } elseif ($path === 'provide-quotation') {
            // Quick action: Provide quotation
            $id = $input['id'] ?? null;
            $response = provideQuotationController($pdo, $id, $input, $_SESSION['admin_id'] ?? null);
        } elseif ($path === 'request-quote-modification') {
            // Request quote modification
            require_once '../includes/booking_contr.inc.php';
            $bookingId = $input['booking_id'] ?? null;
            $customerId = $_SESSION['user_id'] ?? null;
            $reason = $input['reason'] ?? '';
            $requestedChanges = $input['requested_changes'] ?? '';

            if (!$bookingId || !$customerId || empty($reason)) {
                $response = ['success' => false, 'message' => 'Missing required fields'];
            } else {
                $result = createQuoteModificationRequest($pdo, $bookingId, $customerId, $reason, $requestedChanges);
                if ($result) {
                    $response = ['success' => true, 'message' => 'Quote modification request submitted successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to submit modification request'];
                }
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Invalid action.',
                'code' => 400
            ];
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        if ($id) {
            if ($path === 'status') {
                // Update booking status only
                $response = updateBookingStatusController($pdo, $id, $input['status'] ?? null, $input, $_SESSION['admin_id'] ?? null);
            } elseif ($path === 'quote') {
                // Update booking quote
                $response = updateBookingQuoteController($pdo, $id, $input, $_SESSION['admin_id'] ?? null);
            } else {
                // Update booking
                $response = updateBookingController($pdo, $id, $input);
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Booking ID is required for updates.',
                'code' => 400
            ];
        }
        break;
        
    case 'DELETE':
        if ($id) {
            // Delete booking
            $response = deleteBookingController($pdo, $id);
        } else {
            $response = [
                'success' => false,
                'message' => 'Booking ID is required for deletion.',
                'code' => 400
            ];
        }
        break;
        
    default:
        $response = [
            'success' => false,
            'message' => 'Method not allowed.',
            'code' => 405
        ];
}

// Set HTTP status code
if (isset($response['code'])) {
    http_response_code($response['code']);
    unset($response['code']);
} elseif (!$response['success']) {
    http_response_code(500);
}

// Return JSON response
echo json_encode($response);
