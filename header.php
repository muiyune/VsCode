<?php
require_once 'includes/config.php';
?>
<header>
    <h1>Магазин обуви</h1>
    <nav>
        <a href="index.php">Главная</a>
        <a href="catalog.php">Каталог</a>
        <a href="cart.php" class="cart-link">
            Корзина <span class="cart-count">0</span>
        </a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="profile.php"><?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
            <?php if($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager'): ?>
                <a href="admin/dashboard.php">Админка</a>
            <?php endif; ?>
            <!-- Исправленная ссылка на выход -->
            <a href="logout.php">Выйти</a>
        <?php else: ?>
            <a href="login.php">Войти</a>
            <a href="register.php">Регистрация</a>
        <?php endif; ?>
    </nav>
</header>