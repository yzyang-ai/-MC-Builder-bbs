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
    <style>
        .member-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .member-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(139,69,19,0.08);
            padding: 22px 24px;
            min-width: 200px;
            flex: 1 1 220px;
            max-width: 320px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            border: 1px solid #eee;
        }
        .member-name {
            font-size: 1.15em;
            font-weight: bold;
            color: #8B4513;
            margin-bottom: 6px;
        }
        .member-meta {
            color: #888;
            font-size: 0.98em;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="main-content card">
        <h2 style="color:#FFD700;">👥 成员列表</h2>
        <?php if (empty($users)): ?>
            <div style="color:#888; text-align:center;">暂无成员。</div>
        <?php else: ?>
            <div class="member-list">
            <?php foreach ($users as $user): ?>
                <div class="member-card">
                    <div class="member-name"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div class="member-meta">注册时间: <?php echo $user['join_date']; ?></div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a href="index.php" class="btn" style="margin-top:30px;">返回首页</a>
    </div>
</div>
</body>
</html> 