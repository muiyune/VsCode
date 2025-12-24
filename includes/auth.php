<?php
require_once 'database.php';

class Auth {
    public static function register($email, $password, $name, $phone = '') {
        $db = Database::getInstance()->getConnection();
        
        $email = $db->real_escape_string($email);
        $name = $db->real_escape_string($name);
        $phone = $db->real_escape_string($phone);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (email, password, name, phone, role) 
                VALUES ('$email', '$hashedPassword', '$name', '$phone', 'user')";
        
        if ($db->query($sql)) {
            return $db->insert_id;
        }
        return false;
    }
    
    public static function login($email, $password) {
        $db = Database::getInstance()->getConnection();
        
        $email = $db->real_escape_string($email);
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $db->query($sql);
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                return true;
            }
        }
        return false;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    public static function isManager() {
        return isset($_SESSION['user_role']) && 
               ($_SESSION['user_role'] === 'manager' || $_SESSION['user_role'] === 'admin');
    }
    
    public static function logout() {
        session_destroy();
    }
}
?>