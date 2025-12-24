<?php
require_once 'database.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Получение товара по ID
    public function getProduct($id) {
        $stmt = $this->db->query(
            "SELECT p.*,
                    (SELECT GROUP_CONCAT(DISTINCT size ORDER BY size) 
                     FROM product_sizes WHERE product_id = p.id AND quantity > 0) as available_sizes
             FROM products p 
             WHERE p.id = ? AND p.status = 'active'",
            [$id]
        );
        
        if (!$stmt) {
            return null;
        }
        
        $product = $stmt->fetch();
        
        if ($product) {
            $product['images'] = $this->getProductImages($id);
            $product['sizes'] = $this->getProductSizes($id);
        }
        
        return $product;
    }
    
    // Получение изображений товара
    public function getProductImages($productId) {
        $stmt = $this->db->query(
            "SELECT * FROM product_images 
             WHERE product_id = ? ORDER BY is_main DESC, id ASC",
            [$productId]
        );
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Получение размеров товара
    public function getProductSizes($productId) {
        $stmt = $this->db->query(
            "SELECT * FROM product_sizes 
             WHERE product_id = ? AND quantity > 0 
             ORDER BY size",
            [$productId]
        );
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Получение товаров с фильтрацией
    public function getProducts($filters = [], $limit = 12, $offset = 0) {
        $where = "p.status = 'active'";
        $params = [];
        
        // Категория
        if (!empty($filters['category'])) {
            $where .= " AND p.category = ?";
            $params[] = $filters['category'];
        }
        
        // Бренд
        if (!empty($filters['brand'])) {
            $where .= " AND p.brand = ?";
            $params[] = $filters['brand'];
        }
        
        // Цена
        if (!empty($filters['min_price'])) {
            $where .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Цвет
        if (!empty($filters['color'])) {
            $where .= " AND p.color = ?";
            $params[] = $filters['color'];
        }
        
        // Размер
        if (!empty($filters['size'])) {
            $where .= " AND EXISTS (
                SELECT 1 FROM product_sizes ps 
                WHERE ps.product_id = p.id 
                AND ps.size = ? 
                AND ps.quantity > 0
            )";
            $params[] = $filters['size'];
        }
        
        // Поиск
        if (!empty($filters['search'])) {
            $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Сортировка
        $orderBy = "p.created_at DESC";
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $orderBy = "p.price ASC";
                    break;
                case 'price_desc':
                    $orderBy = "p.price DESC";
                    break;
                case 'name_asc':
                    $orderBy = "p.name ASC";
                    break;
                case 'name_desc':
                    $orderBy = "p.name DESC";
                    break;
            }
        }
        
        // Параметры для лимита
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->query(
            "SELECT p.*,
                    (SELECT image_url FROM product_images 
                     WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image
             FROM products p
             WHERE $where
             ORDER BY $orderBy
             LIMIT ? OFFSET ?",
            $params
        );
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Получение общего количества товаров
    public function getProductsCount($filters = []) {
        $where = "status = 'active'";
        $params = [];
        
        // Те же фильтры, что и в getProducts
        if (!empty($filters['category'])) {
            $where .= " AND category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $where .= " AND brand = ?";
            $params[] = $filters['brand'];
        }
        
        if (!empty($filters['min_price'])) {
            $where .= " AND price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where .= " AND price <= ?";
            $params[] = $filters['max_price'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (name LIKE ? OR description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $stmt = $this->db->query(
            "SELECT COUNT(*) as count FROM products WHERE $where",
            $params
        );
        
        if (!$stmt) {
            return 0;
        }
        
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    // Получение всех категорий
    public function getCategories() {
        $stmt = $this->db->query(
            "SELECT DISTINCT category FROM products 
             WHERE category IS NOT NULL AND status = 'active'
             ORDER BY category"
        );
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Получение всех брендов
    public function getBrands() {
        $stmt = $this->db->query(
            "SELECT DISTINCT brand FROM products 
             WHERE brand IS NOT NULL AND status = 'active'
             ORDER BY brand"
        );
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Получение всех цветов
    public function getColors() {
        $stmt = $this->db->query(
            "SELECT DISTINCT color FROM products 
             WHERE color IS NOT NULL AND status = 'active'
             ORDER BY color"
        );
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Получение всех размеров
    public function getSizes() {
        $stmt = $this->db->query(
            "SELECT DISTINCT size FROM product_sizes 
             WHERE quantity > 0
             ORDER BY size"
        );
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Получение популярных товаров
    public function getPopularProducts($limit = 8) {
        $stmt = $this->db->query(
            "SELECT p.*,
                    (SELECT image_url FROM product_images 
                     WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image,
                    COALESCE(SUM(oi.quantity), 0) as total_sold
             FROM products p
             LEFT JOIN order_items oi ON p.id = oi.product_id
             WHERE p.status = 'active'
             GROUP BY p.id
             ORDER BY total_sold DESC, p.created_at DESC
             LIMIT ?",
            [$limit]
        );
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Получение новых товаров
    public function getNewProducts($limit = 8) {
        $stmt = $this->db->query(
            "SELECT p.*,
                    (SELECT image_url FROM product_images 
                     WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image
             FROM products p
             WHERE p.status = 'active'
             ORDER BY p.created_at DESC
             LIMIT ?",
            [$limit]
        );
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Проверка наличия размера
    public function checkSizeAvailability($productId, $size) {
        $stmt = $this->db->query(
            "SELECT quantity FROM product_sizes 
             WHERE product_id = ? AND size = ?",
            [$productId, $size]
        );
        
        if (!$stmt) {
            return 0;
        }
        
        $result = $stmt->fetch();
        return $result ? $result['quantity'] : 0;
    }
}
?>