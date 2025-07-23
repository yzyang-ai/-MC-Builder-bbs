<?php
require_once '../functions.php';
global $pdo;
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND user_level = '建筑大师'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: index.php');
        exit;
    } else {
        $error = '用户名或密码错误，或无管理员权限';
    }
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>管理员登录 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-box { max-width: 400px; margin: 80px auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 0 10px #ccc; }
    </style>
</head>
<body>
<div class="login-box">
    <h2>后台管理员登录</h2>
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>用户名</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>密码</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">登录</button>
    </form>
</div>
</body>
</html> 