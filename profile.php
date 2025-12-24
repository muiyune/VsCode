<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $db->real_escape_string($_POST['name']);
    $phone = $db->real_escape_string($_POST['phone']);
    
    // Если меняется пароль
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        
        // Проверяем текущий пароль
        $user = $db->query("SELECT password FROM users WHERE id = $userId")->fetch_assoc();
        
        if (password_verify($currentPassword, $user['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password = '$hashedPassword' WHERE id = $userId");
            $passwordMessage = "Пароль успешно изменен";
        } else {
            $passwordError = "Неверный текущий пароль";
        }
    }
    
    // Обновляем профиль
    $db->query("UPDATE users SET name = '$name', phone = '$phone' WHERE id = $userId");
    
    // Обновляем сессию
    $_SESSION['user_name'] = $name;
    
    $message = "Профиль успешно обновлен";
}

// Получаем данные пользователя
$user = $db->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <h2>Мой профиль</h2>
        
        <?php if(isset($message)): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($passwordMessage)): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px;">
                <?php echo $passwordMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($passwordError)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">
                <?php echo $passwordError; ?>
            </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
                <h3>Личные данные</h3>
                <form method="POST">
                    <div>
                        <label for="name">Имя:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div>
                        <label for="email">Email (неизменяем):</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <small>Email нельзя изменить</small>
                    </div>
                    
                    <div>
                        <label for="phone">Телефон:</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label>Роль:</label>
                        <input type="text" value="<?php 
                            $roleNames = [
                                'user' => 'Пользователь',
                                'manager' => 'Менеджер',
                                'admin' => 'Администратор'
                            ];
                            echo $roleNames[$user['role']];
                        ?>" disabled>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit">Сохранить изменения</button>
                    </div>
                </form>
            </div>
            
            <div>
                <h3>Смена пароля</h3>
                <form method="POST">
                    <div>
                        <label for="current_password">Текущий пароль:</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    
                    <div>
                        <label for="new_password">Новый пароль:</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    
                    <div>
                        <label for="confirm_password">Подтвердите новый пароль:</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit">Сменить пароль</button>
                    </div>
                </form>
                
                <div style="margin-top: 30px;">
                    <h3>Быстрые ссылки</h3>
                    <ul>
                        <li><a href="orders.php">Мои заказы</a></li>
                        <li><a href="cart.php">Корзина</a></li>
                        <?php if(Auth::isManager()): ?>
                            <li><a href="admin/dashboard.php">Панель управления</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</body>
</html>