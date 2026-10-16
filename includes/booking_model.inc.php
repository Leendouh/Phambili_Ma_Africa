<?php

// Booking Model - Handles all database operations for bookings

/**
 * Create a new booking
 */
function createBooking($pdo, $data) {
    $sql = "INSERT INTO bookings 
            (Customer_ID, Service_ID, Date, Time, Address, Special_Instructions, 
             Duration, Status, Property_Type, Property_Size, Cleaning_Frequency) 
            VALUES 
            (:customer_id, :service_id, :date, :time, :address, :special_instructions,
             :duration, :status, :property_type, :property_size, :cleaning_frequency)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':customer_id' => $data['Customer_ID'],
        ':service_id' => $data['Service_ID'],
        ':date' => $data['Date'],
        ':time' => $data['Time'] ?? '09:00:00',
        ':address' => $data['Address'] ?? null,
        ':special_instructions' => $data['Special_Instructions'] ?? null,
        ':duration' => $data['Duration'] ?? null,
        ':status' => $data['Status'] ?? 'requested',
        ':property_type' => $data['Property_Type'] ?? null,
        ':property_size' => $data['Property_Size'] ?? null,
        ':cleaning_frequency' => $data['Cleaning_Frequency'] ?? null
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Get all bookings with optional filters
 */
function getAllBookings($pdo, $filters = []) {
    $where = [];
    $params = [];
    
    if (!empty($filters['status'])) {
        $where[] = "b.Status = :status";
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['customer_id'])) {
        $where[] = "b.Customer_ID = :customer_id";
        $params[':customer_id'] = $filters['customer_id'];
    }
    if (!empty($filters['service_id'])) {
        $where[] = "b.Service_ID = :service_id";
        $params[':service_id'] = $filters['service_id'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = "b.Date >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = "b.Date <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }
    if (!empty($filters['search'])) {
        $where[] = "(c.Full_Name LIKE :search OR c.Email LIKE :search OR c.Phone LIKE :search OR s.Name LIKE :search OR b.Property_Type LIKE :search OR b.Address LIKE :search OR b.ID LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $sql = "SELECT b.*, 
            c.Full_Name as Customer_Name, c.Email as Customer_Email, c.Phone as Customer_Phone,
            s.Name as Service_Name, s.Description as Service_Description, s.Duration as Service_Duration
            FROM bookings b
            LEFT JOIN customers c ON b.Customer_ID = c.ID
            LEFT JOIN services s ON b.Service_ID = s.ID
            $whereClause
            ORDER BY b.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get booking by ID with customer and service details
 */
function getBookingById($pdo, $id) {
    $sql = "SELECT b.*, 
            c.Full_Name as Customer_Name, c.Email as Customer_Email, c.Phone as Customer_Phone, c.Address as Customer_Address,
            s.Name as Service_Name, s.Description as Service_Description, s.Duration as Service_Duration, s.Category as Service_Category
            FROM bookings b
            LEFT JOIN customers c ON b.Customer_ID = c.ID
            LEFT JOIN services s ON b.Service_ID = s.ID
            WHERE b.ID = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get bookings by customer ID
 */
function getBookingsByCustomerId($pdo, $customerId, $page = 1, $limit = 10) {
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT b.*, 
            s.Name as Service_Name, s.Description as Service_Description, s.Duration as Service_Duration, s.Image_URL as Service_Image
            FROM bookings b
            LEFT JOIN services s ON b.Service_ID = s.ID
            WHERE b.Customer_ID = :customer_id
            ORDER BY b.Date DESC, b.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count total bookings by customer
 */
function countBookingsByCustomer($pdo, $customerId) {
    $sql = "SELECT COUNT(*) FROM bookings WHERE Customer_ID = :customer_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':customer_id' => $customerId]);
    return $stmt->fetchColumn();
}

/**
 * Update booking
 */
function updateBooking($pdo, $id, $data) {
    $fields = [];
    $params = [':id' => $id];
    
    $allowedFields = [
        'Date', 'Time', 'Status', 'Address', 'Special_Instructions', 'Quoted_Amount',
        'Duration', 'Property_Type', 'Property_Size', 'Cleaning_Frequency',
        'contact_date', 'consultation_date', 'consultation_type', 'consultation_notes',
        'quote_breakdown', 'quote_validity_days', 'quote_notes', 'admin_notes',
        'call_notes', 'next_steps', 'completed_date', 'cancelled_date'
    ];
    
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = :" . strtolower($field);
            $params[':' . strtolower($field)] = $data[$field];
        }
    }
    
    // Always update the updated_at timestamp
    $fields[] = "updated_at = NOW()";
    
    if (empty($fields)) {
        return false;
    }
    
    $sql = "UPDATE bookings SET " . implode(', ', $fields) . " WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Update booking status only
 */
function updateBookingStatus($pdo, $id, $status, $additionalData = []) {
    $sql = "UPDATE bookings SET Status = :status, updated_at = NOW() WHERE ID = :id";
    $params = [':id' => $id, ':status' => $status];
    
    // Add timestamps based on status
    switch ($status) {
        case 'contacted':
            $sql = "UPDATE bookings SET Status = :status, contact_date = NOW(), updated_at = NOW() WHERE ID = :id";
            break;
        case 'completed':
            $sql = "UPDATE bookings SET Status = :status, completed_date = NOW(), updated_at = NOW() WHERE ID = :id";
            break;
        case 'cancelled':
            $sql = "UPDATE bookings SET Status = :status, cancelled_date = NOW(), updated_at = NOW() WHERE ID = :id";
            break;
    }
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Delete booking
 */
function deleteBooking($pdo, $id) {
    $sql = "DELETE FROM bookings WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

/**
 * Check for duplicate booking
 */
function checkDuplicateBooking($pdo, $customerId, $serviceId, $date, $excludeId = null) {
    $sql = "SELECT ID FROM bookings 
            WHERE Customer_ID = :customer_id 
            AND Service_ID = :service_id 
            AND Date = :date 
            AND Status NOT IN ('cancelled')";
    
    $params = [
        ':customer_id' => $customerId,
        ':service_id' => $serviceId,
        ':date' => $date
    ];
    
    if ($excludeId) {
        $sql .= " AND ID != :exclude_id";
        $params[':exclude_id'] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get booking statistics
 */
function getBookingStats($pdo) {
    $today = date('Y-m-d');
    $oneWeekAgo = date('Y-m-d', strtotime('-7 days'));
    
    $sql = "SELECT 
            (SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = :today) as today_requests,
            (SELECT COUNT(*) FROM bookings WHERE Status = 'requested') as pending_contact,
            (SELECT COUNT(*) FROM bookings WHERE Status = 'contacted' AND Quoted_Amount IS NULL) as pending_quotation,
            (SELECT COUNT(*) FROM bookings WHERE Status = 'confirmed' AND Date >= :today) as upcoming_bookings,
            (SELECT COUNT(*) FROM bookings WHERE Status = 'completed' AND Date >= :week_ago) as total_completed";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':today' => $today, ':week_ago' => $oneWeekAgo]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get bookings by status
 */
function getBookingsByStatus($pdo, $status, $limit = null) {
    $sql = "SELECT b.*, 
            c.Full_Name as Customer_Name, c.Email as Customer_Email, c.Phone as Customer_Phone,
            s.Name as Service_Name, s.Duration as Service_Duration
            FROM bookings b
            LEFT JOIN customers c ON b.Customer_ID = c.ID
            LEFT JOIN services s ON b.Service_ID = s.ID
            WHERE b.Status = :status
            ORDER BY b.Date ASC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':status', $status);
    if ($limit) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get booking analytics
 */
function getBookingAnalytics($pdo, $period = 'monthly') {
    $dateFormat = match($period) {
        'daily' => '%Y-%m-%d',
        'weekly' => '%Y-%u',
        default => '%Y-%m'
    };
    
    $sql = "SELECT 
            DATE_FORMAT(Date, :format) as period,
            COUNT(*) as bookings,
            SUM(CASE WHEN Status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN Status = 'cancelled' THEN 0 ELSE Quoted_Amount END) as total_value
            FROM bookings
            WHERE Status NOT IN ('cancelled')
            GROUP BY period
            ORDER BY period ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':format' => $dateFormat]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Log status change
 */
function logStatusChange($pdo, $bookingId, $oldStatus, $newStatus, $changedBy = null, $notes = null) {
    $sql = "INSERT INTO booking_status_history 
            (Booking_ID, Old_Status, New_Status, Changed_By, Notes) 
            VALUES (:booking_id, :old_status, :new_status, :changed_by, :notes)";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':booking_id' => $bookingId,
        ':old_status' => $oldStatus,
        ':new_status' => $newStatus,
        ':changed_by' => $changedBy,
        ':notes' => $notes
    ]);
}

/**
 * Get status history for a booking
 */
function getBookingStatusHistory($pdo, $bookingId) {
    $sql = "SELECT * FROM booking_status_history 
            WHERE Booking_ID = :booking_id 
            ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':booking_id' => $bookingId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if date is available for booking
 */
function isDateAvailable($pdo, $date, $time = null) {
    // Check if it's not a past date
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        return ['available' => false, 'reason' => 'Cannot book for past dates'];
    }
    
    // Check same-day rules (must be before 12 PM)
    if ($date === date('Y-m-d') && $time) {
        $hour = (int)substr($time, 0, 2);
        if ($hour >= 12) {
            return ['available' => false, 'reason' => 'Same-day bookings must be made before 12:00 PM'];
        }
    }
    
    return ['available' => true, 'reason' => null];
}
