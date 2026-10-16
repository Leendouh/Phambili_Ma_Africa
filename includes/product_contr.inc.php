<?php

// Product Controller - Handles business logic for products
// This converts the Node.js product controller to PHP

require_once 'product_model.inc.php';

/**
 * Create a new product with validation and image handling
 */
function createProductController($pdo, $data, $files = null) {
    // Validation
    if (empty($data['Name']) || !isset($data['Price'])) {
        return [
            'success' => false,
            'message' => 'Name and Price are required.',
            'code' => 400
        ];
    }
    
    $imageUrl = null;
    
    // Handle image upload
    if ($files && !empty($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadProductImage($files['image']);
        if ($uploadResult['success']) {
            $imageUrl = $uploadResult['url'];
        } else {
            error_log('Image upload failed: ' . $uploadResult['message']);
        }
    }
    
    try {
        $productData = [
            'Name' => trim($data['Name']),
            'Description' => !empty($data['Description']) ? trim($data['Description']) : null,
            'Price' => (float)$data['Price'],
            'Stock_Quantity' => isset($data['Stock_Quantity']) ? (int)$data['Stock_Quantity'] : 0,
            'Category' => !empty($data['Category']) ? trim($data['Category']) : null,
            'Is_Available' => isset($data['Is_Available']) ? (bool)$data['Is_Available'] : true,
            'Image_URL' => $imageUrl
        ];
        
        $productId = createProduct($pdo, $productData);
        $product = getProductById($pdo, $productId);
        
        return [
            'success' => true,
            'message' => 'Product created successfully',
            'product' => $product
        ];
        
    } catch (Exception $e) {
        error_log('Create product error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error creating product: ' . $e->getMessage()
        ];
    }
}

/**
 * Get all products for admin
 */
function getProductsController($pdo) {
    try {
        $products = getAllProducts($pdo);
        
        return [
            'success' => true,
            'products' => $products
        ];
    } catch (Exception $e) {
        error_log('Get products error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching products: ' . $e->getMessage()
        ];
    }
}

/**
 * Get public products (available products for customers)
 */
function getPublicProductsController($pdo) {
    try {
        $products = getAvailableProducts($pdo);
        
        // Format for API response
        $formattedProducts = array_map(function($product) {
            return [
                'ID' => (int)$product['ID'],
                'Name' => $product['Name'],
                'Description' => $product['Description'],
                'Price' => (float)$product['Price'],
                'Stock_Quantity' => (int)$product['Stock_Quantity'],
                'Category' => $product['Category'],
                'Is_Available' => (bool)$product['Is_Available'],
                'Image_URL' => $product['Image_URL'],
                'Created_At' => $product['created_at']
            ];
        }, $products);
        
        return [
            'success' => true,
            'products' => $formattedProducts
        ];
    } catch (Exception $e) {
        error_log('Get public products error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching products'
        ];
    }
}

/**
 * Get product by ID
 */
function getProductByIdController($pdo, $id) {
    try {
        $product = getProductById($pdo, $id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.',
                'code' => 404
            ];
        }
        
        return [
            'success' => true,
            'product' => $product
        ];
        
    } catch (Exception $e) {
        error_log('Get product error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching product: ' . $e->getMessage()
        ];
    }
}

/**
 * Update product with image handling
 */
