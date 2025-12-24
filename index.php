<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Магазин обуви - Главная</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Магазин обуви</h1>
        <nav>
            <a href="index.php">Главная</a>
            <a href="catalog.php">Каталог</a>
            <a href="cart.php">Корзина</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="profile.php">Профиль</a>
                <?php if($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager'): ?>
                    <a href="admin/dashboard.php">Админка</a>
                <?php endif; ?>
                <a href="?logout=1">Выйти</a>
            <?php else: ?>
                <a href="login.php">Войти</a>
                <a href="register.php">Регистрация</a>
            <?php endif; ?>
        </nav>
    </header>
    
    <main>
        <h2>Популярные товары</h2>
        <div class="products">
            <?php $products = getProducts(6); ?>
            <?php foreach($products as $product): ?>
                <div class="product-card">
                    <?php if(!empty($product['image_url'])): ?>
                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                    <?php endif; ?>
                    <h3><?php echo $product['name']; ?></h3>
                    <p>Цена: <?php echo $product['price']; ?> руб.</p>
                    <a href="product.php?id=<?php echo $product['id']; ?>">Подробнее</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    
    <script src="assets/js/main.js"></script>
</body>
</html>