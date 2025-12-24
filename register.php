<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (Auth::isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Валидация
    if (empty($email) || empty($password) || empty($name)) {
        $error = "Все обязательные поля должны быть заполнены";
    } elseif ($password !== $confirmPassword) {
        $error = "Пароли не совпадают";
    } elseif (strlen($password) < 6) {
        $error = "Пароль должен содержать минимум 6 символов";
    } else {
        // Проверка существующего email
        $db = Database::getInstance()->getConnection();
        $check = $db->query("SELECT id FROM users WHERE email = '" . $db->real_escape_string($email) . "'");
        
        if ($check->num_rows > 0) {
            $error = "Пользователь с таким email уже существует";
        } else {
            // Регистрация
            if (Auth::register($email, $password, $name, $phone)) {
                // Автоматический вход после регистрации
                Auth::login($email, $password);
                header("Location: index.php");
                exit();
            } else {
                $error = "Ошибка при регистрации";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <h2>Регистрация</h2>
        
        <?php if($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <div>
                <label for="name">Имя:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div>
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone">
            </div>
            
            <div>
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <div>
                <label for="confirm_password">Подтвердите пароль:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit">Зарегистрироваться</button>
        </form>
        
        <p>Уже есть аккаунт? <a href="login.php">Войдите</a></p>
    </main>
</body>
</html>