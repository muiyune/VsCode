<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$products = getProducts($limit, $offset);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог обуви</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <h2>Каталог товаров</h2>
        
        <div class="products">
            <?php foreach($products as $product): ?>
                <div class="product-card">
                    <?php if(!empty($product['image_url'])): ?>
                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                    <?php endif; ?>
                    <h3><?php echo $product['name']; ?></h3>
                    <p>Цена: <?php echo $product['price']; ?> руб.</p>
                    <p>Бренд: <?php echo $product['brand']; ?></p>
                    <a href="product.php?id=<?php echo $product['id']; ?>">Подробнее</a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>">Назад</a>
            <?php endif; ?>
            <span>Страница <?php echo $page; ?></span>
            <?php if(count($products) == $limit): ?>
                <a href="?page=<?php echo $page+1; ?>">Вперед</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>