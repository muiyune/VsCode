<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireManager();

$action = $_GET['action'] ?? '';
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Фильтры
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Изменение статуса заказа
if ($action === 'update_status' && $orderId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $newStatus = $_POST['status'] ?? '';
        $validStatuses = ['created', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (in_array($newStatus, $validStatuses)) {
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $orderId]);
                
                // Добавляем запись в историю
                $stmt = $pdo->prepare("
                    INSERT INTO order_history (order_id, status, notes, user_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderId,
                    $newStatus,
                    $_POST['notes'] ?? 'Статус изменен через админ-панель',
                    $_SESSION['user_id']
                ]);
                
                header("Location: orders.php?action=view&id=$orderId&success=Статус обновлен");
                exit();
            } catch (PDOException $e) {
                header("Location: orders.php?action=view&id=$orderId&error=Ошибка обновления");
                exit();
            }
        }
    }
}

// Просмотр конкретного заказа
if ($action === 'view' && $orderId > 0) {
    try {
        // Получаем информацию о заказе
        $stmt = $pdo->prepare("
            SELECT o.*, u.name as user_name, u.email, u.phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            header("Location: orders.php");
            exit();
        }
        
        // Товары в заказе
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.brand, ps.size,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image_url
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN product_sizes ps ON oi.size_id = ps.id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ");
        $stmt->execute([$orderId]);
        $orderItems = $stmt->fetchAll();
        
        // История заказа
        $stmt = $pdo->prepare("
            SELECT oh.*, u.name as changed_by
            FROM order_history oh
            LEFT JOIN users u ON oh.user_id = u.id
            WHERE oh.order_id = ?
            ORDER BY oh.created_at DESC
        ");
        $stmt->execute([$orderId]);
        $history = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Order view error: " . $e->getMessage());
        header("Location: orders.php");
        exit();
    }
    
    include 'order_view.php';
    exit();
}

// Получение списка заказов с фильтрами
$where = [];
$params = [];

if ($status) {
    $where[] = "o.status = ?";
    $params[] = $status;
}

if ($search) {
    $where[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR o.shipping_address LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($date_from) {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

try {
    // Получаем заказы
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email,
               COUNT(oi.id) as items_count,
               SUM(oi.quantity) as total_items
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        $whereClause
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $limitParams = array_merge($params, [$limit, $offset]);
    $stmt->execute($limitParams);
    $orders = $stmt->fetchAll();
    
    // Общее количество
    $countStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) as total
        FROM orders o
        JOIN users u ON o.user_id = u.id
        $whereClause
    ");
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetch()['total'];
    
    $totalPages = ceil($totalOrders / $limit);
    
    // Статистика по статусам
    $stats = [];
    $statuses = ['created', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];
    foreach ($statuses as $statStatus) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = ?");
        $stmt->execute([$statStatus]);
        $stats[$statStatus] = $stmt->fetch()['count'];
    }
    
} catch (PDOException $e) {
    error_log("Orders admin error: " . $e->getMessage());
    $orders = [];
    $totalOrders = 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами - ShoeStore</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-orders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Сайдбар -->
        <?php include 'sidebar.php'; ?>

        <!-- Основной контент -->
        <main class="admin-content">
            <!-- Шапка -->
            <header class="admin-header">
                <h1>Управление заказами</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="printOrders()">
                        <i class="fas fa-print"></i> Печать
                    </button>
                </div>
            </header>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo e($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo e($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Статистика по статусам -->
            <div class="stats-cards">
                <a href="orders.php" class="stat-card-link">
                    <div class="stat-card-mini">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['created'] + $stats['paid'] + $stats['processing'] + $stats['shipped'] + $stats['delivered']; ?></h3>
                            <p>Всего заказов</p>
                        </div>
                    </div>
                </a>
                
                <a href="orders.php?status=created" class="stat-card-link">
                    <div class="stat-card-mini <?php echo $status === 'created' ? 'active' : ''; ?>">
                        <div class="stat-icon" style="background-color: #ffeaa7;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['created']; ?></h3>
                            <p>Новые</p>
                        </div>
                    </div>
                </a>
                
                <a href="orders.php?status=processing" class="stat-card-link">
                    <div class="stat-card-mini <?php echo $status === 'processing' ? 'active' : ''; ?>">
                        <div class="stat-icon" style="background-color: #74b9ff;">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['processing']; ?></h3>
                            <p>В обработке</p>
                        </div>
                    </div>
                </a>
                
                <a href="orders.php?status=shipped" class="stat-card-link">
                    <div class="stat-card-mini <?php echo $status === 'shipped' ? 'active' : ''; ?>">
                        <div class="stat-icon" style="background-color: #55efc4;">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['shipped']; ?></h3>
                            <p>В пути</p>
                        </div>
                    </div>
                </a>
                
                <a href="orders.php?status=delivered" class="stat-card-link">
                    <div class="stat-card-mini <?php echo $status === 'delivered' ? 'active' : ''; ?>">
                        <div class="stat-icon" style="background-color: #00b894;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['delivered']; ?></h3>
                            <p>Доставлены</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Фильтры -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Поиск по номеру, имени или адресу..." 
                                   value="<?php echo e($search); ?>" class="form-input">
                        </div>
                        
                        <div class="filter-group">
                            <input type="date" name="date_from" value="<?php echo e($date_from); ?>" 
                                   class="form-input" placeholder="Дата от">
                        </div>
                        
                        <div class="filter-group">
                            <input type="date" name="date_to" value="<?php echo e($date_to); ?>" 
                                   class="form-input" placeholder="Дата до">
                        </div>
                        
                        <div class="filter-group">
                            <select name="status" class="form-select">
                                <option value="">Все статусы</option>
                                <option value="created" <?php echo $status === 'created' ? 'selected' : ''; ?>>Новый</option>
                                <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Оплачен</option>
                                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>В обработке</option>
                                <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Отправлен</option>
                                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Доставлен</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-filter"></i> Фильтровать
                        </button>
                        
                        <?php if ($search || $status || $date_from || $date_to): ?>
                            <a href="orders.php" class="btn btn-outline">Сбросить</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Таблица заказов -->
            <div class="table-section">
                <div class="table-header">
                    <h2>Заказы (<?php echo $totalOrders; ?>)</h2>
                    <div class="table-actions">
                        <button class="btn-action" title="Экспорт">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Номер</th>
                                <th>Клиент</th>
                                <th style="width: 120px;">Дата</th>
                                <th style="width: 100px;">Товары</th>
                                <th style="width: 120px;">Сумма</th>
                                <th style="width: 120px;">Статус</th>
                                <th style="width: 140px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="empty-table">
                                        <i class="fas fa-box-open"></i>
                                        <p>Заказы не найдены</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo $order['order_number']; ?></strong>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <strong><?php echo e($order['user_name']); ?></strong>
                                                <p><?php echo e($order['email']); ?></p>
                                                <small><?php echo e($order['shipping_address']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y', strtotime($order['created_at'])); ?><br>
                                            <small><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <?php echo $order['items_count']; ?> поз.<br>
                                            <small><?php echo $order['total_items']; ?> шт.</small>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($order['total_amount'], 0, '', ' '); ?> ₽</strong><br>
                                            <small><?php echo ucfirst($order['payment_method']); ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php 
                                                $statusText = [
                                                    'created' => 'Новый',
                                                    'paid' => 'Оплачен',
                                                    'processing' => 'Обработка',
                                                    'shipped' => 'Отправлен',
                                                    'delivered' => 'Доставлен',
                                                    'cancelled' => 'Отменен'
                                                ];
                                                echo $statusText[$order['status']] ?? $order['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" 
                                                   class="btn-action" title="Просмотр">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="orders.php?action=edit&id=<?php echo $order['id']; ?>" 
                                                   class="btn-action" title="Редактировать">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="orders.php?action=print&id=<?php echo $order['id']; ?>" 
                                                   target="_blank" class="btn-action" title="Печать">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Пагинация -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="orders.php?<?php 
                                $query = $_GET;
                                $query['page'] = $page - 1;
                                echo http_build_query($query);
                            ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Назад
                            </a>
                        <?php endif; ?>
                        
                        <div class="page-numbers">
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <a href="orders.php?<?php 
                                    $query = $_GET;
                                    $query['page'] = $i;
                                    echo http_build_query($query);
                                ?>" class="page-number <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="orders.php?<?php 
                                $query = $_GET;
                                $query['page'] = $page + 1;
                                echo http_build_query($query);
                            ?>" class="page-link">
                                Вперед <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
    function printOrders() {
        window.print();
    }
    </script>
    
    <style>
    @media print {
        .admin-sidebar,
        .admin-header,
        .stats-cards,
        .filters-section,
        .table-header,
        .action-buttons,
        .pagination {
            display: none !important;
        }
        
        .admin-content {
            margin-left: 0;
            max-width: 100%;
            padding: 20px;
        }
        
        .admin-table th,
        .admin-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
    }
    </style>
</body>
</html>