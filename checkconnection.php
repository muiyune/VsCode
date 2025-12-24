<?php
require_once 'includes/database.php';

$db = Database::getInstance()->getConnection();

// Проверяем какая база данных используется
$result = $db->query("SELECT DATABASE() as db_name");
$row = $result->fetch_assoc();

echo "Используемая база данных: " . ($row['db_name'] ?? 'не определена') . "<br>";

// Проверяем таблицы в этой базе
$result = $db->query("SHOW TABLES");
echo "Таблицы в базе:<br>";
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "<br>";
}
?>