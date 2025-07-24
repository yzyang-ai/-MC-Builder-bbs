<?php
require_once 'functions.php';
global $pdo;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if (mb_strlen($username) < 3) {
        $error = '用户名至少3个字符';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } elseif (mb_strlen($password) < 6) {
        $error = '密码至少6个字符';
    } elseif ($password !== $password2) {
        $error = '两次输入的密码不一致';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = '用户名或邮箱已被注册';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)")
                ->execute([$username, $email, $hashed]);
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>注册 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="main-content card" style="max-width:500px;margin:40px auto;">
        <h2 style="color:#FFD700;">注册新账号</h2>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required minlength="3">
            </div>
            <div class="form-group">
                <label for="email">邮箱</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="password2">确认密码</label>
                <input type="password" id="password2" name="password2" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">注册</button>
            <a href="login.php" class="btn">返回登录</a>
        </form>
    </div>
</div>
</body>
</html>
