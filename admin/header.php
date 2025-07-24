<?php
require_once '../functions.php';
global $pdo;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 只允许管理员访问
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user = getCurrentUser();
if (!$user || $user['user_level'] !== '建筑大师') {
    die('无权限访问后台');
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>后台管理 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-nav { background: #222; padding: 10px; }
        .admin-nav a { color: #FFD700; margin-right: 20px; text-decoration: none; }
        .admin-nav a:hover { text-decoration: underline; }
        .admin-container { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 8px; padding: 30px; }
        .admin-container table,
        .admin-container th,
        .admin-container td {
            color: #222;
            background: #fff;
        }
    </style>
</head>
<body>
<div class="admin-nav">
    <a href="index.php">后台首页</a>
    <a href="users.php">用户管理</a>
    <a href="categories.php">分类管理</a>
    <a href="threads.php">帖子管理</a>
    <a href="replies.php">回复管理</a>
    <a href="feedback.php">问题反馈</a>
    <a href="colors.php">颜色设置</a> 
    <a href="smtp_settings.php">SMTP配置</a>
    <a href="logout.php">退出登录</a>
</div>
<div class="admin-container"> 
