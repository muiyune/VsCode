<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireManager();

$action = $_GET['action'] ?? '';
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Фильтры
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$status = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Удаление товара
if ($action === 'delete' && $productId > 0) {
    if (verify_csrf_token($_GET['csrf_token'] ?? '')) {
        try {
            // Вместо удаления скрываем товар
            $stmt = $pdo->prepare("UPDATE products SET status = 'hidden' WHERE id = ?");
            $stmt->execute([$productId]);
            
            header("Location: products.php?success=Товар скрыт");
            exit();
        } catch (PDOException $e) {
            header("Location: products.php?error=Ошибка при скрытии товара");
            exit();
        }
    }
}

// Получение товаров с фильтрами
$where = [];
$params = [];

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($category) {
    $where[] = "p.category = ?";
    $params[] = $category;
}

if ($brand) {
    $where[] = "p.brand = ?";
    $params[] = $brand;
}

if ($status) {
    $where[] = "p.status = ?";
    $params[] = $status;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Получение товаров
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM product_sizes ps WHERE ps.product_id = p.id AND ps.quantity > 0) as total_sizes,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image
        FROM products p
        $whereClause
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $limitParams = array_merge($params, [$limit, $offset]);
    $stmt->execute($limitParams);
    $products = $stmt->fetchAll();
    
    // Общее количество
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM products p $whereClause");
    $countStmt->execute($params);
    $totalProducts = $countStmt->fetch()['total'];
    
    $totalPages = ceil($totalProducts / $limit);
    
    // Уникальные категории и бренды для фильтров
    $categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category")->fetchAll();
    $brands = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL ORDER BY brand")->fetchAll();
    
} catch (PDOException $e) {
    error_log("Products admin error: " . $e->getMessage());
    $products = [];
    $totalProducts = 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление товарами - ShoeStore</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-products.css">
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
                <h1>Управление товарами</h1>
                <div class="header-actions">
                    <a href="products.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Добавить товар
                    </a>
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

            <!-- Фильтры -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Поиск по названию..." 
                               value="<?php echo e($search); ?>" class="form-input">
                    </div>
                    
                    <div class="filter-group">
                        <select name="category" class="form-select">
                            <option value="">Все категории</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo e($cat['category']); ?>" 
                                    <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo e($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="brand" class="form-select">
                            <option value="">Все бренды</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?php echo e($b['brand']); ?>" 
                                    <?php echo $brand === $b['brand'] ? 'selected' : ''; ?>>
                                    <?php echo e($b['brand']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="status" class="form-select">
                            <option value="">Все статусы</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Активные</option>
                            <option value="hidden" <?php echo $status === 'hidden' ? 'selected' : ''; ?>>Скрытые</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> Фильтровать
                    </button>
                    
                    <?php if ($search || $category || $brand || $status): ?>
                        <a href="products.php" class="btn btn-outline">Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Таблица товаров -->
            <div class="table-section">
                <div class="table-header">
                    <h2>Товары (<?php echo $totalProducts; ?>)</h2>
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
                                <th style="width: 60px;">ID</th>
                                <th style="width: 80px;">Фото</th>
                                <th>Название</th>
                                <th style="width: 100px;">Категория</th>
                                <th style="width: 100px;">Бренд</th>
                                <th style="width: 100px;">Цена</th>
                                <th style="width: 100px;">Размеры</th>
                                <th style="width: 100px;">Статус</th>
                                <th style="width: 120px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="9" class="empty-table">
                                        <i class="fas fa-box-open"></i>
                                        <p>Товары не найдены</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>#<?php echo $product['id']; ?></td>
                                        <td>
                                            <div class="product-thumb">
                                                <img src="<?php echo $product['main_image'] ? '../' . $product['main_image'] : '../assets/images/no-image.jpg'; ?>" 
                                                     alt="<?php echo e($product['name']); ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo e($product['name']); ?></strong>
                                            <p class="product-description">
                                                <?php echo mb_substr(e($product['description'] ?? ''), 0, 50); ?>...
                                            </p>
                                        </td>
                                        <td><?php echo e($product['category']); ?></td>
                                        <td><?php echo e($product['brand']); ?></td>
                                        <td>
                                            <strong><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</strong>
                                            <?php if ($product['old_price']): ?>
                                                <br><small style="text-decoration: line-through; color: #999;">
                                                    <?php echo number_format($product['old_price'], 0, '', ' '); ?> ₽
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($product['total_sizes'] > 0): ?>
                                                <span class="badge badge-success"><?php echo $product['total_sizes']; ?> разм.</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Нет размеров</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $product['status']; ?>">
                                                <?php echo $product['status'] === 'active' ? 'Активен' : 'Скрыт'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="../product.php?id=<?php echo $product['id']; ?>" 
                                                   target="_blank" class="btn-action" title="Просмотр на сайте">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" 
                                                   class="btn-action" title="Редактировать">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="products.php?action=delete&id=<?php echo $product['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                   class="btn-action btn-danger" title="Скрыть"
                                                   onclick="return confirm('Скрыть этот товар?')">
                                                    <i class="fas fa-eye-slash"></i>
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
                            <a href="products.php?<?php 
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
                                <a href="products.php?<?php 
                                    $query = $_GET;
                                    $query['page'] = $i;
                                    echo http_build_query($query);
                                ?>" class="page-number <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="products.php?<?php 
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
    // Подтверждение удаления
    document.addEventListener('DOMContentLoaded', function() {
        const deleteLinks = document.querySelectorAll('.btn-danger');
        deleteLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Вы уверены, что хотите скрыть этот товар?')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>