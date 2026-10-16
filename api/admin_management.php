<?php

// Admin Management API Endpoint
// Handles all admin management requests

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
            // Get single admin
            $response = getAdminById($pdo, $id);
        } else {
            // Get all admins
            $response = getAllAdmins($pdo);
        }
        break;
        
    case 'POST':
        // Create new admin (only main admin can do this)
        $response = createAdmin($pdo);
        break;
        
    case 'PUT':
        // Update admin
        if (!$id) {
            $response = [
                'success' => false,
                'message' => 'Admin ID required for update.',
                'code' => 400
            ];
        } else {
            $response = updateAdmin($pdo, $id);
        }
        break;
        
    case 'DELETE':
        // Delete admin (only main admin can do this)
        if (!$id) {
            $response = [
                'success' => false,
                'message' => 'Admin ID required for deletion.',
                'code' => 400
            ];
        } else {
            $response = deleteAdmin($pdo, $id);
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
 * Get all admins
 */
function getAllAdmins($pdo) {
    try {
        $sql = "SELECT ID, Full_Name, Email, Phone, Role, createdAt, updatedAt FROM admins ORDER BY createdAt DESC";
        $stmt = $pdo->query($sql);
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'admins' => $admins,
            'count' => count($admins)
        ];
    } catch (Exception $e) {
        error_log('Get admins error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching admins: ' . $e->getMessage()
        ];
    }
}

/**
 * Get admin by ID
 */
function getAdminById($pdo, $id) {
    try {
        $sql = "SELECT ID, Full_Name, Email, Phone, Role, createdAt, updatedAt FROM admins WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            return [
                'success' => false,
                'message' => 'Admin not found.',
                'code' => 404
            ];
        }
        
        return [
            'success' => true,
            'admin' => $admin
        ];
    } catch (Exception $e) {
        error_log('Get admin error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching admin: ' . $e->getMessage()
        ];
    }
}

/**
 * Create new admin
 */
function createAdmin($pdo) {
    try {
        // Check if current admin is main_admin
        $currentAdminId = $_SESSION['admin_id'];
        $sql = "SELECT Role FROM admins WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $currentAdminId]);
        $currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentAdmin || $currentAdmin['Role'] !== 'main_admin') {
            return [
                'success' => false,
                'message' => 'Only main admin can create new admins',
                'code' => 403
            ];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Debug: log received data
        error_log('Create admin received data: ' . print_r($input, true));
        
        if (!$input || !isset($input['Full_Name']) || !isset($input['Email']) || !isset($input['Role'])) {
            return [
                'success' => false,
                'message' => 'Full name, email, and role are required. Received: ' . print_r($input, true),
                'code' => 400
            ];
        }
        
        $sql = "INSERT INTO admins (Full_Name, Email, Password, Phone, Role, first_login, created_by, createdAt, updatedAt) 
                VALUES (:full_name, :email, :password, :phone, :role, 1, :created_by, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        
        $tempPassword = $input['Password'] ?? bin2hex(random_bytes(8));
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

        // Verify the hash was created successfully and can verify the password
        if (!$hashedPassword || !password_verify($tempPassword, $hashedPassword)) {
            error_log('Admin creation failed: password_hash failed or verification failed');
            return [
                'success' => false,
                'message' => 'Failed to generate secure password. Please try again.',
                'code' => 500
            ];
        }

        $stmt->bindParam(':full_name', $input['Full_Name']);
        $stmt->bindParam(':email', $input['Email']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':phone', $input['Phone']);
        $stmt->bindParam(':role', $input['Role']);
        $stmt->bindParam(':created_by', $currentAdminId);

        if ($stmt->execute()) {
            $newAdminId = $pdo->lastInsertId();
            error_log("Admin created successfully: ID=$newAdminId, Email=" . $input['Email'] . ", HashLength=" . strlen($hashedPassword));
            return [
                'success' => true,
                'message' => 'Admin created successfully. Temporary password: ' . $tempPassword,
                'admin_id' => $newAdminId,
                'temp_password' => $tempPassword
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create admin',
                'code' => 500
            ];
        }
    } catch (Exception $e) {
        error_log('Create admin error: ' . $e->getMessage());
        if ($e->getCode() == 23000) {
            return [
                'success' => false,
                'message' => 'Email already exists',
                'code' => 409
            ];
        }
        return [
            'success' => false,
            'message' => 'Error creating admin: ' . $e->getMessage(),
            'code' => 500
        ];
    }
}

/**
 * Update admin
 */
function updateAdmin($pdo, $id) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['Full_Name']) || !isset($input['Email'])) {
            return [
                'success' => false,
                'message' => 'Full name and email are required.',
                'code' => 400
            ];
        }
        
        $sql = "UPDATE admins SET Full_Name = :full_name, Email = :email, Phone = :phone, updatedAt = NOW() 
                WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':full_name', $input['Full_Name']);
        $stmt->bindParam(':email', $input['Email']);
        $stmt->bindParam(':phone', $input['Phone']);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Admin updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update admin',
                'code' => 500
            ];
        }
    } catch (Exception $e) {
        error_log('Update admin error: ' . $e->getMessage());
        if ($e->getCode() == 23000) {
            return [
                'success' => false,
                'message' => 'Email already exists',
                'code' => 409
            ];
        }
        return [
            'success' => false,
            'message' => 'Error updating admin: ' . $e->getMessage(),
            'code' => 500
        ];
    }
}

/**
 * Delete admin
 */
function deleteAdmin($pdo, $id) {
    try {
        // Check if current admin is main_admin
        $currentAdminId = $_SESSION['admin_id'];
        $sql = "SELECT Role FROM admins WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $currentAdminId]);
        $currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentAdmin || $currentAdmin['Role'] !== 'main_admin') {
            return [
                'success' => false,
                'message' => 'Only main admin can delete admins',
                'code' => 403
            ];
        }
        
        // Prevent self-deletion
        if ($id == $currentAdminId) {
            return [
                'success' => false,
                'message' => 'Cannot delete your own account',
                'code' => 400
            ];
        }
        
        // Check if admin is main_admin (there should always be at least one main admin)
        $sql = "SELECT Role FROM admins WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $targetAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($targetAdmin['Role'] === 'main_admin') {
            // Check if this is the last main admin
            $countSql = "SELECT COUNT(*) as main_admin_count FROM admins WHERE Role = 'main_admin'";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute();
            $count = $countStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($count['main_admin_count'] <= 1) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete the last main admin',
                    'code' => 400
                ];
            }
        }
        
        // Delete admin
        $sql = "DELETE FROM admins WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Admin deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Admin not found',
                'code' => 404
            ];
        }
    } catch (Exception $e) {
        error_log('Delete admin error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error deleting admin: ' . $e->getMessage(),
            'code' => 500
        ];
    }
}
?>
