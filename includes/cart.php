<?php
require_once 'database.php';

class Cart {
    public static function mergeCarts($userId, $localCart) {
        if (empty($localCart)) return;
        
        $db = Database::getInstance()->getConnection();
        
        // Получаем или создаем корзину пользователя
        $cartId = self::getUserCartId($userId);
        
        foreach ($localCart['items'] as $item) {
            // Находим size_id
            $sizeIdQuery = $db->query("
                SELECT id FROM product_sizes 
                WHERE product_id = {$item['product_id']} 
                AND size = {$item['size']}
            ");
            
            if ($sizeIdQuery->num_rows > 0) {
                $sizeId = $sizeIdQuery->fetch_assoc()['id'];
                
                // Проверяем, есть ли уже такой товар в корзине
                $existingItem = $db->query("
                    SELECT * FROM cart_items 
                    WHERE cart_id = $cartId 
                    AND product_id = {$item['product_id']} 
                    AND size_id = $sizeId
                ");
                
                if ($existingItem->num_rows > 0) {
                    // Обновляем количество
                    $existing = $existingItem->fetch_assoc();
                    $newQty = $existing['quantity'] + $item['quantity'];
                    $db->query("
                        UPDATE cart_items 
                        SET quantity = $newQty 
                        WHERE id = {$existing['id']}
                    ");
                } else {
                    // Добавляем новый элемент
                    $db->query("
                        INSERT INTO cart_items (cart_id, product_id, size_id, quantity)
                        VALUES ($cartId, {$item['product_id']}, $sizeId, {$item['quantity']})
                    ");
                }
            }
        }
    }
    
    private static function getUserCartId($userId) {
        $db = Database::getInstance()->getConnection();
        
        $result = $db->query("SELECT id FROM carts WHERE user_id = $userId");
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['id'];
        } else {
            $db->query("INSERT INTO carts (user_id) VALUES ($userId)");
            return $db->insert_id;
        }
    }
    
    public static function getUserCart($userId) {
        $db = Database::getInstance()->getConnection();
        
        $cartId = self::getUserCartId($userId);
        
        $result = $db->query("
            SELECT ci.*, p.name, p.price, ps.size 
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            JOIN product_sizes ps ON ci.size_id = ps.id
            WHERE ci.cart_id = $cartId
        ");
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
}
?>