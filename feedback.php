<?php
require_once 'functions.php';
global $pdo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = isLoggedIn() ? getCurrentUser() : null;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    if (mb_strlen($content) < 5) {
        $error = '反馈内容不能少于5个字';
    } else {
        $pdo->prepare("INSERT INTO feedback (user_id, username, content) VALUES (?, ?, ?)")
            ->execute([$user ? $user['id'] : null, $user ? $user['username'] : null, $content]);
        $success = '感谢您的反馈！我们会尽快处理。';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>问题反馈 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="main-content card" style="max-width:500px;margin:40px auto;">
        <h2 style="color:#FFD700;">问题反馈</h2>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>反馈内容</label>
                <textarea name="content" rows="5" required minlength="5"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">提交反馈</button>
        </form>
    </div>
</div>
</body>
</html> 