<?php
// admin/sidebar.php - общий сайдбар для админ панели
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2><?= SITE_NAME ?></h2>
        <p>Панель управления</p>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>>
                <a href="index.php">📊 Дашборд</a>
            </li>
            <li <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'class="active"' : '' ?>>
                <a href="products.php">👟 Товары</a>
            </li>
            <li <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'class="active"' : '' ?>>
                <a href="orders.php">📦 Заказы</a>
            </li>
            <?php if($auth->isAdmin()): ?>
                <li <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : '' ?>>
                    <a href="users.php">👥 Пользователи</a>
                </li>
            <?php endif; ?>
            <li class="divider"></li>
            <li><a href="../index.php">🏠 На сайт</a></li>
            <li><a href="../logout.php">🚪 Выйти</a></li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <p>Вы вошли как: <strong><?= $auth->getUserName() ?></strong></p>
        <p>Роль: <?= $auth->isAdmin() ? 'Администратор' : 'Менеджер' ?></p>
    </div>
</aside>