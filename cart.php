<?php
require_once 'includes/config.php';
require_once 'includes/cart.php';

// Если пользователь авторизован, показываем корзину из БД
if(isset($_SESSION['user_id'])) {
    $cartItems = Cart::getUserCart($_SESSION['user_id']);
} else {
    // Для неавторизованных - из localStorage
    $cartItems = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина покупок</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <h2>Ваша корзина</h2>
        
        <div id="cart-items">
            <?php if(empty($cartItems)): ?>
                <p>Корзина пуста</p>
            <?php else: ?>
                <?php $total = 0; ?>
                <?php foreach($cartItems as $item): ?>
                    <div class="cart-item">
                        <span><?php echo $item['name']; ?> (Размер: <?php echo $item['size']; ?>)</span>
                        <span>Количество: <?php echo $item['quantity']; ?></span>
                        <span><?php echo $item['price'] * $item['quantity']; ?> руб.</span>
                    </div>
                    <?php $total += $item['price'] * $item['quantity']; ?>
                <?php endforeach; ?>
                
                <div class="cart-total">
                    <h3>Итого: <?php echo $total; ?> руб.</h3>
                </div>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="checkout.php"><button>Оформить заказ</button></a>
                <?php else: ?>
                    <p>Для оформления заказа <a href="login.php">войдите</a> или <a href="register.php">зарегистрируйтесь</a></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>