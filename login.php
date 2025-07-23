<?php
require_once 'functions.php';

$error = '';
$success = '';

if ($_POST) {
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = '请填写所有字段';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">⛏️ MC Builder</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">🏠 首页</a></li>
                    <li><a href="register.php">📝 注册</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="main-content mc-border" style="max-width: 500px; margin: 50px auto;">
            <h1 style="text-align: center; margin-bottom: 30px; color: #FFD700;">🔑 玩家登录</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">🎮 用户名或邮箱</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="输入用户名或邮箱" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">🔒 密码</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="输入密码" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="remember" style="margin-right: 8px;">
                        记住我的登录状态
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        🚀 进入游戏
                    </button>
                </div>
            </form>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #444;">
                <p>还没有账号？ <a href="register.php" style="color: #FFD700;">立即注册</a></p>
                <p><a href="forgot-password.php" style="color: #AAAAAA; font-size: 0.9em;">忘记密码？</a></p>
            </div>
        </div>
    </div>
</body>
</html>
