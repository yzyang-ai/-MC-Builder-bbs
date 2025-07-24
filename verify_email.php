<?php
require_once 'functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['pending_verify_email'])) {
    header('Location: login.php');
    exit;
}
$email = $_SESSION['pending_verify_email'];
$err = '';
$success = false;
$resend_msg = '';
$resend_log = '';
// 处理重新发送验证码
if (isset($_POST['resend'])) {
    if (!isset($_SESSION['last_resend']) || time() - $_SESSION['last_resend'] > 60) {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expire = date('Y-m-d H:i:s', time() + 600); // 10分钟有效
        $stmt = $pdo->prepare("UPDATE users SET email_verification_code=?, email_verification_expire=? WHERE email=?");
        $stmt->execute([$code, $expire, $email]);
        $mail_body = "感谢注册论坛账号\n您的验证码是" . $code;
        $resend_log = sendMail($email, '您的邮箱验证码', $mail_body);
        $_SESSION['last_resend'] = time();
        $resend_msg = '验证码已重新发送，请查收邮箱。';
    } else {
        $resend_msg = '请勿频繁操作，请稍后再试。';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = trim($_POST['code'] ?? '');
    $stmt = $pdo->prepare("SELECT id, email_verification_code, email_verification_expire FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $err = '用户不存在';
    } elseif (!$user['email_verification_code'] || !$user['email_verification_expire'] || strtotime($user['email_verification_expire']) < time()) {
        $err = '验证码已过期，请重新发送验证码。';
    } elseif ($code !== $user['email_verification_code']) {
        $err = '验证码错误，请重试。';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET email_verified=1, email_verification_code=NULL, email_verification_expire=NULL WHERE id=?");
        $stmt->execute([$user['id']]);
        unset($_SESSION['pending_verify_email']);
        unset($_SESSION['last_resend']);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>邮箱验证</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .verify-box { max-width: 400px; margin: 60px auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px #eee; }
        .verify-box h2 { margin-bottom: 20px; }
        .verify-box input { width: 100%; padding: 10px; margin-bottom: 16px; }
        .verify-box button { width: 100%; padding: 10px; }
        .msg { color: #d48806; margin-bottom: 10px; }
        .err { color: #f5222d; margin-bottom: 10px; }
        .success { color: #52c41a; margin-bottom: 10px; }
        .resend-btn { width: 100%; margin-top: 10px; background: #FFD700; color: #222; border-radius: 6px; border: none; padding: 10px; font-weight: bold; cursor: pointer; }
        .resend-btn:disabled { background: #eee; color: #aaa; cursor: not-allowed; }
    </style>
</head>
<body>
<div class="verify-box">
    <h2>邮箱验证</h2>
    <div class="msg">验证码已发送至邮箱：<?php echo htmlspecialchars($email); ?></div>
    <?php if ($resend_msg): ?><div class="msg"><?php echo $resend_msg; ?></div><?php endif; ?>
    <?php if ($resend_log): ?><div class="msg"><pre style="font-size:12px;background:#222;color:#fff;padding:8px 12px;border-radius:6px;overflow:auto;"><?php echo htmlspecialchars($resend_log); ?></pre></div><?php endif; ?>
    <?php if ($err): ?><div class="err"><?php echo $err; ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="success">验证成功！<a href="login.php">点击登录</a></div>
    <?php else: ?>
    <form method="post">
        <input type="text" name="code" maxlength="6" placeholder="请输入6位验证码" required autofocus>
        <button type="submit">验证</button>
    </form>
    <form method="post" style="margin-top:10px;">
        <button type="submit" name="resend" class="resend-btn" <?php if(isset($_SESSION['last_resend']) && time() - $_SESSION['last_resend'] < 60) echo 'disabled'; ?>>重新发送验证码<?php if(isset($_SESSION['last_resend']) && time() - $_SESSION['last_resend'] < 60) echo '（'.(60-(time()-$_SESSION['last_resend'])).'秒后可重试）'; ?></button>
    </form>
    <?php endif; ?>
</div>
</body>
</html> 