<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!Auth::isAdmin() && !Auth::isManager()) {
    header("Location: ../login.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'add':
            $name = $db->real_escape_string($_POST['name']);
            $description = $db->real_escape_string($_POST['description']);
            $category = $db->real_escape_string($_POST['category']);
            $brand = $db->real_escape_string($_POST['brand']);
            $price = (float)$_POST['price'];
            $color = $db->real_escape_string($_POST['color']);
            $material = $db->real_escape_string($_POST['material']);
            
            $sql = "INSERT INTO products (name, description, category, brand, price, color, material) 
                    VALUES ('$name', '$description', '$category', '$brand', $price, '$color', '$material')";
            
            if ($db->query($sql)) {
                $productId = $db->insert_id;
                
                // Добавление размеров
                if (isset($_POST['sizes'])) {
                    foreach ($_POST['sizes'] as $sizeData) {
                        $size = (float)$sizeData['size'];
                        $quantity = (int)$sizeData['quantity'];
                        
                        $db->query("
                            INSERT INTO product_sizes (product_id, size, quantity) 
                            VALUES ($productId, $size, $quantity)
                        ");
                    }
                }
                
                // Загрузка изображений
                if (!empty($_FILES['images'])) {
                    $uploadDir = '../assets/images/products/';
                    
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileName = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                            $filePath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($tmpName, $filePath)) {
                                $isMain = ($key === 0) ? 1 : 0;
                                $imageUrl = 'assets/images/products/' . $fileName;
                                
                                $db->query("
                                    INSERT INTO product_images (product_id, image_url, is_main) 
                                    VALUES ($productId, '$imageUrl', $isMain)
                                ");
                            }
                        }
                    }
                }
                
                header("Location: products.php?message=Товар успешно добавлен");
                exit();
            }
            break;
            
        case 'update':
            $productId = (int)$_POST['product_id'];
            $name = $db->real_escape_string($_POST['name']);
            $description = $db->real_escape_string($_POST['description']);
            $category = $db->real_escape_string($_POST['category']);
            $brand = $db->real_escape_string($_POST['brand']);
            $price = (float)$_POST['price'];
            $color = $db->real_escape_string($_POST['color']);
            $material = $db->real_escape_string($_POST['material']);
            $status = $_POST['status'] === 'active' ? 'active' : 'hidden';
            
            $sql = "UPDATE products SET 
                    name = '$name',
                    description = '$description',
                    category = '$category',
                    brand = '$brand',
                    price = $price,
                    color = '$color',
                    material = '$material',
                    status = '$status'
                    WHERE id = $productId";
            
            $db->query($sql);
            
            // Обновление размеров
            if (isset($_POST['sizes'])) {
                // Удаляем старые размеры
                $db->query("DELETE FROM product_sizes WHERE product_id = $productId");
                
                // Добавляем новые
                foreach ($_POST['sizes'] as $sizeData) {
                    $size = (float)$sizeData['size'];
                    $quantity = (int)$sizeData['quantity'];
                    
                    if ($quantity > 0) {
                        $db->query("
                            INSERT INTO product_sizes (product_id, size, quantity) 
                            VALUES ($productId, $size, $quantity)
                        ");
                    }
                }
            }
            
            header("Location: products.php?message=Товар успешно обновлен");
            exit();
            break;
            
        case 'delete':
            $productId = (int)$_POST['product_id'];
            $db->query("DELETE FROM products WHERE id = $productId");
            header("Location: products.php?message=Товар удален");
            exit();
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление товарами</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .product-form {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .size-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .size-row input {
            flex: 1;
        }
        
        .product-list {
            display: grid;
            gap: 20px;
        }
        
        .product-item {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
        }
        
        .status-active { color: green; }
        .status-hidden { color: orange; }
    </style>
</head>
<body>
    <header>
        <h1>Управление товарами</h1>
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
        <?php if(isset($_GET['message'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px;">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if($action === 'add' || $action === 'edit'): ?>
            <h2><?php echo $action === 'add' ? 'Добавить товар' : 'Редактировать товар'; ?></h2>
            
            <?php
            $product = null;
            if ($action === 'edit' && isset($_GET['id'])) {
                $productId = (int)$_GET['id'];
                $product = $db->query("SELECT * FROM products WHERE id = $productId")->fetch_assoc();
                
                if ($product) {
                    $sizes = $db->query("SELECT * FROM product_sizes WHERE product_id = $productId");
                }
            }
            ?>
            
            <form method="POST" enctype="multipart/form-data" class="product-form">
                <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'add' : 'update'; ?>">
                <?php if($action === 'edit'): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Название товара:</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo $product['name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Описание:</label>
                    <textarea id="description" name="description" rows="4" required><?php echo $product['description'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Категория:</label>
                    <select id="category" name="category" required>
                        <option value="">Выберите категорию</option>
                        <option value="Мужская" <?php echo ($product['category'] ?? '') === 'Мужская' ? 'selected' : ''; ?>>Мужская</option>
                        <option value="Женская" <?php echo ($product['category'] ?? '') === 'Женская' ? 'selected' : ''; ?>>Женская</option>
                        <option value="Детская" <?php echo ($product['category'] ?? '') === 'Детская' ? 'selected' : ''; ?>>Детская</option>
                        <option value="Унисекс" <?php echo ($product['category'] ?? '') === 'Унисекс' ? 'selected' : ''; ?>>Унисекс</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="brand">Бренд:</label>
                    <input type="text" id="brand" name="brand" required 
                           value="<?php echo $product['brand'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="price">Цена (руб.):</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required 
                           value="<?php echo $product['price'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="color">Цвет:</label>
                    <input type="text" id="color" name="color" 
                           value="<?php echo $product['color'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="material">Материал:</label>
                    <input type="text" id="material" name="material" 
                           value="<?php echo $product['material'] ?? ''; ?>">
                </div>
                
                <?php if($action === 'edit'): ?>
                    <div class="form-group">
                        <label>Статус:</label>
                        <select name="status">
                            <option value="active" <?php echo ($product['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Активен</option>
                            <option value="hidden" <?php echo ($product['status'] ?? '') === 'hidden' ? 'selected' : ''; ?>>Скрыт</option>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Размеры и количество:</label>
                    <div id="sizes-container">
                        <?php if(isset($sizes) && $sizes->num_rows > 0): ?>
                            <?php while($size = $sizes->fetch_assoc()): ?>
                                <div class="size-row">
                                    <input type="number" name="sizes[][size]" step="0.5" placeholder="Размер" 
                                           value="<?php echo $size['size']; ?>" required>
                                    <input type="number" name="sizes[][quantity]" placeholder="Количество" min="0" 
                                           value="<?php echo $size['quantity']; ?>" required>
                                    <button type="button" class="remove-size">×</button>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="size-row">
                                <input type="number" name="sizes[][size]" step="0.5" placeholder="Размер" required>
                                <input type="number" name="sizes[][quantity]" placeholder="Количество" min="0" required>
                                <button type="button" class="remove-size">×</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-size">+ Добавить размер</button>
                </div>
                
                <?php if($action === 'add'): ?>
                    <div class="form-group">
                        <label for="images">Изображения (минимум 1, максимум 5):</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" required>
                        <small>Первое загруженное изображение будет главным</small>
                    </div>
                <?php endif; ?>
                
                <button type="submit"><?php echo $action === 'add' ? 'Добавить товар' : 'Сохранить изменения'; ?></button>
                <a href="products.php">Отмена</a>
            </form>
            
            <script>
            document.getElementById('add-size').addEventListener('click', function() {
                const container = document.getElementById('sizes-container');
                const newRow = document.createElement('div');
                newRow.className = 'size-row';
                newRow.innerHTML = `
                    <input type="number" name="sizes[][size]" step="0.5" placeholder="Размер" required>
                    <input type="number" name="sizes[][quantity]" placeholder="Количество" min="0" required>
                    <button type="button" class="remove-size">×</button>
                `;
                container.appendChild(newRow);
            });
            
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-size')) {
                    e.target.closest('.size-row').remove();
                }
            });
            </script>
            
        <?php else: ?>
            <h2>Список товаров</h2>
            
            <div style="margin: 20px 0;">
                <a href="?action=add" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">
                    + Добавить товар
                </a>
            </div>
            
            <?php
            $result = $db->query("
                SELECT p.*, 
                       (SELECT GROUP_CONCAT(CONCAT(size, ':', quantity) SEPARATOR '; ') 
                        FROM product_sizes ps 
                        WHERE ps.product_id = p.id) as sizes_info
                FROM products p 
                ORDER BY p.created_at DESC
            ");
            ?>
            
            <div class="product-list">
                <?php while($product = $result->fetch_assoc()): ?>
                    <div class="product-item">
                        <div>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p><strong>Бренд:</strong> <?php echo htmlspecialchars($product['brand']); ?></p>
                            <p><strong>Цена:</strong> <?php echo $product['price']; ?> руб.</p>
                            <p><strong>Размеры:</strong> <?php echo $product['sizes_info'] ?? 'Нет в наличии'; ?></p>
                            <p><strong>Статус:</strong> 
                                <span class="status-<?php echo $product['status']; ?>">
                                    <?php echo $product['status'] === 'active' ? 'Активен' : 'Скрыт'; ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="product-actions">
                            <a href="?action=edit&id=<?php echo $product['id']; ?>" 
                               style="padding: 5px 10px; background: #28a745; color: white; text-decoration: none; border-radius: 3px;">
                                Редактировать
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить этот товар?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" style="padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;">
                                    Удалить
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>