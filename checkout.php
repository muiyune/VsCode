<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/cart.php';

if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$cartItems = Cart::getUserCart($userId);

if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

// Расчет общей суммы
$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    
    $shippingAddress = $db->real_escape_string($_POST['shipping_address']);
    $paymentMethod = $db->real_escape_string($_POST['payment_method']);
    
    // Создание заказа
    $db->query("
        INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status)
        VALUES ($userId, $totalAmount, '$shippingAddress', '$paymentMethod', 'created')
    ");
    
    $orderId = $db->insert_id;
    
    // Добавление товаров в заказ
    foreach ($cartItems as $item) {
        $db->query("
            INSERT INTO order_items (order_id, product_id, size_id, quantity, price)
            VALUES ($orderId, {$item['product_id']}, {$item['size_id']}, {$item['quantity']}, {$item['price']})
        ");
        
        // Обновление остатков
        $db->query("
            UPDATE product_sizes 
            SET quantity = quantity - {$item['quantity']}
            WHERE id = {$item['size_id']}
        ");
    }
    
    // Очистка корзины
    $cartId = Cart::getUserCartId($userId);
    $db->query("DELETE FROM cart_items WHERE cart_id = $cartId");
    
    header("Location: orders.php?order_id=$orderId");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <h2>Оформление заказа</h2>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <div>
                <h3>Данные для доставки</h3>
                <form method="POST">
                    <div>
                        <label for="shipping_address">Адрес доставки:</label>
                        <textarea id="shipping_address" name="shipping_address" rows="4" required></textarea>
                    </div>
                    
                    <div>
                        <label for="payment_method">Способ оплаты:</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">Выберите способ оплаты</option>
                            <option value="cash">Наличными при получении</option>
                            <option value="card">Банковской картой онлайн</option>
                            <option value="card_upon_receipt">Картой при получении</option>
                        </select>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit">Подтвердить заказ</button>
                        <a href="cart.php" style="margin-left: 10px;">Вернуться в корзину</a>
                    </div>
                </form>
            </div>
            
            <div>
                <h3>Ваш заказ</h3>
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <?php foreach($cartItems as $item): ?>
                        <div style="padding: 10px 0; border-bottom: 1px solid #eee;">
                            <p><strong><?php echo $item['name']; ?></strong></p>
                            <p>Размер: <?php echo $item['size']; ?></p>
                            <p>Количество: <?php echo $item['quantity']; ?> × <?php echo $item['price']; ?> руб.</p>
                            <p style="text-align: right;"><?php echo $item['quantity'] * $item['price']; ?> руб.</p>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <h3>Итого: <?php echo $totalAmount; ?> руб.</h3>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>