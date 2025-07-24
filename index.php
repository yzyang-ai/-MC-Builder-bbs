<?php
require_once 'functions.php';
global $pdo;

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
<div id="particles-js"></div>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
particlesJS('particles-js', {
  "particles": {
    "number": {"value": 60, "density": {"enable": true, "value_area": 800}},
    "color": {"value": "#DDA0DD"},
    "shape": {"type": "circle"},
    "opacity": {"value": 0.5, "random": true},
    "size": {"value": 3, "random": true},
    "line_linked": {"enable": true, "distance": 150, "color": "#A9A9A9", "opacity": 0.3, "width": 1},
    "move": {"enable": true, "speed": 2, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false}
  },
  "interactivity": {
    "detect_on": "canvas",
    "events": {"onhover": {"enable": true, "mode": "repulse"}, "onclick": {"enable": true, "mode": "push"}},
    "modes": {"repulse": {"distance": 100, "duration": 0.4}, "push": {"particles_nb": 4}}
  },
  "retina_detect": true
});
</script>
    <!-- 头部导航 -->
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">⛏️ MC Builder</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">🏠 首页</a></li>
                    <li><a href="recent.php">🆕 最新</a></li>
                    <li><a href="members.php">👥 成员</a></li>
                    <li><a href="feedback.php">💬 反馈</a></li>
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
        <?php
// 获取颜色设置
$colors = getColorSettings();
?>
<style>
:root {
    --primary-color: <?php echo $colors['主色调'] ?? '#FFD700'; ?>;
    --secondary-color: <?php echo $colors['辅助色'] ?? '#8B4513'; ?>;
    --background-color: <?php echo $colors['背景色'] ?? '#FFFFFF'; ?>;
    --text-color: <?php echo $colors['文本色'] ?? '#333333'; ?>;
    --link-color: <?php echo $colors['链接色'] ?? '#DDA0DD'; ?>;
}
</style>
    </header>

    <!-- 主要内容 -->
    <div class="container">
        <!-- 欢迎横幅 -->
        <div class="main-content card" style="text-align:center;">
            <h1 style="color:#FFD700; font-size:2.1em; margin-bottom:10px;">🎮 欢迎来到 MC Builder 论坛！</h1>
            <p style="color:#555; font-size:1.15em;">这里是 Minecraft 玩家的聚集地，分享建筑、讨论技巧、展示创意！</p>
            <div style="margin-top: 20px;">
                <span class="btn">👥 <?php echo $user_count; ?> 位玩家</span>
                <span class="btn">📝 <?php echo $thread_count; ?> 个帖子</span>
                <span class="btn">⭐ 活跃社区</span>
            </div>
        </div>

        <!-- 论坛分类 -->
        <div class="main-content card">
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
        <div class="main-content card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #FFD700;">🆕 最新帖子</h2>
                <a href="recent.php" class="btn">查看更多</a>
            </div>
            <!-- 最新帖子 -->
<div class="main-content card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: #FFD700;">🆕 最新帖子</h2>
        <div>
            <a href="recent.php" class="btn">查看更多</a>
            <?php if (isLoggedIn()): ?>
                <a href="post.php" class="btn btn-primary">发布新帖</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if (empty($latest_threads)): ?>
        <div style="color:#888; text-align:center;">暂无最新帖子。</div>
    <?php else: ?>
        <!-- 帖子列表内容 -->
    <?php endif; ?>
    </div>
            <?php if (empty($latest_threads)): ?>
                <div style="color:#888; text-align:center;">暂无最新帖子。</div>
            <?php else: ?>
                <div class="thread-list">
                <?php foreach ($latest_threads as $thread): ?>
                    <div class="thread-card">
                        <a class="thread-title" href="thread.php?id=<?php echo $thread['id']; ?>">
                            <?php echo htmlspecialchars($thread['title']); ?>
                        </a>
                        <div class="thread-meta">
                            [<?php echo htmlspecialchars($thread['category_name']); ?>]
                            by <?php echo htmlspecialchars($thread['username']); ?>
                            <span style="margin-left:10px;">创建于 <?php echo $thread['created_at']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
