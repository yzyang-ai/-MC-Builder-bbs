<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO('mysql:host=localhost;dbname=1', '1', 'Drm3aS2sRBPBhT8b');
    echo '连接成功';
} catch (PDOException $e) {
    die('数据库连接失败: ' . $e->getMessage());
}