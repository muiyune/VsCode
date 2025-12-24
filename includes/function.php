<?php
// Функция для безопасного вывода данных
function safe_output($string, $strip_tags = false) {
    if ($string === null) {
        return '';
    }
    
    $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    
    if ($strip_tags) {
        $string = strip_tags($string);
    }
    
    return $string;
}

// Функция для редиректа
function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

// Функция для форматирования цены
function format_price($price) {
    return number_format($price, 0, '', ' ') . ' ₽';
}

// Функция для получения текущего URL
function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// Функция для проверки email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Функция для проверки телефона
function is_valid_phone($phone) {
    return preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $phone);
}

// Функция для генерации случайной строки
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

// Функция для загрузки файла
function upload_file($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Ошибка загрузки файла'];
    }
    
    $file_name = basename($file['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Недопустимый тип файла'];
    }
    
    $new_file_name = uniqid() . '.' . $file_ext;
    $target_file = rtrim($target_dir, '/') . '/' . $new_file_name;
    
    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => false, 'message' => 'Ошибка сохранения файла'];
    }
    
    return [
        'success' => true,
        'filename' => $new_file_name,
        'path' => $target_file,
        'url' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $target_file)
    ];
}

// Функция для отображения ошибок
function display_error($message) {
    return '<div class="alert alert-error">' . safe_output($message) . '</div>';
}

// Функция для отображения успеха
function display_success($message) {
    return '<div class="alert alert-success">' . safe_output($message) . '</div>';
}

// Функция для получения параметров из GET/POST
function get_param($name, $default = '') {
    if (isset($_POST[$name])) {
        return $_POST[$name];
    } elseif (isset($_GET[$name])) {
        return $_GET[$name];
    }
    return $default;
}

// Функция для проверки AJAX запроса
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Функция для генерации CSRF токена
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Функция для проверки CSRF токена
function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Функция для логгирования
function log_message($message, $type = 'INFO') {
    $log_file = dirname(__DIR__) . '/logs/app.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$type] $message\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
?>