<?php
// Подключаем database.php в самом начале
require_once __DIR__ . '/database.php';

function redirect($url) {
    header("Location: $url");
    exit();
}

function getProducts($limit = 12, $offset = 0) {
    // Получаем экземпляр базы данных
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT p.*, pi.image_url 
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
            WHERE p.status = 'active'
            LIMIT $limit OFFSET $offset";
    
    $result = $db->query($sql);
    $products = [];
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

function getProduct($id) {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT p.* 
            FROM products p 
            WHERE p.id = $id";
    
    $result = $db->query($sql);
    return $result->fetch_assoc();
}

function getProductSizes($productId) {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM product_sizes WHERE product_id = $productId AND quantity > 0";
    $result = $db->query($sql);
    
    $sizes = [];
    while ($row = $result->fetch_assoc()) {
        $sizes[] = $row;
    }
    
    return $sizes;
}

function getProductImages($productId) {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM product_images WHERE product_id = $productId";
    $result = $db->query($sql);
    
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    
    return $images;
}
?>