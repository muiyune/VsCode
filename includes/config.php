<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Старт сессии
session_start();

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'shoe_store');
define('DB_USER', 'root');
define('DB_PASS', ''); // По умолчанию в XAMPP пароль пустой
define('BASE_URL', 'http://localhost/kond/');

// Автозагрузка классов (упрощенная версия)
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Подключаем основные файлы
require_once 'database.php';
require_once 'auth.php';
require_once 'cart.php';
require_once 'functions.php';
?>