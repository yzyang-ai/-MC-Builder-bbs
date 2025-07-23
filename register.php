<?php
require_once 'functions.php';

$error = '';
$success = '';

if ($_POST) {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $mc_username = clean($_POST['mc_username']);
    
    // 验证输入
    if (empty($username) || empty($email) || empty($password)) {
        $error = '请填写所有必填字段';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = '用户名长度必须在3-20字符之间';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少6个字符';
    } elseif ($password !== $confirm_password) {
        $error = '两次输入的密码不一致';
    } else {
        // 检查用户名和邮箱是否已存在
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = '用户名或邮箱已被使用';
        } else {
            // 创建新用户
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, mc_username) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $mc_username])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                header('Location: index.php?welcome=1');
                exit;
            } else {
                $error = '注册失败，请稍后重试';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">⛏️ MC Builder</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">🏠 首页</a></li>
                    <li><a href="login.php">🔑 登录</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="main-content mc-border" style="max-width: 600px; margin: 50px auto;">
            <h1 style="text-align: center; margin-bottom: 30px; color: #FFD700;">🎮 加入 MC Builder</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="username">🎯 用户名 *</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               placeholder="3-20个字符" required maxlength="20"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="mc_username">⛏️ MC游戏用户名</label>
                        <input type="text" id="mc_username" name="mc_username" class="form-control" 
                               placeholder="你的Minecraft用户名"
                               value="<?php echo isset($_POST['mc_username']) ? htmlspecialchars($_POST['mc_username']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">📧 邮箱地址 *</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="your@email.com" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="password">🔒 密码 *</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="至少6个字符" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">🔐 确认密码 *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="再次输入密码" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" required style="margin-right: 8px;">
                        我已阅读并同意 <a href="terms.php" style="color: #FFD700;">用户协议</a> 和 <a href="privacy.php" style="color: #FFD700;">隐私政策</a>
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        🚀 开始建造之旅
                    </button>
                </div>
            </form>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #444;">
                <p>已经有账号了？ <a href="login.php" style="color: #FFD700;">立即登录</a></p>
            </div>
        </div>
    </div>

    <script>
        // 密码确认验证
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('密码不匹配');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
