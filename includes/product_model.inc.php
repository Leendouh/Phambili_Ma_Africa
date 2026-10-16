<?php

// Product Model - Handles all database operations for products

/**
 * Create a new product
 */
function createProduct($pdo, $data) {
    $sql = "INSERT INTO products (Name, Description, Price, Stock_Quantity, Category, Is_Available, Image_URL) 
            VALUES (:name, :description, :price, :stock_quantity, :category, :is_available, :image_url)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $data['Name'],
        ':description' => $data['Description'] ?? null,
        ':price' => $data['Price'],
        ':stock_quantity' => $data['Stock_Quantity'] ?? 0,
        ':category' => $data['Category'] ?? null,
        ':is_available' => isset($data['Is_Available']) ? (int)$data['Is_Available'] : 1,
        ':image_url' => $data['Image_URL'] ?? null
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Get all products
 */
function getAllProducts($pdo) {
    $sql = "SELECT * FROM products ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get available products (for public display)
 */
function getAvailableProducts($pdo) {
    $sql = "SELECT * FROM products WHERE Is_Available = 1 AND Stock_Quantity > 0 ORDER BY Name ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get products by category
 */
function getProductsByCategory($pdo, $category) {
    $sql = "SELECT * FROM products WHERE Category = :category AND Is_Available = 1 ORDER BY Name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':category' => $category]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get product by ID
 */
function getProductById($pdo, $id) {
    $sql = "SELECT * FROM products WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Update product
 */
function updateProduct($pdo, $id, $data) {
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
    if (isset($data['Price'])) {
        $fields[] = "Price = :price";
        $params[':price'] = $data['Price'];
    }
    if (isset($data['Stock_Quantity'])) {
        $fields[] = "Stock_Quantity = :stock_quantity";
        $params[':stock_quantity'] = $data['Stock_Quantity'];
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
    
    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Delete product
 */
function deleteProduct($pdo, $id) {
    $sql = "DELETE FROM products WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

/**
 * Toggle product availability
 */
function toggleProductAvailability($pdo, $id, $isAvailable) {
    $sql = "UPDATE products SET Is_Available = :is_available WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':id' => $id,
        ':is_available' => (int)$isAvailable
    ]);
}

/**
 * Update product stock
 */
function updateProductStock($pdo, $id, $quantity) {
    $sql = "UPDATE products SET Stock_Quantity = :quantity WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':id' => $id,
        ':quantity' => $quantity
    ]);
}

/**
 * Decrement product stock
 */
function decrementProductStock($pdo, $id, $amount = 1) {
    $sql = "UPDATE products SET Stock_Quantity = Stock_Quantity - :amount WHERE ID = :id AND Stock_Quantity >= :amount";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id, ':amount' => $amount]);
    return $stmt->rowCount() > 0;
}

/**
 * Increment product stock
 */
function incrementProductStock($pdo, $id, $amount = 1) {
    $sql = "UPDATE products SET Stock_Quantity = Stock_Quantity + :amount WHERE ID = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([':id' => $id, ':amount' => $amount]);
}

/**
 * Search products
 */
function searchProducts($pdo, $query) {
    $sql = "SELECT * FROM products WHERE Name LIKE :query OR Description LIKE :query OR Category LIKE :query ORDER BY Name ASC";
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->execute([':query' => $searchTerm]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get product categories
 */
function getProductCategories($pdo) {
    $sql = "SELECT DISTINCT Category FROM products WHERE Category IS NOT NULL ORDER BY Category ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get products count
 */
function getProductsCount($pdo) {
    $sql = "SELECT COUNT(*) FROM products";
    return $pdo->query($sql)->fetchColumn();
}

/**
 * Get available products count
 */
function getAvailableProductsCount($pdo) {
    $sql = "SELECT COUNT(*) FROM products WHERE Is_Available = 1 AND Stock_Quantity > 0";
    return $pdo->query($sql)->fetchColumn();
}

/**
 * Get low stock products
 */
function getLowStockProducts($pdo, $threshold = 10) {
    $sql = "SELECT * FROM products WHERE Stock_Quantity <= :threshold AND Is_Available = 1 ORDER BY Stock_Quantity ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':threshold' => $threshold]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
