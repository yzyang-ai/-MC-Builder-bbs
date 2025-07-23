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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($thread['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">⛏️ MC Builder</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">🏠 首页</a></li>
                    <li><a href="categories.php">📁 分类</a></li>
                    <li><a href="category.php?id=<?php echo $thread['category_id']; ?>">📂 <?php echo htmlspecialchars($thread['category_name']); ?></a></li>
                </ul>
            </nav>
            <div class="user-info">
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    <img src="images/avatars/<?php echo $user['avatar']; ?>" alt="头像" class="user-avatar">
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                    <a href="logout.php" class="btn">退出</a>
                <?php else: ?>
                    <a href="login.php" class="btn">登录</a>
                    <a href="register.php" class="btn btn-primary">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- 帖子主体 -->
        <div class="main-content mc-border">
            <div style="margin-bottom: 20px;">
                <span style="background: rgba(139, 69, 19, 0.6); padding: 5px 12px; border-radius: 15px; font-size: 0.9em;">
                    📂 <?php echo htmlspecialchars($thread['category_name']); ?>
                </span>
            </div>
            
            <h1 style="color: #FFD700; margin-bottom: 20px; line-height: 1.3;">
                <?php echo htmlspecialchars($thread['title']); ?>
            </h1>
            
            <div style="display: flex; gap: 20px; margin-bottom: 30px; padding: 20px; background: rgba(40, 40, 40, 0.5); border-radius: 8px;">
                <img src="images/avatars/<?php echo $thread['avatar']; ?>" alt="头像" 
                     style="width: 80px; height: 80px; border-radius: 8px; image-rendering: pixelated; border: 3px solid #8B4513;">
                
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <div>
                            <h3 style="color: #FFD700; margin-bottom: 5px;">
                                <?php echo htmlspecialchars($thread['username']); ?>
                            </h3>
                            <div style="color: #AAAAAA; font-size: 0.9em;">
                                🎯 <?php echo $thread['user_level']; ?> • 
                                📅 <?php echo date('Y年m月d日', strtotime($thread['join_date'])); ?>加入 • 
                                📝 共 <?php echo $thread['posts_count']; ?> 帖
                            </div>
                        </div>
                        <div style="text-align: right; color: #AAAAAA; font-size: 0.9em;">
                            <div>发布时间: <?php echo date('Y-m-d H:i', strtotime($thread['created_at'])); ?></div>
                            <div>👀 <?php echo $thread['views']; ?> 浏览 • 💬 <?php echo $thread['replies']; ?> 回复</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="background: rgba(26, 26, 26, 0.8); padding: 30px; border-radius: 8px; line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($thread['content'])); ?>
            </div>
            
            <?php if (isLoggedIn() && getCurrentUser()['id'] == $thread['author_id']): ?>
                <div style="text-align: right; margin-top: 15px;">
                    <a href="edit-thread.php?id=<?php echo $thread['id']; ?>" class="btn">✏️ 编辑</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- 在帖子内容下方插入点赞和删除按钮 -->
        <div style="margin:20px 0;">
            <form method="get" action="thread.php" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo $thread['id']; ?>">
                <?php if (isLoggedIn()): ?>
                    <?php if ($liked): ?>
                        <button type="button" class="btn" disabled>👍 已点赞 (<?php echo $like_count; ?>)</button>
                    <?php else: ?>
                        <button type="submit" name="like" value="<?php echo $thread['id']; ?>" class="btn btn-primary">👍 点赞 (<?php echo $like_count; ?>)</button>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="btn">👍 点赞 (<?php echo $like_count; ?>)</span>
                    <span style="color:#888;">（请登录后点赞）</span>
                <?php endif; ?>
            </form>
            <?php if ($can_delete): ?>
                <form method="get" action="thread.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $thread['id']; ?>">
                    <button type="submit" name="delete" value="1" class="btn btn-danger" onclick="return confirm('确定要删除该帖子吗？');">🗑️ 删除帖子</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- 回复列表 -->
        <?php if (!empty($replies)): ?>
            <div class="main-content">
                <h2 style="color: #FFD700; margin-bottom: 25px;">💬 回复 (<?php echo count($replies); ?>)</h2>
                
                <?php foreach ($replies as $index => $reply): ?>
                    <div id="reply-<?php echo $reply['id']; ?>" style="margin-bottom: 25px; padding: 25px; background: rgba(40, 40, 40, 0.7); border-radius: 8px; border-left: 4px solid #8B4513;">
                        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                            <img src="images/avatars/<?php echo $reply['avatar']; ?>" alt="头像" 
                                 style="width: 60px; height: 60px; border-radius: 6px; image-rendering: pixelated; border: 2px solid #8B4513;">
                            
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <h4 style="color: #FFD700;">
                                        <?php echo htmlspecialchars($reply['username']); ?>
                                    </h4>
                                    <span style="color: #AAAAAA; font-size: 0.9em;">
                                        #<?php echo $index + 1; ?> • <?php echo timeAgo($reply['created_at']); ?>
                                    </span>
                                </div>
                                <div style="color: #AAAAAA; font-size: 0.85em;">
                                    🎯 <?php echo $reply['user_level']; ?> • 📝 <?php echo $reply['posts_count']; ?> 帖
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: rgba(26, 26, 26, 0.6); padding: 20px; border-radius: 6px; line-height: 1.5;">
                            <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- 回复表单 -->
        <?php if (isLoggedIn()): ?>
            <div class="main-content mc-border">
                <h3 style="color: #FFD700; margin-bottom: 20px;">✏️ 发表回复</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <textarea name="content" class="form-control" rows="6" 
                                  placeholder="写下你的回复...&#10;&#10;💡 回复小贴士：&#10;• 保持友善和尊重&#10;• 提供有用的信息或建议&#10;• 可以@其他用户进行互动" 
                                  required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="color: #AAAAAA; font-size: 0.9em;">
                            💭 以 <strong><?php echo htmlspecialchars(getCurrentUser()['username']); ?></strong> 的身份回复
                        </div>
                        <button type="submit" class="btn btn-primary">🚀 发表回复</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="main-content" style="text-align: center; padding: 40px;">
                <h3 style="color: #FFD700; margin-bottom: 15px;">💬 参与讨论</h3>
                <p style="margin-bottom: 20px; color: #AAAAAA;">登录后即可回复帖子，参与社区讨论</p>
                <a href="login.php" class="btn btn-primary">🔑 立即登录</a>
                <a href="register.php" class="btn">📝 免费注册</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // 平滑滚动到指定回复
        if (window.location.hash) {
            setTimeout(function() {
                const element = document.querySelector(window.location.hash);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    element.style.background = 'rgba(255, 215, 0, 0.1)';
                    setTimeout(() => {
                        element.style.background = '';
                    }, 3000);
                }
            }, 100);
        }
    </script>
</body>
</html>
