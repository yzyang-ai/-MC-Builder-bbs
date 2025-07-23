<?php
require_once 'config.php';
global $pdo;

// 检查用户是否登录
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 获取当前用户信息
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 清理HTML输入
function clean($input) {
    return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
}

// 格式化时间
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return '刚刚';
    if ($time < 3600) return floor($time/60) . '分钟前';
    if ($time < 86400) return floor($time/3600) . '小时前';
    if ($time < 2592000) return floor($time/86400) . '天前';
    
    return date('Y-m-d H:i', strtotime($datetime));
}

// 创建数据库表
function createTables() {
    global $pdo;
    if (!$pdo) {
        throw new Exception('数据库连接未初始化');
    }
    
    // 用户表
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        avatar VARCHAR(255) DEFAULT 'default_steve.png',
        join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        posts_count INT DEFAULT 0,
        mc_username VARCHAR(50),
        user_level ENUM('新手矿工', '石器时代', '铁器专家', '钻石大师', '红石工程师', '建筑大师') DEFAULT '新手矿工'
    )");
    
    // 论坛分类表
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(50),
        sort_order INT DEFAULT 0
    )");
    
    // 帖子表
    $pdo->exec("CREATE TABLE IF NOT EXISTS threads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        author_id INT,
        category_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        views INT DEFAULT 0,
        replies INT DEFAULT 0,
        is_pinned BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (author_id) REFERENCES users(id),
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )");
    
    // 回复表
    $pdo->exec("CREATE TABLE IF NOT EXISTS replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thread_id INT,
        author_id INT,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (thread_id) REFERENCES threads(id),
        FOREIGN KEY (author_id) REFERENCES users(id)
    )");
    
    // 插入默认分类
    $pdo->exec("INSERT IGNORE INTO categories (id, name, description, icon) VALUES 
        (1, '综合讨论', '关于Minecraft的一切话题', '🏠'),
        (2, '建筑展示', '展示你的精美建筑作品', '🏗️'),
        (3, '红石科技', '红石电路和自动化装置', '🔴'),
        (4, '生存攻略', '生存模式的技巧和心得', '⚔️'),
        (5, '模组推荐', 'MOD介绍和使用教程', '🔧'),
        (6, '服务器交流', '服务器宣传和玩家招募', '🌐')");
}

// 初始化数据库（建议只在安装流程中调用，不要自动执行）
// createTables();
?>
