<?php
// 开发环境下显示所有错误
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 数据库配置（请根据实际情况修改）
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '6b292740f809aaeb');
define('DB_NAME', '1');

define('SITE_NAME', 'MC Builder 论坛');
define('SITE_URL', 'http://localhost');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8mb4");
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

session_start();
?>