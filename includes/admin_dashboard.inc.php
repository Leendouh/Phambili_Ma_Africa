<?php
// Admin Dashboard PHP Functions
// Converted from admin-dashboard.js to server-side PHP

// ========== CUSTOMER MANAGEMENT FUNCTIONS ==========
function getCustomerById($pdo, $customerId) {
    try {
        $sql = "SELECT ID, Full_Name, Email, Phone, Address, createdAt FROM customers WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

function createCustomer($pdo, $data) {
    try {
        $sql = "INSERT INTO customers (Full_Name, Email, Password, Phone, Address, createdAt, updatedAt) 
                VALUES (:full_name, :email, :password, :phone, :address, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        
        $hashedPassword = password_hash($data['Password'] ?? 'Customer@123', PASSWORD_DEFAULT);
        
        $stmt->execute([
            ':full_name' => $data['Full_Name'],
            ':email' => $data['Email'],
            ':password' => $hashedPassword,
            ':phone' => $data['Phone'] ?? null,
            ':address' => $data['Address'] ?? null
        ]);
        
        return ['success' => true, 'message' => 'Customer created successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getCode() == 23000 ? 'Email already exists' : 'Error creating customer'];
    }
}

function updateCustomer($pdo, $customerId, $data) {
    try {
        $sql = "UPDATE customers SET Full_Name = :full_name, Email = :email, Phone = :phone, 
                Address = :address, updatedAt = NOW() WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $data['Full_Name'],
            ':email' => $data['Email'],
            ':phone' => $data['Phone'] ?? null,
            ':address' => $data['Address'] ?? null,
            ':id' => $customerId
        ]);
        return ['success' => true, 'message' => 'Customer updated successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error updating customer'];
    }
}

function deleteCustomer($pdo, $customerId) {
    try {
        $checkSql = "SELECT COUNT(*) as booking_count FROM bookings WHERE Customer_ID = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':id' => $customerId]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['booking_count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete customer with existing bookings'];
        }
        
        $sql = "DELETE FROM customers WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $customerId]);
        
        return ['success' => true, 'message' => 'Customer deleted successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error deleting customer'];
    }
}

// ========== ADMIN MANAGEMENT FUNCTIONS ==========
function getAdminById($pdo, $adminId) {
    try {
        $sql = "SELECT ID, Full_Name, Email, Phone, Role, createdAt FROM admins WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $adminId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

function createAdmin($pdo, $data, $currentAdminId) {
    try {
        $checkSql = "SELECT Role FROM admins WHERE ID = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':id' => $currentAdminId]);
        $currentAdmin = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentAdmin || $currentAdmin['Role'] !== 'main_admin') {
            return ['success' => false, 'message' => 'Only main admin can create new admins'];
        }
        
        $sql = "INSERT INTO admins (Full_Name, Email, Password, Phone, Role, first_login, created_by, createdAt, updatedAt) 
                VALUES (:full_name, :email, :password, :phone, :role, 1, :created_by, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        
        $tempPassword = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        $stmt->execute([
            ':full_name' => $data['Full_Name'],
            ':email' => $data['Email'],
            ':password' => $hashedPassword,
            ':phone' => $data['Phone'] ?? null,
            ':role' => $data['Role'],
            ':created_by' => $currentAdminId
        ]);
        
        return ['success' => true, 'message' => 'Admin created successfully', 'temp_password' => $tempPassword];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getCode() == 23000 ? 'Email already exists' : 'Error creating admin'];
    }
}

function updateAdmin($pdo, $adminId, $data) {
    try {
        $sql = "UPDATE admins SET Full_Name = :full_name, Email = :email, Phone = :phone, updatedAt = NOW() WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $data['Full_Name'],
            ':email' => $data['Email'],
            ':phone' => $data['Phone'] ?? null,
            ':id' => $adminId
        ]);
        return ['success' => true, 'message' => 'Admin updated successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error updating admin'];
    }
}

function deleteAdmin($pdo, $adminId, $currentAdminId) {
    try {
        if ($adminId == $currentAdminId) {
            return ['success' => false, 'message' => 'Cannot delete your own account'];
        }
        
        $sql = "DELETE FROM admins WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $adminId]);
        
        return ['success' => true, 'message' => 'Admin deleted successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error deleting admin'];
    }
}