function updateProductController($pdo, $id, $data, $files = null) {
    try {
        $product = getProductById($pdo, $id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.',
                'code' => 404
            ];
        }
        
        $imageUrl = $product['Image_URL'];
        
        // Handle image upload
        if ($files && !empty($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            // Delete old image
            if ($product['Image_URL']) {
                deleteProductImage($product['Image_URL']);
            }
            
            $uploadResult = uploadProductImage($files['image']);
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
        if (isset($data['Price'])) $updateData['Price'] = (float)$data['Price'];
        if (isset($data['Stock_Quantity'])) $updateData['Stock_Quantity'] = (int)$data['Stock_Quantity'];
        if (array_key_exists('Category', $data)) $updateData['Category'] = trim($data['Category']) ?: null;
        if (isset($data['Is_Available'])) $updateData['Is_Available'] = (bool)$data['Is_Available'];
        if ($imageUrl !== $product['Image_URL']) $updateData['Image_URL'] = $imageUrl;
        
        updateProduct($pdo, $id, $updateData);
        $updatedProduct = getProductById($pdo, $id);
        
        return [
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $updatedProduct
        ];
        
    } catch (Exception $e) {
        error_log('Update product error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating product: ' . $e->getMessage()
        ];
    }
}

/**
 * Delete product
 */
function deleteProductController($pdo, $id) {
    try {
        $product = getProductById($pdo, $id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.',
                'code' => 404
            ];
        }
        
        // Delete associated image
        if ($product['Image_URL']) {
            deleteProductImage($product['Image_URL']);
        }
        
        deleteProduct($pdo, $id);
        
        return [
            'success' => true,
            'message' => 'Product deleted successfully'
        ];
        
    } catch (Exception $e) {
        error_log('Delete product error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error deleting product: ' . $e->getMessage()
        ];
    }
}

/**
 * Toggle product availability
 */
function toggleProductAvailabilityController($pdo, $id, $isAvailable) {
    try {
        $product = getProductById($pdo, $id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.',
                'code' => 404
            ];
        }
        
        toggleProductAvailability($pdo, $id, $isAvailable);
        $updatedProduct = getProductById($pdo, $id);
        
        return [
            'success' => true,
            'message' => 'Product ' . ($isAvailable ? 'activated' : 'deactivated') . ' successfully',
            'product' => $updatedProduct
        ];
        
    } catch (Exception $e) {
        error_log('Toggle product availability error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating product availability: ' . $e->getMessage()
        ];
    }
}

/**
 * Search products
 */
function searchProductsController($pdo, $query) {
    try {
        $products = searchProducts($pdo, $query);
        
        return [
            'success' => true,
            'products' => $products
        ];
        
    } catch (Exception $e) {
        error_log('Search products error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error searching products: ' . $e->getMessage()
        ];
    }
}

/**
 * Get product categories
 */
function getProductCategoriesController($pdo) {
    try {
        $categories = getProductCategories($pdo);
        
        return [
            'success' => true,
            'categories' => $categories
        ];
        
    } catch (Exception $e) {
        error_log('Get product categories error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching categories: ' . $e->getMessage()
        ];
    }
}

/**
 * Get product statistics
 */
function getProductStatsController($pdo) {
    try {
        $total = getProductsCount($pdo);
        $available = getAvailableProductsCount($pdo);
        $lowStock = getLowStockProducts($pdo);
        $categories = getProductCategories($pdo);
        
        return [
            'success' => true,
            'stats' => [
                'total' => $total,
                'available' => $available,
                'unavailable' => $total - $available,
                'low_stock_count' => count($lowStock),
                'low_stock_items' => $lowStock,
                'categories_count' => count($categories)
            ]
        ];
        
    } catch (Exception $e) {
        error_log('Get product stats error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching product statistics: ' . $e->getMessage()
        ];
    }
}

/**
 * Update product stock
 */
function updateProductStockController($pdo, $id, $quantity) {
    try {
        $product = getProductById($pdo, $id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.',
                'code' => 404
            ];
        }
        
        if ($quantity < 0) {
            return [
                'success' => false,
                'message' => 'Stock quantity cannot be negative.',
                'code' => 400
            ];
        }
        
        updateProductStock($pdo, $id, $quantity);
        $updatedProduct = getProductById($pdo, $id);
        
        return [
            'success' => true,
            'message' => 'Product stock updated successfully',
            'product' => $updatedProduct
        ];
        
    } catch (Exception $e) {
        error_log('Update product stock error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating product stock: ' . $e->getMessage()
        ];
    }
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Upload product image
 */
function uploadProductImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }
    
    $uploadDir = '../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($file['name']));
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'url' => '/uploads/products/' . $filename
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file.'];
}

/**
 * Delete product image
 */
function deleteProductImage($imageUrl) {
    $filepath = '..' . $imageUrl;
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}
