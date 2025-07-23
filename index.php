<?php
require_once 'functions.php';
global $pdo;
if (!$pdo) {
    die('数据库连接失败：请检查 config.php 的数据库配置、账号密码、数据库是否存在，以及主机 PDO/pdo_mysql 扩展是否启用。');
}

// 获取分类列表
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY sort_order ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取最新帖子
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.avatar, c.name as category_name 
    FROM threads t 
    JOIN users u ON t.author_id = u.id 
    JOIN categories c ON t.category_id = c.id 
    ORDER BY t.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$latest_threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取在线用户数
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
$stmt->execute();
$user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// 获取帖子总数
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM threads");
$stmt->execute();
$thread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Minecraft 玩家社区</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- 头部导航 -->
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">⛏️ MC Builder</a>
            
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">🏠 首页</a></li>
                    <li><a href="categories.php">📁 分类</a></li>
                    <li><a href="recent.php">🆕 最新</a></li>
                    <li><a href="members.php">👥 成员</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    <img src="images/avatars/<?php echo $user['avatar']; ?>" alt="头像" class="user-avatar">
                    <span>欢迎, <?php echo htmlspecialchars($user['username']); ?>!</span>
                    <a href="logout.php" class="btn">退出</a>
                <?php else: ?>
                    <a href="login.php" class="btn">登录</a>
                    <a href="register.php" class="btn btn-primary">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- 主要内容 -->
    <div class="container">
        <!-- 欢迎横幅 -->
        <div class="main-content mc-border">
            <h1>🎮 欢迎来到 MC Builder 论坛！</h1>
            <p>这里是 Minecraft 玩家的聚集地，分享建筑、讨论技巧、展示创意！</p>
            <strong>暂不支持上传图片，请上传至任意网站或网盘将链接发出（功能将在正式版上线添加）</strong>
            <p>当前论坛系统版本：v0.1 bate<br>更新预告：<br>1.添加上传图片功能<br>2.添加在线聊天室</p>
             <a href="http://bbs.yue910.xyz/feedback.php">问题反馈戳我</a>
            <div style="margin-top: 20px;">
                <span class="btn">👥 <?php echo $user_count; ?> 位玩家</span>
                <span class="btn">📝 <?php echo $thread_count; ?> 个帖子</span>
                <span class="btn">⭐ 不活跃社区</span>
            </div>
        </div>

        <!-- 论坛分类 -->
        <div class="main-content">
            <h2 style="margin-bottom: 20px; color: #FFD700;">📁 论坛分类</h2>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                <div class="category-card" onclick="location.href='category.php?id=<?php echo $category['id']; ?>'">
                    <span class="category-icon"><?php echo $category['icon']; ?></span>
                    <div class="category-title"><?php echo htmlspecialchars($category['name']); ?></div>
                    <div class="category-desc"><?php echo htmlspecialchars($category['description']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 最新帖子 -->
        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #FFD700;">🆕 最新帖子</h2>
                <?php if (isLoggedIn()): ?>
                    <a href="post.php" class="btn btn-primary">✏️ 发布帖子</a>
                <?php endif; ?>
            </div>
            
            <div class="thread-list">
                <?php if (empty($latest_threads)): ?>
                    <div style="text-align: center; padding: 40px; color: #AAAAAA;">
                        <h3>📝 还没有帖子</h3>
                        <p>成为第一个发布帖子的玩家吧！</p>
                        <?php if (isLoggedIn()): ?>
                            <a href="post.php" class="btn btn-primary" style="margin-top: 15px;">发布第一个帖子</a>
                        <?php else: ?>
                            <a href="register.php" class="btn" style="margin-top: 15px;">立即注册</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($latest_threads as $thread): ?>
                    <div class="thread-item">
                        <img src="images/avatars/<?php echo $thread['avatar']; ?>" alt="头像" class="thread-avatar">
                        <div class="thread-content">
                            <a href="thread.php?id=<?php echo $thread['id']; ?>" class="thread-title">
                                <?php echo htmlspecialchars($thread['title']); ?>
                            </a>
                            <div class="thread-meta">
                                由 <strong><?php echo htmlspecialchars($thread['username']); ?></strong> 
                                发布在 <strong><?php echo htmlspecialchars($thread['category_name']); ?></strong> 
                                • <?php echo timeAgo($thread['created_at']); ?>
                            </div>
                        </div>
                        <div class="thread-stats">
                            <div>👀 <?php echo $thread['views']; ?> 浏览</div>
                            <div>💬 <?php echo $thread['replies']; ?> 回复</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 社区统计 -->
        <div class="main-content">
            <h2 style="margin-bottom: 20px; color: #FFD700;">📊 社区统计</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="text-align: center; padding: 20px; background: rgba(139, 69, 19, 0.3); border-radius: 8px;">
                    <div style="font-size: 2em; margin-bottom: 10px;">👥</div>
                    <div style="font-size: 1.5em; font-weight: bold;"><?php echo $user_count; ?></div>
                    <div>注册玩家</div>
                </div>
                <div style="text-align: center; padding: 20px; background: rgba(139, 69, 19, 0.3); border-radius: 8px;">
                    <div style="font-size: 2em; margin-bottom: 10px;">📝</div>
                    <div style="font-size: 1.5em; font-weight: bold;"><?php echo $thread_count; ?></div>
                    <div>讨论帖子</div>
                </div>
                <div style="text-align: center; padding: 20px; background: rgba(139, 69, 19, 0.3); border-radius: 8px;">
                    <div style="font-size: 2em; margin-bottom: 10px;">💎</div>
                    <div style="font-size: 1.5em; font-weight: bold;">∞</div>
                    <div>创意无限</div>
                </div>
                <div style="text-align: center; padding: 20px; background: rgba(139, 69, 19, 0.3); border-radius: 8px;">
                    <div style="font-size: 2em; margin-bottom: 10px;">🌟</div>
                    <div style="font-size: 1.5em; font-weight: bold;">24/7</div>
                    <div>在线服务</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer style="background: rgba(26, 26, 26, 0.9); padding: 30px 0; margin-top: 50px; text-align: center; border-top: 4px solid #8B4513;">
        <div class="container">
            <p>&copy; 2025 <?php echo SITE_NAME; ?> - 一个充满创意的 Minecraft 社区</p>
            <p style="margin-top: 10px; color: #AAAAAA;">
                用 ❤️ 和 ⛏️ 为 Minecraft 玩家们打造
            </p>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
