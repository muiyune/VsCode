<?php
// Проверяем, запущена ли сессия
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключаем необходимые файлы
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Магазин обуви</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/main.js" defer></script>
</head>
<body>
    <div class="header">
        <div class="container nav">
            <a href="../index.php" class="logo">Обувной магазин</a>
            <div class="nav-links">
                <a href="../index.php">Главная</a>
                <a href="../catalog.php">Каталог</a>
                <a href="../cart.php">
                    Корзина 
                    <?php 
                    if (isLoggedIn()) { 
                        $cart_count = getCartCount();
                        if ($cart_count > 0) { 
                    ?>
                        <span class="cart-count" style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8rem;">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php 
                        }
                    } 
                    ?>
                </a>
                
                <?php if (isLoggedIn()) { ?>
                    <span class="user-greeting">Привет, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>!</span>
                    <a href="../profile.php">Профиль</a>
                    <a href="../orders.php">Заказы</a>
                    <?php if (hasRole('manager')) { ?>
                        <a href="../admin/dashboard.php">Админка</a>
                    <?php } ?>
                    <a href="../index.php?logout=1">Выйти</a>
                <?php } else { ?>
                    <a href="../login.php">Войти</a>
                    <a href="../register.php">Регистрация</a>
                <?php } ?>
            </div>
        </div>
    </div>
    
    <?php 
    // Обработка выхода
    if (isset($_GET['logout'])) {
        Auth::logout();
    }
    ?>