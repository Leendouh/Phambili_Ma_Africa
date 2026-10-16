<?php

// allow code to have type decoration
declare(strict_types=1); 

// function that query the database using the email provided by the user to see if the user exist in the database
function get_user(object $pdo, string $email) 
{
   // query to check whether the email exist in the customers table
   $query = "SELECT * FROM customers WHERE Email = :Email;";

   // prepared statement 
   $stmt = $pdo -> prepare($query);

   // binding user data to the actual name parameters
   $stmt -> bindParam(":Email", $email);

   // execute the statement
   $stmt -> execute();

   // check if we did grab the wrong data
   $result = $stmt -> fetch (PDO::FETCH_ASSOC);
   
   // return a false statement 
   return $result;
}

// function that query the admins table
function get_admin_by_email(object $pdo, string $email)
{
   // query to check whether the email exist in the admins table
   $query = "SELECT * FROM admins WHERE Email = :Email;";

   // prepared statement
   $stmt = $pdo->prepare($query);

   // binding user data to the actual name parameters
   $stmt->bindParam(":Email", $email);

   // execute the statement
   $stmt->execute();

   // check if we did grab the wrong data
   $result = $stmt->fetch(PDO::FETCH_ASSOC);

   // return a false statement
   return $result;
}

// unified function to get user from either table
function get_user_unified(object $pdo, string $email)
{
    // First check customers table
    $customer = get_user($pdo, $email);
    if ($customer) {
        $customer['user_type'] = 'customer';
        return $customer;
    }

    // Then check admins table
    $admin = get_admin_by_email($pdo, $email);
    if ($admin) {
        $admin['user_type'] = 'admin';
        return $admin;
    }

    // Return false if not found in either table
    return false;
}

// function to get user by ID from either table (for remember me functionality)
function get_user_by_id(object $pdo, int $userId)
{
    // First check customers table
    $query = "SELECT * FROM customers WHERE ID = :ID;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":ID", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        $customer['user_type'] = 'customer';
        return $customer;
    }

    // Then check admins table
    $query = "SELECT * FROM admins WHERE ID = :ID;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":ID", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        $admin['user_type'] = 'admin';
        return $admin;
    }

    // Return false if not found in either table
    return false;
}

// ========== ADMIN SPECIFIC FUNCTIONS ==========

// Update admin password and set first_login to false
function update_admin_password(object $pdo, int $adminId, string $newPassword)
{
    $query = "UPDATE admins SET Password = :Password, first_login = 0, updatedAt = NOW() WHERE ID = :Id;";
    $stmt = $pdo->prepare($query);
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt->bindParam(":Password", $hashedPassword);
    $stmt->bindParam(":Id", $adminId, PDO::PARAM_INT);
    $stmt->execute();

    // Delete all remember me tokens for this admin (security measure)
    delete_all_remember_me_tokens($pdo, $adminId, 'admin');
}

// Update admin profile (Full Name and Phone)
function update_admin_profile(object $pdo, int $adminId, string $fullName, string $phone)
{
    $query = "UPDATE admins SET Full_Name = :FullName, Phone = :Phone, updatedAt = NOW() WHERE ID = :Id;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":FullName", $fullName);
    $stmt->bindParam(":Phone", $phone);
    $stmt->bindParam(":Id", $adminId, PDO::PARAM_INT);
    $stmt->execute();
}

// Create new admin (for adding sub-admins by main admin)
function create_admin(object $pdo, string $email, string $password, string $role, int $createdBy)
{
    $query = "INSERT INTO admins (Email, Password, Role, first_login, created_by, createdAt, updatedAt) VALUES (:Email, :Password, :Role, 1, :CreatedBy, NOW(), NOW());";
    $stmt = $pdo->prepare($query);
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt->bindParam(":Email", $email);
    $stmt->bindParam(":Password", $hashedPassword);
    $stmt->bindParam(":Role", $role);
    $stmt->bindParam(":CreatedBy", $createdBy, PDO::PARAM_INT);
    $stmt->execute();
    return $pdo->lastInsertId();
}

// ========== REMEMBER ME TOKEN FUNCTIONS ==========

// Generate a random selector (public identifier)
function generate_selector(): string
{
    return bin2hex(random_bytes(16)); // 32 character hex string
}

// Generate a random validator (secret token)
function generate_validator(): string
{
    return bin2hex(random_bytes(32)); // 64 character hex string
}

// Hash the validator for database storage
function hash_validator(string $validator): string
{
    return hash('sha256', $validator);
}

// Store remember me token in database
function create_remember_me_token(object $pdo, int $userId, string $userType, string $selector, string $hashedValidator, int $expiresInDays = 30): bool
{
    $query = "INSERT INTO remember_me_tokens (user_id, user_type, selector, validator, expires_at) 
              VALUES (:user_id, :user_type, :selector, :validator, DATE_ADD(NOW(), INTERVAL :days DAY))";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":user_type", $userType);
    $stmt->bindParam(":selector", $selector);
    $stmt->bindParam(":validator", $hashedValidator);
    $stmt->bindParam(":days", $expiresInDays, PDO::PARAM_INT);
    return $stmt->execute();
}

// Get remember me token by selector
function get_remember_me_token(object $pdo, string $selector): ?array
{
    $query = "SELECT * FROM remember_me_tokens WHERE selector = :selector AND expires_at > NOW() LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":selector", $selector);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

// Update remember me token (rotate validator)
function rotate_remember_me_token(object $pdo, string $selector, string $newHashedValidator, int $expiresInDays = 30): bool
{
    $query = "UPDATE remember_me_tokens 
              SET validator = :validator, 
                  expires_at = DATE_ADD(NOW(), INTERVAL :days DAY),
                  last_used_at = NOW() 
              WHERE selector = :selector";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":validator", $newHashedValidator);
    $stmt->bindParam(":days", $expiresInDays, PDO::PARAM_INT);
    $stmt->bindParam(":selector", $selector);
    return $stmt->execute();
}

// Delete remember me token by selector
function delete_remember_me_token(object $pdo, string $selector): bool
{
    $query = "DELETE FROM remember_me_tokens WHERE selector = :selector";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":selector", $selector);
    return $stmt->execute();
}

// Delete all remember me tokens for a user (e.g., on password change or logout from all devices)
function delete_all_remember_me_tokens(object $pdo, int $userId, string $userType): bool
{
    $query = "DELETE FROM remember_me_tokens WHERE user_id = :user_id AND user_type = :user_type";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":user_type", $userType);
    return $stmt->execute();
}

// Clean up expired tokens (can be run periodically)
function cleanup_expired_tokens(object $pdo): int
{
    $query = "DELETE FROM remember_me_tokens WHERE expires_at < NOW()";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->rowCount();
}