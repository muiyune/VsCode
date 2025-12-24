<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$productId = (int)$_GET['id'];
$product = getProduct($productId);
$sizes = getProductSizes($productId);
$images = getProductImages($productId);

if(!$product) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <h2><?php echo $product['name']; ?></h2>
        
        <div class="product-detail">
            <?php if(!empty($images)): ?>
                <div class="product-images">
                    <?php foreach($images as $image): ?>
                        <img src="<?php echo $image['image_url']; ?>" alt="Изображение товара">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="product-info">
                <p><strong>Цена:</strong> <?php echo $product['price']; ?> руб.</p>
                <p><strong>Бренд:</strong> <?php echo $product['brand']; ?></p>
                <p><strong>Цвет:</strong> <?php echo $product['color']; ?></p>
                <p><strong>Материал:</strong> <?php echo $product['material']; ?></p>
                <p><strong>Описание:</strong> <?php echo $product['description']; ?></p>
                
                <form onsubmit="addToCartSubmit(event)">
                    <input type="hidden" id="product_id" value="<?php echo $product['id']; ?>">
                    
                    <label for="size">Размер:</label>
                    <select id="size" required>
                        <option value="">Выберите размер</option>
                        <?php foreach($sizes as $size): ?>
                            <option value="<?php echo $size['size']; ?>">
                                <?php echo $size['size']; ?> (в наличии: <?php echo $size['quantity']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="quantity">Количество:</label>
                    <input type="number" id="quantity" value="1" min="1" required>
                    
                    <button type="submit">Добавить в корзину</button>
                </form>
            </div>
        </div>
    </main>
    
    <script src="assets/js/main.js"></script>
    <script>
    function addToCartSubmit(event) {
        event.preventDefault();
        const productId = document.getElementById('product_id').value;
        const size = document.getElementById('size').value;
        const quantity = document.getElementById('quantity').value;
        
        if(size) {
            addToCart(productId, size, quantity);
        } else {
            alert('Выберите размер!');
        }
    }
    </script>
</body>
</html>