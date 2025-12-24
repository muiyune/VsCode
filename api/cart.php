<?php
require_once '../includes/config.php';
require_once '../includes/cart.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            if (!isset($_SESSION['user_id'])) {
                // Для неавторизованных возвращаем данные для localStorage
                $response = [
                    'success' => true,
                    'local_cart' => true
                ];
                echo json_encode($response);
                exit;
            }
            
            $productId = (int)$_POST['product_id'];
            $sizeId = (int)$_POST['size_id'];
            $quantity = (int)$_POST['quantity'];
            
            // Добавление в корзину БД
            $cartId = Cart::getUserCartId($_SESSION['user_id']);
            $result = Cart::addToCart($cartId, $productId, $sizeId, $quantity);
            
            echo json_encode(['success' => $result]);
            break;
            
        case 'remove':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => true]);
                exit;
            }
            
            $itemId = (int)$_POST['item_id'];
            $result = Cart::removeFromCart($itemId);
            
            echo json_encode(['success' => $result]);
            break;
            
        case 'update':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => true]);
                exit;
            }
            
            $itemId = (int)$_POST['item_id'];
            $quantity = (int)$_POST['quantity'];
            $result = Cart::updateQuantity($itemId, $quantity);
            
            echo json_encode(['success' => $result]);
            break;
    }
}
?>