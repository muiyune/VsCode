<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/order.php';
require_once '../includes/product.php';
require_once '../includes/user.php';

$auth = new Auth();
$auth->requireManager();

$orderManager = new Order();
$productManager = new Product();
$userManager = new User();

// Статистика
$orderStats = $orderManager->getAllOrders([], 10);
$userStats = $userManager->getUserStats();

// Последние заказы
$recentOrders = $orderManager->getAllOrders([], 10, 0);

// Популярные товары
$popularProducts = $productManager->getPopularProducts(5);

// Общая статистика
$totalOrders = count($orderManager->getAllOrders([]));
$totalProducts = $productManager->getProductsCount([]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Сайдбар -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><?= SITE_NAME ?></h2>
                <p>Панель управления</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="index.php">📊 Дашборд</a></li>
                    <li><a href="products.php">👟 Товары</a></li>
                    <li><a href="orders.php">📦 Заказы</a></li>
                    <?php if($auth->isAdmin()): ?>
                        <li><a href="users.php">👥 Пользователи</a></li>
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
        
        <!-- Основной контент -->
        <main class="admin-content">
            <header class="admin-header">
                <h1>Дашборд</h1>
                <div class="admin-actions">
                    <span><?= date('d.m.Y H:i') ?></span>
                </div>
            </header>
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?= $totalOrders ?></h3>
                        <p>Всего заказов</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👟</div>
                    <div class="stat-info">
                        <h3><?= $totalProducts ?></h3>
                        <p>Товаров в каталоге</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?= $userStats['total'] ?? 0 ?></h3>
                        <p>Пользователей</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3><?= 
                            array_sum(array_column(
                                array_slice($orderStats, 0, 10), 
                                'total_amount'
                            )) 
                        ?> ₽</h3>
                        <p>Общая выручка</p>
                    </div>
                </div>
            </div>
            
            <!-- Последние заказы -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Последние заказы</h2>
                    <a href="orders.php" class="btn btn-primary">Все заказы</a>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Пользователь</th>
                                <th>Дата</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['user_name']) ?></td>
                                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td><?= format_price($order['total_amount']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= $orderManager->getStatusText($order['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="orders.php?action=view&id=<?= $order['id'] ?>" 
                                           class="btn btn-small">Просмотр</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Популярные товары -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Популярные товары</h2>
                    <a href="products.php" class="btn btn-primary">Все товары</a>
                </div>
                
                <div class="products-grid">
                    <?php foreach($popularProducts as $product): ?>
                        <div class="product-card-small">
                            <div class="product-image">
                                <img src="<?= $product['main_image'] ?: '../assets/images/no-image.jpg' ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>">
                            </div>
                            <div class="product-info">
                                <h4><?= htmlspecialchars($product['name']) ?></h4>
                                <p class="product-brand"><?= htmlspecialchars($product['brand']) ?></p>
                                <p class="product-price"><?= format_price($product['price']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>