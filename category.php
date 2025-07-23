<?php
require_once 'functions.php';
global $pdo;

if (!isset($_GET['id'])) {
    die('缺少分类ID');
}
$category_id = intval($_GET['id']);

// 查询分类信息
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$category) {
    die('分类不存在');
}

// 查询该分类下的帖子
$stmt = $pdo->prepare("
    SELECT t.*, u.username 
    FROM threads t 
    JOIN users u ON t.author_id = u.id 
    WHERE t.category_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$category_id]);
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($category['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1><?php echo htmlspecialchars($category['name']); ?></h1>
    <p><?php echo htmlspecialchars($category['description']); ?></p>
    <h2>帖子列表</h2>
    <?php if (empty($threads)): ?>
        <p>该分类下暂无帖子。</p>
    <?php else: ?>
        <ul>
        <?php foreach ($threads as $thread): ?>
            <li>
                <a href="thread.php?id=<?php echo $thread['id']; ?>">
                    <?php echo htmlspecialchars($thread['title']); ?>
                </a>
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