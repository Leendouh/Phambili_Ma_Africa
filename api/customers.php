<?php

// Customers API Endpoint
// Handles all customer-related requests for admin

require_once __DIR__ . '/../includes/api_bootstrap.inc.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in as admin.'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

$response = [];

switch ($method) {
    case 'GET':
        if ($id) {
            // Get single customer
            $response = getCustomerById($pdo, $id);
        } else {
            // Get all customers
            $response = getAllCustomers($pdo);
        }
        break;
        
    case 'POST':
        // Create new customer
        $response = createCustomer($pdo);
        break;
        
    case 'PUT':
        // Update customer
        if (!$id) {
            $response = [
                'success' => false,
                'message' => 'Customer ID required for update.',
                'code' => 400
            ];
        } else {
            $response = updateCustomer($pdo, $id);
        }
        break;
        
    case 'DELETE':
        // Delete customer
        if (!$id) {
            $response = [
                'success' => false,
                'message' => 'Customer ID required for deletion.',
                'code' => 400
            ];
        } else {
            $response = deleteCustomer($pdo, $id);
        }
        break;
        
    default:
        $response = [
            'success' => false,
            'message' => 'Method not allowed.',
            'code' => 405
        ];
}

if (isset($response['code'])) {
    http_response_code($response['code']);
    unset($response['code']);
}

echo json_encode($response);

/**
 * Get all customers
 */
function getAllCustomers($pdo) {
    try {
        $sql = "SELECT ID, Full_Name, Email, Phone, Address, createdAt, updatedAt FROM customers ORDER BY createdAt DESC";
        $stmt = $pdo->query($sql);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'customers' => $customers,
            'count' => count($customers)
        ];
    } catch (Exception $e) {
        error_log('Get customers error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching customers: ' . $e->getMessage()
        ];
    }
}

/**
 * Get customer by ID
 */
function getCustomerById($pdo, $id) {
    try {
        $sql = "SELECT ID, Full_Name, Email, Phone, Address, createdAt, updatedAt FROM customers WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Customer not found.',
                'code' => 404
            ];
        }
        
        return [
            'success' => true,
            'customer' => $customer
        ];
    } catch (Exception $e) {
        error_log('Get customer error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching customer: ' . $e->getMessage()
        ];
    }
}

/**
 * Create new customer
 */
function createCustomer($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['Full_Name']) || !isset($input['Email'])) {
            return [
                'success' => false,
                'message' => 'Full name and email are required.',
                'code' => 400
            ];
        }
        
        $sql = "INSERT INTO customers (Full_Name, Email, Password, Phone, Address, createdAt, updatedAt) 
                VALUES (:full_name, :email, :password, :phone, :address, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        
        $hashedPassword = password_hash($input['password'] ?? 'Customer@123', PASSWORD_DEFAULT);
        
        $stmt->bindParam(':full_name', $input['Full_Name']);
        $stmt->bindParam(':email', $input['Email']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':phone', $input['Phone']);
        $stmt->bindParam(':address', $input['Address']);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Customer created successfully',
                'customer_id' => $pdo->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create customer',
                'code' => 500
            ];
        }
    } catch (Exception $e) {
        error_log('Create customer error: ' . $e->getMessage());
        if ($e->getCode() == 23000) {
            return [
                'success' => false,
                'message' => 'Email already exists',
                'code' => 409
            ];
        }
        return [
            'success' => false,
            'message' => 'Error creating customer: ' . $e->getMessage(),
            'code' => 500
        ];
    }
}

/**
 * Update customer
 */
function updateCustomer($pdo, $id) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['Full_Name']) || !isset($input['Email'])) {
            return [
                'success' => false,
                'message' => 'Full name and email are required.',
                'code' => 400
            ];
        }
        
        $sql = "UPDATE customers SET Full_Name = :full_name, Email = :email, Phone = :phone, Address = :address, updatedAt = NOW() 
                WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':full_name', $input['Full_Name']);
        $stmt->bindParam(':email', $input['Email']);
        $stmt->bindParam(':phone', $input['Phone']);
        $stmt->bindParam(':address', $input['Address']);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Customer updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update customer',
                'code' => 500
            ];
        }
    } catch (Exception $e) {
        error_log('Update customer error: ' . $e->getMessage());
        if ($e->getCode() == 23000) {
            return [
                'success' => false,
                'message' => 'Email already exists',
                'code' => 409
            ];
        }
        return [
            'success' => false,
            'message' => 'Error updating customer: ' . $e->getMessage(),
            'code' => 500
        ];
    }
}

/**
 * Delete customer
 */
function deleteCustomer($pdo, $id) {
    try {
        // Check if customer has bookings
        $sql = "SELECT COUNT(*) as booking_count FROM bookings WHERE Customer_ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['booking_count'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete customer with existing bookings',
                'code' => 400
            ];
        }
        
        // Delete customer
        $sql = "DELETE FROM customers WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Customer deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Customer not found',
                'code' => 404
            ];
        }
    } catch (Exception $e) {
        error_log('Delete customer error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error deleting customer: ' . $e->getMessage(),
            'code' => 500
        ];
    }
}
