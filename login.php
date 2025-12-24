<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/cart.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if(Auth::login($email, $password)) {
        // Слияние корзин
        $localCart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : null;
        if($localCart) {
            Cart::mergeCarts($_SESSION['user_id'], $localCart);
            setcookie('cart', '', time() - 3600, '/'); // Очищаем куки
        }
        
        header("Location: index.php");
        exit();
    } else {
        $error = "Неверный email или пароль";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <h2>Вход в систему</h2>
        
        <?php if($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
        
        <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
    </main>
</body>
</html>