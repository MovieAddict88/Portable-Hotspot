<?php
// Auto-generated config file
define('DB_HOST', 'sql100.infinityfree.com');
define('DB_NAME', 'if0_40117343_hotspot');
define('DB_USER', 'if0_40117343');
define('DB_PASS', 'rW0LYMue4MQkP');
define('SECRET_KEY', '8888');

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>