<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'load_more':
            $page = (int)$_GET['page'] ?? 1;
            $limit = 12;
            $offset = ($page - 1) * $limit;
            
            $products = getProducts($limit, $offset);
            
            echo json_encode([
                'success' => true,
                'products' => $products,
                'has_more' => count($products) === $limit
            ]);
            break;
            
        case 'search':
            $query = $_GET['query'] ?? '';
            $db = Database::getInstance()->getConnection();
            
            $searchQuery = $db->real_escape_string($query);
            $sql = "SELECT p.*, pi.image_url 
                    FROM products p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.status = 'active' 
                    AND (p.name LIKE '%$searchQuery%' OR p.description LIKE '%$searchQuery%')
                    LIMIT 20";
            
            $result = $db->query($sql);
            $products = [];
            
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'products' => $products
            ]);
            break;
    }
}
?>