// ========== BOOKING FUNCTIONS ==========
function provideQuote($pdo, $bookingId, $quoteData) {
    try {
        $sql = "UPDATE bookings SET Status = 'quoted', Quoted_Amount = :amount,
                Quote_Breakdown = :breakdown, Quote_Valid_Until = DATE_ADD(NOW(), INTERVAL :validity DAY),
                Notes = :notes, updatedAt = NOW() WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':amount' => $quoteData['quoted_amount'],
            ':breakdown' => $quoteData['quote_breakdown'] ?? null,
            ':validity' => $quoteData['quote_validity_days'] ?? 7,
            ':notes' => $quoteData['quote_notes'] ?? null,
            ':id' => $bookingId
        ]);

        // Notify customer about quote
        require_once 'notifications_model.inc.php';
        $booking_query = "SELECT Customer_ID FROM bookings WHERE ID = ?";
        $booking_stmt = $pdo->prepare($booking_query);
        $booking_stmt->execute([$bookingId]);
        $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            create_notification(
                $pdo,
                $booking['Customer_ID'],
                'customer',
                'Quote Received',
                "Your booking #{$bookingId} has been quoted. Please review and approve or decline the quote.",
                'quote'
            );
        }

        return ['success' => true, 'message' => 'Quote provided successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error providing quote'];
    }
}

// ========== UTILITY FUNCTIONS ==========
function generateRandomPassword($length = 12) {
    $charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    $password = "";
    for ($i = 0; $i < $length; $i++) {
        $password .= $charset[random_int(0, strlen($charset) - 1)];
    }
    return $password;
}

function setToastMessage($message, $type = 'success') {
    $_SESSION['toast_message'] = $message;
    $_SESSION['toast_type'] = $type;
}

function getToastMessage() {
    if (isset($_SESSION['toast_message'])) {
        $message = ['message' => $_SESSION['toast_message'], 'type' => $_SESSION['toast_type'] ?? 'success'];
        unset($_SESSION['toast_message'], $_SESSION['toast_type']);
        return $message;
    }
    return null;
}

function validateRSAPhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatDate($dateString, $format = 'M d, Y') {
    return empty($dateString) ? 'N/A' : date($format, strtotime($dateString));
}

function getStatusColorClass($status) {
    $colors = ['requested' => 'warning', 'contacted' => 'info', 'in_progress' => 'primary', 'quoted' => 'secondary', 'confirmed' => 'success', 'completed' => 'dark', 'cancelled' => 'danger'];
    return $colors[$status] ?? 'secondary';
}

function getStatusLabel($status) {
    $labels = ['requested' => 'Requested', 'contacted' => 'Contacted', 'in_progress' => 'In Progress', 'quoted' => 'Quoted', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    return $labels[$status] ?? ucfirst($status);
}

function redirectWithMessage($url, $message = null, $type = 'success') {
    if ($message) setToastMessage($message, $type);
    header("Location: $url");
    exit();
}

function hasPermission($currentAdminRole, $requiredRole = 'admin') {
    if ($requiredRole === 'main_admin') return $currentAdminRole === 'main_admin';
    return in_array($currentAdminRole, ['admin', 'main_admin']);
}

function getAdminRoleDisplay($role) {
    $roles = ['main_admin' => 'Main Admin', 'sub_admin' => 'Sub-Admin', 'admin' => 'Admin'];
    return $roles[$role] ?? ucfirst(str_replace('_', ' ', $role));
}

function getAdminRoleBadgeClass($role) {
    return $role === 'main_admin' ? 'badge-primary' : 'badge-secondary';
}

function formatCurrency($amount, $currency = 'R') {
    return ($amount === null || $amount === '') ? 'N/A' : $currency . ' ' . number_format((float)$amount, 2);
}

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials ?: 'U';
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function isPostRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isGetRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

function getInputData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    return $_POST;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function errorResponse($message, $code = 400) {
    jsonResponse(['success' => false, 'message' => $message, 'code' => $code], $code);
}

function successResponse($data = null, $message = 'Success') {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    jsonResponse($response);
}

function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}

function getCurrentDate() {
    return date('Y-m-d');
}

function addDays($date, $days) {
    return date('Y-m-d', strtotime($date . ' + ' . $days . ' days'));
}

function daysDifference($date1, $date2) {
    $d1 = new DateTime($date1);
    $d2 = new DateTime($date2);
    return $d1->diff($d2)->days;
}

function isWeekend($date) {
    return date('N', strtotime($date)) >= 6;
}

function isWeekday($date) {
    return !isWeekend($date);
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    elseif ($diff < 3600) return floor($diff / 60) . ' mins ago';
    elseif ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    elseif ($diff < 604800) return floor($diff / 86400) . ' days ago';
    elseif ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
    elseif ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    else return floor($diff / 31536000) . ' years ago';
}

function truncateText($text, $length = 50) {
    return strlen($text) <= $length ? $text : substr($text, 0, $length) . '...';
}

function calculateAge($birthdate) {
    return (new DateTime())->diff(new DateTime($birthdate))->y;
}

function isBirthdayToday($birthdate) {
    return date('m-d', strtotime($birthdate)) === date('m-d');
}

function formatNumber($num) {
    if ($num >= 1000000000) return round($num / 1000000000, 1) . 'B';
    if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
    if ($num >= 1000) return round($num / 1000, 1) . 'K';
    return $num;
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unitIndex = 0;
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}

