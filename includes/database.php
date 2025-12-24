<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new mysqli('localhost', 'root', '', 'shoe_store');
            if ($this->connection->connect_error) {
                throw new Exception("Ошибка подключения: " . $this->connection->connect_error);
            }
            $this->connection->set_charset("utf8");
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Создаем глобальный экземпляр для обратной совместимости
$GLOBALS['db'] = Database::getInstance()->getConnection();
?>