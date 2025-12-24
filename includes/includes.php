<?php
session_start();

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'shoestore_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Настройки сайта
define('SITE_NAME', 'ShoeStore');
define('SITE_URL', 'http://localhost/shoestore/');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/shoestore/uploads/');

// Настройки безопасности
define('CSRF_TOKEN_LIFE', 3600); // 1 час

// Автозагрузка классов
spl_autoload_register(function ($className) {
    $file = __DIR__ . '/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// Функция проверки CSRF токена
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'], $_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFE) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Функция для безопасного вывода
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Подключение к базе данных
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>