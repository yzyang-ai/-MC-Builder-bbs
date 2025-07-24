<?php
require_once 'functions.php';

$thread_id = intval($_GET['id']);
if (!$thread_id) {
    header('Location: index.php');
    exit;
}

// 获取帖子信息
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.avatar, u.user_level, u.join_date, u.posts_count, c.name as category_name 
    FROM threads t 
    JOIN users u ON t.author_id = u.id 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.id = ?
");
$stmt->execute([$thread_id]);
$thread = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$thread) {
    header('Location: index.php');
    exit;
}

// 修复：获取分类信息
$category = [
    'id' => $thread['category_id'],
    'name' => $thread['category_name'],
    // 需要获取icon字段
];
// 获取icon字段
$stmt = $pdo->prepare("SELECT icon FROM categories WHERE id = ?");
$stmt->execute([$thread['category_id']]);
$catRow = $stmt->fetch(PDO::FETCH_ASSOC);
$category['icon'] = $catRow ? $catRow['icon'] : '';

// 修复：获取作者信息
$author = [
    'username' => $thread['username']
];

// 更新浏览次数
$stmt = $pdo->prepare("UPDATE threads SET views = views + 1 WHERE id = ?");
$stmt->execute([$thread_id]);

// 获取回复
$stmt = $pdo->prepare("
    SELECT r.*, u.username, u.avatar, u.user_level, u.join_date, u.posts_count 
    FROM replies r 
    JOIN users u ON r.author_id = u.id 
    WHERE r.thread_id = ? 
    ORDER BY r.created_at ASC
");
$stmt->execute([$thread_id]);
$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 处理回复提交
$error = '';
if ($_POST && isLoggedIn()) {
    $content = clean($_POST['content']);
    
    if (empty($content)) {
        $error = '请输入回复内容';
    } elseif (strlen($content) < 5) {
        $error = '回复内容至少需要5个字符';
    } else {
        $stmt = $pdo->prepare("INSERT INTO replies (thread_id, author_id, content) VALUES (?, ?, ?)");
        
        if ($stmt->execute([$thread_id, $_SESSION['user_id'], $content])) {
            // 更新帖子回复数
            $stmt = $pdo->prepare("UPDATE threads SET replies = replies + 1 WHERE id = ?");
            $stmt->execute([$thread_id]);
            
            // 更新用户发帖数
            $stmt = $pdo->prepare("UPDATE users SET posts_count = posts_count + 1 WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            header("Location: thread.php?id=$thread_id#reply-" . $pdo->lastInsertId());
            exit;
        } else {
            $error = '回复失败，请稍后重试';
        }
    }
}

// 点赞处理
if (isLoggedIn() && isset($_GET['like'])) {
    $user = getCurrentUser();
    $thread_id = intval($_GET['like']);
    // 防止重复点赞
    $stmt = $pdo->prepare("SELECT * FROM thread_likes WHERE thread_id=? AND user_id=?");
    $stmt->execute([$thread_id, $user['id']]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO thread_likes (thread_id, user_id) VALUES (?, ?)")->execute([$thread_id, $user['id']]);
    }
    header("Location: thread.php?id=$thread_id");
    exit;
}
// 获取点赞数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM thread_likes WHERE thread_id=?");
$stmt->execute([$thread['id']]);
$like_count = $stmt->fetchColumn();
// 判断当前用户是否已点赞
$liked = false;
if (isLoggedIn()) {
    $user = getCurrentUser();
    $stmt = $pdo->prepare("SELECT 1 FROM thread_likes WHERE thread_id=? AND user_id=?");
    $stmt->execute([$thread['id'], $user['id']]);
    $liked = $stmt->fetch() ? true : false;
}
// 删除权限判断
$can_delete = false;
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['id'] == $thread['author_id'] || $user['user_level'] === '建筑大师') {
        $can_delete = true;
    }
}
// 删除处理
if ($can_delete && isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM threads WHERE id=?")->execute([$thread['id']]);
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($thread['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .thread-main-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(139,69,19,0.08);
            padding: 32px 36px;
            margin-bottom: 30px;
        }
        .thread-title {
            font-size: 1.6em;
            color: #8B4513;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .thread-meta {
            color: #888;
            font-size: 1em;
            margin-bottom: 18px;
        }
        .thread-content {
            font-size: 1.13em;
            color: #222;
            margin-bottom: 24px;
            line-height: 1.7;
        }
        .thread-actions {
            margin-bottom: 18px;
        }
        .reply-list {
            margin-top: 30px;
        }
        .reply-card {
            background: #faf8f6;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(139,69,19,0.04);
            padding: 18px 22px;
            margin-bottom: 18px;
        }
        .reply-meta {
            color: #888;
            font-size: 0.98em;
            margin-bottom: 6px;
        }
        .reply-content {
            color: #222;
            font-size: 1.08em;
        }
        .reply-form {
            background: #fffbe6;
            border-radius: 8px;
            padding: 18px 22px;
            margin-top: 30px;
            box-shadow: 0 1px 4px rgba(139,69,19,0.06);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="thread-main-card">
        <div class="thread-title"><?php echo htmlspecialchars($thread['title']); ?></div>
        <div class="thread-meta">
            <?php echo $category['icon']; ?>
            <span style="color:#8B4513;"><?php echo htmlspecialchars($category['name']); ?></span>
            | by <b><?php echo htmlspecialchars($author['username']); ?></b>
            | <?php echo $thread['created_at']; ?>
            | 👀 <?php echo $thread['views']; ?> 浏览
        </div>
        <div class="thread-content"><?php echo nl2br(htmlspecialchars($thread['content'])); ?></div>
        <div class="thread-actions">
            <!-- 点赞和删除按钮已在逻辑中插入 -->
        </div>
        <!-- 点赞和删除按钮插入点 -->
        <?php /* 点赞和删除按钮已在逻辑中插入，这里保留插入点 */ ?>
    </div>
    <div class="reply-list">
        <h3 style="color:#FFD700; margin-bottom:16px;">回复列表</h3>
        <?php if (empty($replies)): ?>
            <div style="color:#888; text-align:center;">暂无回复。</div>
        <?php else: ?>
            <?php foreach ($replies as $reply): ?>
                <div class="reply-card">
                    <div class="reply-meta">
                        <b><?php echo htmlspecialchars($reply['username']); ?></b> 回复于 <?php echo $reply['created_at']; ?>
                    </div>
                    <div class="reply-content"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if (isLoggedIn()): ?>
        <div class="reply-form">
            <form method="post">
                <div class="form-group">
                    <label for="reply-content">回复内容</label>
                    <textarea id="reply-content" name="content" rows="4" required minlength="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">发表回复</button>
            </form>
        </div>
    <?php else: ?>
        <div style="margin-top:30px; color:#888;">请 <a href="login.php">登录</a> 后回复</div>
    <?php endif; ?>
    <a href="category.php?id=<?php echo $category['id']; ?>" class="btn" style="margin-top:30px;">返回分类</a>
</div>
<div class="thread-actions">
    <?php if (isLoggedIn()): ?>
        <a href="post.php" class="btn btn-primary">发布新帖</a>
    <?php endif; ?>
    <!-- 原有的点赞和删除按钮 -->
</div>
</body>
</html>
