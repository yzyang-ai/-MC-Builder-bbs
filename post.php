<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// 获取分类列表
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY sort_order ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_POST) {
    $title = clean($_POST['title']);
    $content = clean($_POST['content']);
    $category_id = intval($_POST['category_id']);
    
    if (empty($title) || empty($content) || empty($category_id)) {
        $error = '请填写所有字段';
    } elseif (strlen($title) < 5) {
        $error = '标题至少需要5个字符';
    } elseif (strlen($content) < 10) {
        $error = '内容至少需要10个字符';
    } else {
        $stmt = $pdo->prepare("INSERT INTO threads (title, content, author_id, category_id) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$title, $content, $_SESSION['user_id'], $category_id])) {
            // 更新用户发帖数
            $stmt = $pdo->prepare("UPDATE users SET posts_count = posts_count + 1 WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $thread_id = $pdo->lastInsertId();
            header("Location: thread.php?id=$thread_id");
            exit;
        } else {
            $error = '发布失败，请稍后重试';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>发布帖子 - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="recent.php">🆕 最新</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <?php $user = getCurrentUser(); ?>
                <img src="images/avatars/<?php echo $user['avatar']; ?>" alt="头像" class="user-avatar">
                <span><?php echo htmlspecialchars($user['username']); ?></span>
                <a href="logout.php" class="btn">退出</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="main-content mc-border">
            <h1 style="margin-bottom: 30px; color: #FFD700;">✏️ 发布新帖子</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="category_id">📁 选择分类 *</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">-- 请选择分类 --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo $category['icon'] . ' ' . htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="title">📝 帖子标题 *</label>
                    <input type="text" id="title" name="title" class="form-control" 
                           placeholder="用一句话描述你的帖子内容..." required maxlength="200"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    <small style="color: #AAAAAA;">至少5个字符，最多200个字符</small>
                </div>
                
                <div class="form-group">
                    <label for="content">📄 帖子内容 *</label>
                    <textarea id="content" name="content" class="form-control" rows="12" 
                              placeholder="详细描述你的想法、经验或问题...&#10;&#10;💡 小贴士：&#10;• 可以分享建筑截图和坐标&#10;• 可以讨论游戏技巧和心得&#10;• 可以求助或回答其他玩家的问题&#10;• 请保持友善和尊重" 
                              required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    <small style="color: #AAAAAA;">至少10个字符，支持换行</small>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                    <a href="index.php" class="btn">❌ 取消</a>
                    <button type="submit" class="btn btn-primary">🚀 发布帖子</button>
                </div>
            </form>
        </div>
        
        <!-- 发帖指南 -->
        <div class="main-content">
            <h3 style="color: #FFD700; margin-bottom: 15px;">📋 发帖指南</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="padding: 15px; background: rgba(139, 69, 19, 0.3); border-radius: 8px;">
                    <h4 style="color: #FFD700;">🏗️ 建筑展示</h4>
                    <p>分享你的建筑作品、提供坐标、介绍建造过程和使用的材料</p>
                </div>
                <div style="padding: 15px; background: rgba(139, 69, 19, 0.3); border-radius: 8px;">
                    <h4 style="color: #FFD700;">🔴 红石科技</h4>
                    <p>展示红石电路、自动化装置、机械原理和制作教程</p>
                </div>
                <div style="padding: 15px; background: rgba(139, 69, 19, 0.3); border-radius: 8px;">
                    <h4 style="color: #FFD700;">❓ 求助问题</h4>
                    <p>描述遇到的问题、提供相关截图、说明你已经尝试的解决方法</p>
                </div>
                <div style="padding: 15px; background: rgba(139, 69, 19, 0.3); border-radius: 8px;">
                    <h4 style="color: #FFD700;">💡 经验分享</h4>
                    <p>分享游戏技巧、生存心得、效率方法和实用建议</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 字符计数
        const titleInput = document.getElementById('title');
        const contentTextarea = document.getElementById('content');
        
        function updateCharCount(element, maxLength) {
            const remaining = maxLength - element.value.length;
            const color = remaining < 20 ? '#ff6b6b' : '#AAAAAA';
            
            let counter = element.nextElementSibling;
            if (counter && counter.tagName === 'SMALL') {
                counter.innerHTML = counter.innerHTML.split('，')[0] + `，还可输入 <span style="color: ${color}">${remaining}</span> 个字符`;
            }
        }
        
        titleInput.addEventListener('input', () => updateCharCount(titleInput, 200));
        contentTextarea.addEventListener('input', () => updateCharCount(contentTextarea, 5000));
    </script>
</body>
</html>
