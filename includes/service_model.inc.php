<?php

// Service Model - Handles all database operations for services

/**
 * Create a new service
 */
function createService($pdo, $data) {
    $sql = "INSERT INTO services (Name, Description, Duration, Category, Is_Available, Image_URL) 
            VALUES (:name, :description, :duration, :category, :is_available, :image_url)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $data['Name'],
        ':description' => $data['Description'] ?? null,
        ':duration' => $data['Duration'],
        ':category' => $data['Category'] ?? null,
        ':is_available' => isset($data['Is_Available']) ? (int)$data['Is_Available'] : 1,
        ':image_url' => $data['Image_URL'] ?? null
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Get all services
 */
function getAllServices($pdo) {
    $sql = "SELECT * FROM services ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get available services (for public display)
 */
function getAvailableServices($pdo) {
    $sql = "SELECT * FROM services WHERE Is_Available = 1 ORDER BY Name ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get services by category
 */
function getServicesByCategory($pdo, $category) {
    $sql = "SELECT * FROM services WHERE Category = :category ORDER BY Name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':category' => $category]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get service by ID
 */
function getServiceById($pdo, $id) {
    $sql = "SELECT * FROM services WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Update service
 */
function updateService($pdo, $id, $data) {
    $fields = [];
    $params = [':id' => $id];
    
    if (isset($data['Name'])) {
        $fields[] = "Name = :name";
        $params[':name'] = $data['Name'];
    }
    if (array_key_exists('Description', $data)) {
        $fields[] = "Description = :description";
        $params[':description'] = $data['Description'];
    }
    if (isset($data['Duration'])) {
        $fields[] = "Duration = :duration";
        $params[':duration'] = $data['Duration'];
    }
    if (array_key_exists('Category', $data)) {
        $fields[] = "Category = :category";
        $params[':category'] = $data['Category'];
    }
    if (isset($data['Is_Available'])) {
        $fields[] = "Is_Available = :is_available";
        $params[':is_available'] = (int)$data['Is_Available'];
    }
    if (array_key_exists('Image_URL', $data)) {
        $fields[] = "Image_URL = :image_url";
        $params[':image_url'] = $data['Image_URL'];
    }
    
    if (empty($fields)) {
        return false;
    }
    
    $sql = "UPDATE services SET " . implode(', ', $fields) . " WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Delete service
 */
function deleteService($pdo, $id) {
    $sql = "DELETE FROM services WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

/**
 * Toggle service availability
 */
function toggleServiceAvailability($pdo, $id, $isAvailable) {
    $sql = "UPDATE services SET Is_Available = :is_available WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':id' => $id,
        ':is_available' => (int)$isAvailable
    ]);
}

/**
 * Search services
 */
function searchServices($pdo, $query) {
    $sql = "SELECT * FROM services WHERE Name LIKE :query OR Description LIKE :query OR Category LIKE :query ORDER BY Name ASC";
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->execute([':query' => $searchTerm]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get service categories
 */
function getServiceCategories($pdo) {
    $sql = "SELECT DISTINCT Category FROM services WHERE Category IS NOT NULL ORDER BY Category ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get services count
 */
function getServicesCount($pdo) {
    $sql = "SELECT COUNT(*) FROM services";
    return $pdo->query($sql)->fetchColumn();
}

/**
 * Get available services count
 */
function getAvailableServicesCount($pdo) {
    $sql = "SELECT COUNT(*) FROM services WHERE Is_Available = 1";
    return $pdo->query($sql)->fetchColumn();
}
