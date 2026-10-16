<?php

// Service Controller - Handles business logic for services
// This converts the Node.js controller to PHP

require_once 'service_model.inc.php';

/**
 * Create a new service with validation and image handling
 */
function createServiceController($pdo, $data, $files = null) {
    // Validation
    if (empty($data['Name']) || empty($data['Duration'])) {
        return [
            'success' => false,
            'message' => 'Name and Duration are required.'
        ];
    }
    
    $imageUrl = null;
    $imageUploadWarning = null;
    
    // Handle image upload
    if ($files && !empty($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadServiceImage($files['image']);
        if ($uploadResult['success']) {
            $imageUrl = $uploadResult['url'];
        } else {
            $imageUploadWarning = 'Service created but image upload failed. You can add an image later.';
            error_log('Image upload failed: ' . $uploadResult['message']);
        }
    }
    
    try {
        $serviceData = [
            'Name' => trim($data['Name']),
            'Description' => !empty($data['Description']) ? trim($data['Description']) : null,
            'Duration' => (int)$data['Duration'],
            'Category' => !empty($data['Category']) ? trim($data['Category']) : null,
            'Is_Available' => isset($data['Is_Available']) ? (bool)$data['Is_Available'] : true,
            'Image_URL' => $imageUrl
        ];
        
        $serviceId = createService($pdo, $serviceData);
        $service = getServiceById($pdo, $serviceId);
        
        return [
            'success' => true,
            'message' => $imageUploadWarning ?: 'Service created successfully',
            'service' => $service,
            'warning' => $imageUploadWarning
        ];
        
    } catch (Exception $e) {
        error_log('Create service error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error creating service: ' . $e->getMessage()
        ];
    }
}

/**
 * Get all services for admin
 */
function getServicesController($pdo) {
    try {
        $services = getAllServices($pdo);
        
        return [
            'success' => true,
            'services' => $services
        ];
    } catch (Exception $e) {
        error_log('Get services error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching services: ' . $e->getMessage()
        ];
    }
}

/**
 * Get public services (all available services for customers)
 */
function getPublicServicesController($pdo) {
    try {
        error_log('Fetching ALL public services...');
        
        $services = getAllServices($pdo);
        
        // Sort by name
        usort($services, function($a, $b) {
            return strcmp($a['Name'] ?? '', $b['Name'] ?? '');
        });
        
        error_log('Found ' . count($services) . ' services total');
        
        // Format for API response
        $formattedServices = array_map(function($service) {
            return [
                'ID' => (int)$service['ID'],
                'Name' => $service['Name'],
                'Description' => $service['Description'],
                'Duration' => (int)$service['Duration'],
                'Category' => $service['Category'],
                'Is_Available' => (bool)$service['Is_Available'],
                'Image_URL' => $service['Image_URL'],
                'Created_At' => $service['created_at']
            ];
        }, $services);
        
        return [
            'success' => true,
            'services' => $formattedServices
        ];
        
    } catch (Exception $e) {
        error_log('Error fetching public services: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching services'
        ];
    }
}

/**
 * Get service by ID
 */
function getServiceByIdController($pdo, $id) {
    try {
        $service = getServiceById($pdo, $id);
        
        if (!$service) {
            return [
                'success' => false,
                'message' => 'Service not found.',
                'code' => 404
            ];
        }
        
        return [
            'success' => true,
            'service' => $service
        ];
        
    } catch (Exception $e) {
        error_log('Get service error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching service: ' . $e->getMessage()
        ];
    }
}

/**
 * Update service with image handling
 */
function updateServiceController($pdo, $id, $data, $files = null) {
    try {
        $service = getServiceById($pdo, $id);
        
        if (!$service) {
            return [
                'success' => false,
                'message' => 'Service not found.',
                'code' => 404
            ];
        }
        
        $imageUrl = $service['Image_URL'];
        
        // Handle image upload
        if ($files && !empty($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            // Delete old image
            if ($service['Image_URL']) {
                deleteServiceImage($service['Image_URL']);
            }
            
            $uploadResult = uploadServiceImage($files['image']);
            if ($uploadResult['success']) {
                $imageUrl = $uploadResult['url'];
            } else {
                error_log('Image upload failed during update: ' . $uploadResult['message']);
                // Keep old image if upload fails
            }
        }
        
        $updateData = [];
        if (isset($data['Name'])) $updateData['Name'] = trim($data['Name']);
        if (array_key_exists('Description', $data)) $updateData['Description'] = trim($data['Description']) ?: null;
        if (isset($data['Duration'])) $updateData['Duration'] = (int)$data['Duration'];
        if (array_key_exists('Category', $data)) $updateData['Category'] = trim($data['Category']) ?: null;
        if (isset($data['Is_Available'])) $updateData['Is_Available'] = (bool)$data['Is_Available'];
        if ($imageUrl !== $service['Image_URL']) $updateData['Image_URL'] = $imageUrl;
        
        updateService($pdo, $id, $updateData);
        $updatedService = getServiceById($pdo, $id);
        
        return [
            'success' => true,
            'message' => 'Service updated successfully',
            'service' => $updatedService
        ];
        
    } catch (Exception $e) {
        error_log('Update service error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating service: ' . $e->getMessage()
        ];
    }
}

/**
 * Delete service
 */
function deleteServiceController($pdo, $id) {
    try {
        $service = getServiceById($pdo, $id);
        
        if (!$service) {
            return [
                'success' => false,
                'message' => 'Service not found.',
                'code' => 404
            ];
        }
        
        // Delete associated image
        if ($service['Image_URL']) {
            deleteServiceImage($service['Image_URL']);
        }
        
        deleteService($pdo, $id);
        
        return [
            'success' => true,
            'message' => 'Service deleted successfully'
        ];
        
    } catch (Exception $e) {
        error_log('Delete service error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error deleting service: ' . $e->getMessage()
        ];
    }
}

/**
 * Toggle service availability
 */
function toggleServiceAvailabilityController($pdo, $id, $isAvailable) {
    try {
        $service = getServiceById($pdo, $id);
        
        if (!$service) {
            return [
                'success' => false,
                'message' => 'Service not found.',
                'code' => 404
            ];
        }
        
        toggleServiceAvailability($pdo, $id, $isAvailable);
        $updatedService = getServiceById($pdo, $id);
        
        return [
            'success' => true,
            'message' => 'Service ' . ($isAvailable ? 'activated' : 'deactivated') . ' successfully',
            'service' => $updatedService
        ];
        
    } catch (Exception $e) {
        error_log('Toggle service availability error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating service availability: ' . $e->getMessage()
        ];
    }
}

/**
 * Search services
 */
function searchServicesController($pdo, $query) {
    try {
        $services = searchServices($pdo, $query);
        
        return [
            'success' => true,
            'services' => $services
        ];
        
    } catch (Exception $e) {
        error_log('Search services error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error searching services: ' . $e->getMessage()
        ];
    }
}

/**
 * Get service categories
 */
function getServiceCategoriesController($pdo) {
    try {
        $categories = getServiceCategories($pdo);
        
        return [
            'success' => true,
            'categories' => $categories
        ];
        
    } catch (Exception $e) {
        error_log('Get categories error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching categories: ' . $e->getMessage()
        ];
    }
}

/**
 * Get service statistics
 */
function getServiceStatsController($pdo) {
    try {
        $total = getServicesCount($pdo);
        $available = getAvailableServicesCount($pdo);
        $categories = getServiceCategories($pdo);
        
        return [
            'success' => true,
            'stats' => [
                'total' => $total,
                'available' => $available,
                'unavailable' => $total - $available,
                'categories_count' => count($categories)
            ]
        ];
        
    } catch (Exception $e) {
        error_log('Get service stats error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching service statistics: ' . $e->getMessage()
        ];
    }
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Upload service image
 */
function uploadServiceImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }
    
    $uploadDir = '../uploads/services/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($file['name']));
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'url' => '/uploads/services/' . $filename
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file.'];
}

/**
 * Delete service image
 */
function deleteServiceImage($imageUrl) {
    $filepath = '..' . $imageUrl;
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}
