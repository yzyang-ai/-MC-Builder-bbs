<?php
require_once 'functions.php';
global $pdo;

// 获取所有用户
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY join_date DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>成员列表 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>👥 成员列表</h1>
    <?php if (empty($users)): ?>
        <p>暂无成员。</p>
    <?php else: ?>
        <ul>
        <?php foreach ($users as $user): ?>
            <li>
                <?php echo htmlspecialchars($user['username']); ?>
                (注册时间: <?php echo $user['join_date']; ?>)
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <a href="index.php" class="btn">返回首页</a>
</div>
</body>
</html> 