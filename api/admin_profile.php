<?php

// Admin Profile API Endpoint
// Handles admin profile operations

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
$adminId = $_SESSION['admin_id'];

$response = [];

switch ($method) {
    case 'GET':
        // Get current admin profile
        $response = getAdminProfile($pdo, $adminId);
        break;
        
    case 'PUT':
        // Update admin profile
        $response = updateAdminProfile($pdo, $adminId);
        break;
        
    case 'POST':
        // Change password
        if (isset($_GET['action']) && $_GET['action'] === 'change_password') {
            $response = changeAdminPassword($pdo, $adminId);
        } else {
            $response = [
                'success' => false,
                'message' => 'Invalid action',
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

if (isset($response['code'])) {
    http_response_code($response['code']);
    unset($response['code']);
}

echo json_encode($response);

/**
 * Get admin profile
 */
function getAdminProfile($pdo, $adminId) {
    try {
        $sql = "SELECT ID, Full_Name, Email, Phone, Role, createdAt, updatedAt FROM admins WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $adminId]);
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
        error_log('Get admin profile error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching admin profile: ' . $e->getMessage()
        ];
    }
}

/**
 * Update admin profile
 */
function updateAdminProfile($pdo, $adminId) {
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
        $stmt->bindParam(':id', $adminId);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update profile',
                'code' => 500
            ];
        }
    } catch (Exception $e) {
        error_log('Update admin profile error: ' . $e->getMessage());
        if ($e->getCode() == 23000) {
            return [
                'success' => false,
                'message' => 'Email already exists',
                'code' => 409
            ];
        }
        return [
            'success' => false,
            'message' => 'Error updating profile: ' . $e->getMessage(),
            'code' => 500
        ];
    }
}

/**
 * Change admin password
 */
function changeAdminPassword($pdo, $adminId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['current_password']) || !isset($input['new_password'])) {
            return [
                'success' => false,
                'message' => 'Current password and new password are required.',
                'code' => 400
            ];
        }
        
        // Get current password
        $sql = "SELECT Password FROM admins WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            return [
                'success' => false,
                'message' => 'Admin not found',
                'code' => 404
            ];
        }
        
        // Verify current password
        if (!password_verify($input['current_password'], $admin['Password'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect',
                'code' => 400
            ];
        }
        
        // Validate new password
        $newPassword = $input['new_password'];
        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters long',
                'code' => 400
            ];
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateSql = "UPDATE admins SET Password = :password, first_login = 0, updatedAt = NOW() WHERE ID = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindParam(':password', $hashedPassword);
        $updateStmt->bindParam(':id', $adminId);
        
        if ($updateStmt->execute()) {
            // Delete all remember me tokens for this admin (security measure)
            require_once '../includes/login_model.inc.php';
            delete_all_remember_me_tokens($pdo, $adminId, 'admin');
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to change password',
                'code' => 500
            ];
        }
    } catch (Exception $e) {
        error_log('Change admin password error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error changing password: ' . $e->getMessage(),
            'code' => 500
        ];
    }
}
?>
