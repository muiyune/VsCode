<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!Auth::isAdmin() && !Auth::isManager()) {
    header("Location: ../login.php");
    exit();
}

// Статистика
$db = Database::getInstance()->getConnection();

// Количество товаров
$productsCount = $db->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];

// Количество заказов
$ordersCount = $db->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];

// Количество пользователей
$usersCount = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Последние заказы
$recentOrders = $db->query("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// Популярные товары
$popularProducts = $db->query("
    SELECT p.name, COUNT(oi.product_id) as sold_count
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY oi.product_id
    ORDER BY sold_count DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0;
            color: #333;
        }
        
        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
        }
        
        .admin-menu {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .admin-menu a {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .admin-menu a:hover {
            background: #0056b3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        th {
            background: #f2f2f2;
        }
    </style>
</head>
<body>
    <header>
        <h1>Панель управления</h1>
        <nav>
            <a href="../index.php">На сайт</a>
            <a href="dashboard.php">Дашборд</a>
            <a href="products.php">Товары</a>
            <?php if(Auth::isAdmin()): ?>
                <a href="users.php">Пользователи</a>
            <?php endif; ?>
            <a href="../logout.php">Выйти</a>
        </nav>
    </header>
    
    <main>
        <h2>Общая статистика</h2>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Товары</h3>
                <div class="number"><?php echo $productsCount; ?></div>
                <p>активных товаров</p>
            </div>
            
            <div class="stat-card">
                <h3>Заказы</h3>
                <div class="number"><?php echo $ordersCount; ?></div>
                <p>всего заказов</p>
            </div>
            
            <div class="stat-card">
                <h3>Пользователи</h3>
                <div class="number"><?php echo $usersCount; ?></div>
                <p>зарегистрировано</p>
            </div>
        </div>
        
        <div class="admin-menu">
            <a href="products.php?action=add">Добавить товар</a>
            <a href="products.php">Управление товарами</a>
            <?php if(Auth::isAdmin()): ?>
                <a href="users.php">Управление пользователями</a>
            <?php endif; ?>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
                <h3>Последние заказы</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $recentOrders->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo $order['user_name']; ?></td>
                                <td><?php echo $order['total_amount']; ?> руб.</td>
                                <td><?php echo $order['status']; ?></td>
                                <td><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div>
                <h3>Популярные товары</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Товар</th>
                            <th>Продано</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($product = $popularProducts->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['sold_count']; ?> шт.</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>