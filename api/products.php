<?php

// Products API Endpoint
// Handles all product-related requests

require_once __DIR__ . '/../includes/api_bootstrap.inc.php';
require_once '../includes/product_contr.inc.php';

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
            // Get single product
            $response = getProductByIdController($pdo, $id);
        } elseif ($path === 'public') {
            // Get public products (for customers)
            $response = getPublicProductsController($pdo);
        } elseif ($path === 'categories') {
            // Get product categories
            $response = getProductCategoriesController($pdo);
        } elseif ($path === 'stats') {
            // Get product statistics
            $response = getProductStatsController($pdo);
        } elseif ($path === 'search') {
            // Search products
            $query = $_GET['q'] ?? '';
            $response = searchProductsController($pdo, $query);
        } else {
            // Get all products (admin)
            $response = getProductsController($pdo);
        }
        break;
        
    case 'POST':
        if ($path === 'toggle-availability') {
            // Toggle product availability
            $id = $input['id'] ?? null;
            $isAvailable = $input['is_available'] ?? true;
            $response = toggleProductAvailabilityController($pdo, $id, $isAvailable);
        } elseif ($path === 'update-stock') {
            // Update product stock
            $id = $input['id'] ?? null;
            $quantity = $input['quantity'] ?? null;
            $response = updateProductStockController($pdo, $id, $quantity);
        } else {
            // Create new product
            $response = createProductController($pdo, $input, $files);
        }
        break;

    case 'PUT':
    case 'PATCH':
        if ($id) {
            // Update product
            $response = updateProductController($pdo, $id, $input, $files);
        } else {
            $response = [
                'success' => false,
                'message' => 'Product ID is required for updates.',
                'code' => 400
            ];
        }
        break;
        
    case 'DELETE':
        if ($id) {
            // Delete product
            $response = deleteProductController($pdo, $id);
        } else {
            $response = [
                'success' => false,
                'message' => 'Product ID is required for deletion.',
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
