<?php
require_once 'functions.php';
global $pdo;

// 获取最新帖子
$stmt = $pdo->prepare("
    SELECT t.*, u.username, c.name as category_name 
    FROM threads t 
    JOIN users u ON t.author_id = u.id 
    JOIN categories c ON t.category_id = c.id 
    ORDER BY t.created_at DESC 
    LIMIT 30
");
$stmt->execute();
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>最新帖子 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>🆕 最新帖子</h1>
    <?php if (empty($threads)): ?>
        <p>暂无帖子。</p>
    <?php else: ?>
        <ul>
        <?php foreach ($threads as $thread): ?>
            <li>
                <a href="thread.php?id=<?php echo $thread['id']; ?>">
                    <?php echo htmlspecialchars($thread['title']); ?>
                </a>
                [<?php echo htmlspecialchars($thread['category_name']); ?>]
                by <?php echo htmlspecialchars($thread['username']); ?>
                (<?php echo $thread['created_at']; ?>)
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <a href="index.php" class="btn">返回首页</a>
</div>
</body>
</html> 