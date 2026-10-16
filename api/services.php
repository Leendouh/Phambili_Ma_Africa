<?php

// Services API Endpoint
// Handles all service-related requests

require_once __DIR__ . '/../includes/api_bootstrap.inc.php';
require_once '../includes/service_contr.inc.php';

header('Content-Type: application/json');

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the request path
$path = $_GET['path'] ?? '';
$id = $_GET['id'] ?? null;

// Parse input data
$input = [];
$files = $_FILES ?? [];
if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        // Handle multipart/form-data (FormData with file uploads)
        $input = $_POST;
        $files = $_FILES;
    }
}

// Support _method parameter for PUT/PATCH via POST (workaround for PHP not supporting multipart PUT)
if ($method === 'POST' && isset($input['_method'])) {
    $method = strtoupper($input['_method']);
    unset($input['_method']);
}

$response = [];

switch ($method) {
    case 'GET':
        if ($id) {
            // Get single service
            $response = getServiceByIdController($pdo, $id);
        } elseif ($path === 'public') {
            // Get public services (for customers)
            $response = getPublicServicesController($pdo);
        } elseif ($path === 'categories') {
            // Get service categories
            $response = getServiceCategoriesController($pdo);
        } elseif ($path === 'stats') {
            // Get service statistics
            $response = getServiceStatsController($pdo);
        } elseif ($path === 'search') {
            // Search services
            $query = $_GET['q'] ?? '';
            $response = searchServicesController($pdo, $query);
        } else {
            // Get all services (admin)
            $response = getServicesController($pdo);
        }
        break;
        
    case 'POST':
        if ($path === 'toggle-availability') {
            // Toggle service availability
            $id = $input['id'] ?? null;
            $isAvailable = $input['is_available'] ?? true;
            $response = toggleServiceAvailabilityController($pdo, $id, $isAvailable);
        } else {
            // Create new service
            $response = createServiceController($pdo, $input, $files);
        }
        break;

    case 'PUT':
    case 'PATCH':
        if ($id) {
            // Update service
            $response = updateServiceController($pdo, $id, $input, $files);
        } else {
            $response = [
                'success' => false,
                'message' => 'Service ID is required for updates.',
                'code' => 400
            ];
        }
        break;
        
    case 'DELETE':
        if ($id) {
            // Delete service
            $response = deleteServiceController($pdo, $id);
        } else {
            $response = [
                'success' => false,
                'message' => 'Service ID is required for deletion.',
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
