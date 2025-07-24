<?php
require_once 'functions.php';
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$username = isset($_GET['username']) ? trim($_GET['username']) : '';
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$user_id]);
} elseif ($username) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
} else {
    header('Location: index.php');
    exit;
}
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "用户不存在";
    exit;
}
// 获取该用户发的主题
$stmt = $pdo->prepare("SELECT * FROM threads WHERE author_id=? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($user['username']); ?>的个人主页</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container" style="max-width:700px;margin:40px auto;">
    <div class="card" style="padding:30px;">
        <div style="display:flex;align-items:center;">
            <img src="images/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" style="width:80px;height:80px;border-radius:50%;margin-right:24px;">
            <div>
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <div>等级：<?php echo htmlspecialchars($user['user_level']); ?></div>
                <div>注册时间：<?php echo $user['join_date']; ?></div>
                <div>发帖数：<?php echo $user['posts_count']; ?></div>
                <?php if (isLoggedIn() && getCurrentUser()['id'] == $user['id']): ?>
                    <a href="upload_avatar.php" class="btn btn-primary" style="margin-top:10px;">上传/更换头像</a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($user['signature']): ?>
            <div style="margin-top:18px;color:#888;">签名：<?php echo nl2br(htmlspecialchars($user['signature'])); ?></div>
        <?php endif; ?>
    </div>
    <div class="card" style="margin-top:30px;padding:24px;">
        <h3>最新主题</h3>
        <?php if ($threads): ?>
            <ul>
                <?php foreach ($threads as $t): ?>
                    <li><a href="thread.php?id=<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['title']); ?></a> <span style="color:#888;">(<?php echo $t['created_at']; ?>)</span></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div style="color:#888;">暂无主题</div>
        <?php endif; ?>
    </div>
    <div class="level-requirements" style="margin-top:32px;">
        <h3 style="color:#FFD700;">等级升级要求</h3>
        <ul style="line-height:2; color:#fff;">
            <li>新手矿工：0 帖</li>
            <li>石器时代：10 帖</li>
            <li>铁器专家：30 帖</li>
            <li>钻石大师：80 帖</li>
            <li>红石工程师：200 帖</li>
            <li>建筑大师：仅限管理员/特殊身份</li>
        </ul>
    </div>
    <a href="index.php" class="btn" style="margin-top:30px;">返回首页</a>
</div>
</body>
</html> 