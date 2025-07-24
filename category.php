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
    <style>
        .cat-header {
            background: linear-gradient(90deg, #FFD700 0%, #FFB300 100%);
            color: #222;
            border-radius: 10px;
            padding: 30px 30px 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .cat-header .cat-icon {
            font-size: 3em;
            margin-right: 18px;
        }
        .cat-header .cat-info h1 {
            margin: 0 0 8px 0;
            font-size: 2em;
            color: #8B4513;
        }
        .cat-header .cat-info p {
            margin: 0;
            color: #444;
            font-size: 1.1em;
        }
        .thread-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .thread-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px 22px;
            min-width: 260px;
            flex: 1 1 320px;
            max-width: 420px;
            transition: box-shadow 0.2s;
            border: 1px solid #eee;
        }
        .thread-card:hover {
            box-shadow: 0 4px 16px rgba(139,69,19,0.13);
            border-color: #FFD700;
        }
        .thread-title {
            font-size: 1.15em;
            font-weight: bold;
            color: #8B4513;
            margin-bottom: 8px;
            text-decoration: none;
        }
        .thread-meta {
            color: #888;
            font-size: 0.98em;
        }
        .no-threads {
            color: #888;
            text-align: center;
            margin: 40px 0;
        }
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            background: #FFD700;
            color: #8B4513;
            padding: 8px 22px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .back-btn:hover {
            background: #8B4513;
            color: #FFD700;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="cat-header">
        <span class="cat-icon"><?php echo htmlspecialchars($category['icon']); ?></span>
        <div class="cat-info">
            <h1><?php echo htmlspecialchars($category['name']); ?></h1>
            <p><?php echo htmlspecialchars($category['description']); ?></p>
        </div>
    </div>
    <h2 style="color:#FFD700; margin-bottom:18px;">帖子列表</h2>
    <?php if (empty($threads)): ?>
        <div class="no-threads">该分类下暂无帖子。</div>
    <?php else: ?>
        <div class="thread-list">
        <?php foreach ($threads as $thread): ?>
            <div class="thread-card">
                <a class="thread-title" href="thread.php?id=<?php echo $thread['id']; ?>">
                    <?php echo htmlspecialchars($thread['title']); ?>
                </a>
                <div class="thread-meta">
                    by <?php echo htmlspecialchars($thread['username']); ?>
                    <span style="margin-left:10px;">创建于 <?php echo $thread['created_at']; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <a href="index.php" class="back-btn">返回首页</a>
</div>
<div class="cat-info">
    <h1><?php echo htmlspecialchars($category['name']); ?></h1>
    <p><?php echo htmlspecialchars($category['description']); ?></p>
    <?php if (isLoggedIn()): ?>
        <a href="post.php" class="btn btn-primary">发布新帖到本分类</a>
    <?php else: ?>
        <a href="login.php" class="btn btn-primary">登录后发布新帖</a>
    <?php endif; ?>
</div>
</body>
</html> 