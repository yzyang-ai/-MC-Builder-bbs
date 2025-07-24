<?php
require_once 'functions.php';
global $pdo;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: index.php');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>登录 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="main-content card" style="max-width:400px;margin:40px auto;">
        <h2 style="color:#FFD700;">登录</h2>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">登录</button>
            <a href="register.php" class="btn">注册新账号</a>
        </form>
    </div>
</div>
</body>
</html>
