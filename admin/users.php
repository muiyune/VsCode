<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!Auth::isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_role':
            $userId = (int)$_POST['user_id'];
            $role = $_POST['role'];
            
            $db->query("UPDATE users SET role = '$role' WHERE id = $userId");
            break;
            
        case 'delete':
            $userId = (int)$_POST['user_id'];
            $db->query("DELETE FROM users WHERE id = $userId");
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .users-table th, .users-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        .users-table th {
            background: #f2f2f2;
        }
        
        .role-user { color: #28a745; }
        .role-manager { color: #ffc107; }
        .role-admin { color: #dc3545; }
        
        .role-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .role-form select, .role-form button {
            padding: 5px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Управление пользователями</h1>
        <nav>
            <a href="../index.php">На сайт</a>
            <a href="dashboard.php">Дашборд</a>
            <a href="products.php">Товары</a>
            <a href="users.php">Пользователи</a>
            <a href="../logout.php">Выйти</a>
        </nav>
    </header>
    
    <main>
        <h2>Список пользователей</h2>
        
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Роль</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $db->query("
                    SELECT * FROM users 
                    ORDER BY created_at DESC
                ");
                
                while($user = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                        <td class="role-<?php echo $user['role']; ?>">
                            <?php 
                            $roleNames = [
                                'user' => 'Пользователь',
                                'manager' => 'Менеджер',
                                'admin' => 'Администратор'
                            ];
                            echo $roleNames[$user['role']];
                            ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" class="role-form">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="role">
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Пользователь</option>
                                        <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Менеджер</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Администратор</option>
                                    </select>
                                    <button type="submit">Изменить</button>
                                </form>
                                
                                <form method="POST" style="margin-top: 5px;" onsubmit="return confirm('Удалить пользователя?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                                        Удалить
                                    </button>
                                </form>
                            <?php else: ?>
                                <em>(это вы)</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>