function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function generateRandomString($length = 16) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $result = '';
    for ($i = 0; $i < $length; $i++) $result .= $chars[random_int(0, strlen($chars) - 1)];
    return $result;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text) ?: 'n-a';
}

function getHolidays($year) {
    return [
        "$year-01-01" => 'New Year\'s Day', "$year-03-21" => 'Human Rights Day',
        "$year-04-27" => 'Freedom Day', "$year-05-01" => 'Workers\' Day',
        "$year-06-16" => 'Youth Day', "$year-08-09" => 'National Women\'s Day',
        "$year-09-24" => 'Heritage Day', "$year-12-16" => 'Day of Reconciliation',
        "$year-12-25" => 'Christmas Day', "$year-12-26" => 'Day of Goodwill'
    ];
}

function isHoliday($date, $holidays = []) {
    if (empty($holidays)) $holidays = getHolidays(date('Y', strtotime($date)));
    return isset($holidays[date('Y-m-d', strtotime($date))]);
}

function getGreeting($name = null) {
    $hour = date('G');
    $greeting = ($hour < 6) ? 'Good night' : (($hour < 12) ? 'Good morning' : (($hour < 18) ? 'Good afternoon' : 'Good evening'));
    return $name ? "$greeting, $name" : $greeting;
}

function getDashboardStats($pdo) {
    try {
        $stats = [];
        $stats['total_bookings'] = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
        $stats['pending_bookings'] = $pdo->query("SELECT COUNT(*) FROM bookings WHERE Status IN ('requested', 'contacted')")->fetchColumn();
        $stats['total_customers'] = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $stats['total_services'] = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
        $stats['total_products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $stats['todays_bookings'] = $pdo->query("SELECT COUNT(*) FROM bookings WHERE Date = CURDATE()")->fetchColumn();
        return $stats;
    } catch (Exception $e) {
        return [];
    }
}

function getRecentBookings($pdo, $limit = 5) {
    try {
        $sql = "SELECT b.*, s.Name as Service_Name, c.Full_Name as Customer_Name 
                FROM bookings b LEFT JOIN services s ON b.Service_ID = s.ID 
                LEFT JOIN customers c ON b.Customer_ID = c.ID 
                ORDER BY b.createdAt DESC LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function searchCustomers($pdo, $searchTerm) {
    try {
        $sql = "SELECT ID, Full_Name, Email, Phone, Address, createdAt FROM customers 
                WHERE Full_Name LIKE :search OR Email LIKE :search OR Phone LIKE :search ORDER BY createdAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':search' => "%$searchTerm%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getClientIP() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            return trim($ips[0]);
        }
    }
    return '0.0.0.0';
}

function checkPasswordStrength($password) {
    $score = 0;
    if (strlen($password) >= 8) $score++;
    if (preg_match('/[A-Z]/', $password)) $score++;
    if (preg_match('/[a-z]/', $password)) $score++;
    if (preg_match('/[0-9]/', $password)) $score++;
    if (preg_match('/[!@#$%^&*]/', $password)) $score++;
    
    $strength = ($score <= 2) ? 'weak' : (($score <= 4) ? 'medium' : 'strong');
    return ['strength' => $strength, 'score' => $score, 'isStrong' => $score === 5];
}

function calculatePercentageChange($oldValue, $newValue) {
    if ($oldValue == 0) return $newValue > 0 ? 100 : 0;
    return (($newValue - $oldValue) / $oldValue) * 100;
}

function calculateAverage($values) {
    return empty($values) ? 0 : array_sum($values) / count($values);
}

function calculateMedian($values) {
    if (empty($values)) return 0;
    sort($values);
    $count = count($values);
    $middle = floor($count / 2);
    return ($count % 2) ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
}

function movingAverage($values, $period) {
    $result = [];
    for ($i = $period - 1; $i < count($values); $i++) {
        $result[] = array_sum(array_slice($values, $i - $period + 1, $period)) / $period;
    }
    return $result;
}

function exponentialMovingAverage($values, $period) {
    $result = [$values[0]];
    $multiplier = 2 / ($period + 1);
    $ema = $values[0];
    for ($i = 1; $i < count($values); $i++) {
        $ema = ($values[$i] * $multiplier) + ($ema * (1 - $multiplier));
        $result[] = $ema;
    }
    return $result;
}

function getMonthName($month) {
    $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
    return $months[(int)$month] ?? '';
}

function getDayName($day) {
    $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    return $days[(int)$day] ?? '';
}

function convertTo12Hour($time) {
    return date('g:i A', strtotime($time));
}

function formatDuration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($hours > 0) return $hours . ' hour' . ($hours > 1 ? 's' : '') . ($mins > 0 ? ' ' . $mins . ' min' : '');
    return $mins . ' minute' . ($mins > 1 ? 's' : '');
}

function getDaysInMonth($month, $year) {
    return cal_days_in_month(CAL_GREGORIAN, $month, $year);
}
