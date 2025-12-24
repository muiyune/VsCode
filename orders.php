<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

// Если передан ID заказа, показываем детали
if (isset($_GET['order_id'])) {
    $orderId = (int)$_GET['order_id'];
    
    $order = $db->query("
        SELECT * FROM orders 
        WHERE id = $orderId AND user_id = $userId
    ")->fetch_assoc();
    
    if ($order) {
        $orderItems = $db->query("
            SELECT oi.*, p.name, ps.size
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN product_sizes ps ON oi.size_id = ps.id
            WHERE oi.order_id = $orderId
        ");
    }
} else {
    // Список всех заказов пользователя
    $orders = $db->query("
        SELECT * FROM orders 
        WHERE user_id = $userId 
        ORDER BY created_at DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .orders-list, .order-items {
            margin: 20px 0;
        }
        
        .order-card, .order-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .order-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .status-created { background: #ffc107; color: #000; }
        .status-paid { background: #17a2b8; color: white; }
        .status-processing { background: #007bff; color: white; }
        .status-shipped { background: #6f42c1; color: white; }
        .status-delivered { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <h2>Мои заказы</h2>
        
        <?php if(isset($_GET['order_id']) && $order): ?>
            <a href="orders.php" style="display: inline-block; margin-bottom: 20px;">← Вернуться к списку заказов</a>
            
            <h3>Заказ #<?php echo $order['id']; ?></h3>
            
            <div class="order-card">
                <p><strong>Дата заказа:</strong> <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                <p><strong>Сумма заказа:</strong> <?php echo $order['total_amount']; ?> руб.</p>
                <p><strong>Статус:</strong> 
                    <span class="order-status status-<?php echo $order['status']; ?>">
                        <?php 
                        $statusNames = [
                            'created' => 'Оформлен',
                            'paid' => 'Оплачен',
                            'processing' => 'В обработке',
                            'shipped' => 'Отправлен',
                            'delivered' => 'Доставлен',
                            'cancelled' => 'Отменен'
                        ];
                        echo $statusNames[$order['status']];
                        ?>
                    </span>
                </p>
                <p><strong>Способ оплаты:</strong> 
                    <?php 
                    $paymentMethods = [
                        'cash' => 'Наличными',
                        'card' => 'Картой онлайн',
                        'card_upon_receipt' => 'Картой при получении'
                    ];
                    echo $paymentMethods[$order['payment_method']] ?? $order['payment_method'];
                    ?>
                </p>
                <p><strong>Адрес доставки:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            </div>
            
            <h4>Состав заказа:</h4>
            <div class="order-items">
                <?php while($item = $orderItems->fetch_assoc()): ?>
                    <div class="order-item">
                        <p><strong><?php echo $item['name']; ?></strong></p>
                        <p>Размер: <?php echo $item['size']; ?></p>
                        <p>Количество: <?php echo $item['quantity']; ?></p>
                        <p>Цена: <?php echo $item['price']; ?> руб.</p>
                        <p>Итого: <?php echo $item['price'] * $item['quantity']; ?> руб.</p>
                    </div>
                <?php endwhile; ?>
            </div>
            
        <?php else: ?>
            <?php if($orders->num_rows > 0): ?>
                <div class="orders-list">
                    <?php while($order = $orders->fetch_assoc()): ?>
                        <div class="order-card">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h3>Заказ #<?php echo $order['id']; ?></h3>
                                    <p>Дата: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                                    <p>Сумма: <?php echo $order['total_amount']; ?> руб.</p>
                                </div>
                                
                                <div style="text-align: right;">
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php 
                                        $statusNames = [
                                            'created' => 'Оформлен',
                                            'paid' => 'Оплачен',
                                            'processing' => 'В обработке',
                                            'shipped' => 'Отправлен',
                                            'delivered' => 'Доставлен',
                                            'cancelled' => 'Отменен'
                                        ];
                                        echo $statusNames[$order['status']];
                                        ?>
                                    </span>
                                    <br>
                                    <a href="orders.php?order_id=<?php echo $order['id']; ?>" style="margin-top: 10px; display: inline-block;">
                                        Подробнее
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>У вас еще нет заказов.</p>
                <a href="catalog.php">Перейти в каталог</a>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>