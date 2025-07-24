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
// 获取配色设置
function getColorSettings() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'colors'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $default = [
        'primary' => '#FFD700',
        'background' => '#fff',
        'button' => '#8B4513'
    ];
    return $row ? json_decode($row['setting_value'], true) : $default;
}

// 获取单个颜色
function getColor($name) {
    $colors = getColorSettings();
    return isset($colors[$name]) ? $colors[$name] : '#000000';
}

function sendMail($to, $subject, $body) {
    global $pdo;
    $log = '';
    // 获取SMTP配置
    $settings = [];
    $res = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'smtp_%'");
    foreach ($res as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $host = $settings['smtp_host'] ?? '';
    $port = $settings['smtp_port'] ?? 25;
    $user = $settings['smtp_user'] ?? '';
    $pass = $settings['smtp_pass'] ?? '';
    $from = $settings['smtp_from'] ?? '';
    $secure = $settings['smtp_secure'] ?? '';
    $log .= "SMTP服务器: $host:$port\n";
    $log .= "发件人: $from\n";
    $log .= "收件人: $to\n";
    if (file_exists(__DIR__.'/PHPMailer/PHPMailer.php')) {
        require_once __DIR__.'/PHPMailer/PHPMailer.php';
        require_once __DIR__.'/PHPMailer/SMTP.php';
        require_once __DIR__.'/PHPMailer/Exception.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        if ($secure) $mail->SMTPSecure = $secure;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($from);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body;
        if (!$mail->send()) {
            $log .= "发送失败: " . $mail->ErrorInfo . "\n";
        } else {
            $log .= "发送成功\n";
        }
        return $log;
    } else {
        $headers = "From: $from\r\nContent-Type: text/html; charset=UTF-8";
        $result = mail($to, $subject, $body, $headers);
        if (!$result) {
            $log .= "mail() 发送失败\n";
        } else {
            $log .= "mail() 发送成功\n";
        }
        return $log;
    }
}

function getEmailVerificationEnabled() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'enable_email_verification'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return isset($row['setting_value']) && $row['setting_value'] == '1';
}

// 允许 <img> 标签的函数
function allow_img_tag($text) {
    // 只允许 <img> 标签，且只允许 http(s) src
    $text = strip_tags($text, '<img>');
    return preg_replace_callback(
        '/<img\\s+[^>]*src=[\'\"]?([^\'\" >]+)[\'\"]?[^>]*>/i',
        function($matches) {
            $src = $matches[1];
            if (preg_match('/^https?:\/\//i', $src)) {
                return '<img src="'.htmlspecialchars($src).'" style="max-width:100%;height:auto;">';
            }
            return '';
        },
        $text
    );
}

// 允许基础安全HTML标签（b, i, u, a, code, pre, ul, ol, li, img）
function allow_basic_html(
    $text
) {
    // 允许的标签
    $allowed = '<b><i><u><a><code><pre><ul><ol><li><img><p><br>';
    $text = strip_tags($text, $allowed);
    // 只允许 http(s) 的 img src
    $text = preg_replace_callback(
        '/<img\s+[^>]*src=[\'\"]?([^\'\" >]+)[\'\"]?[^>]*>/i',
        function($matches) {
            $src = $matches[1];
            if (preg_match('/^https?:\/\//i', $src)) {
                return '<img src="'.htmlspecialchars($src).'" style="max-width:100%;height:auto;">';
            }
            return '';
        },
        $text
    );
    // a 标签只允许 http(s) 协议
    $text = preg_replace_callback(
        '/<a\s+[^>]*href=[\'\"]?([^\'\" >]+)[\'\"]?[^>]*>(.*?)<\/a>/i',
        function($matches) {
            $href = $matches[1];
            $label = $matches[2];
            if (preg_match('/^https?:\/\//i', $href)) {
                return '<a href="'.htmlspecialchars($href).'" target="_blank">'.$label.'</a>';
            }
            return $label;
        },
        $text
    );
    // 去除 <pre>、<ul>、<ol>、<li> 标签内的 <br>
    $text = preg_replace_callback('/<pre[^>]*>.*?<\/pre>/is', function($m) {
        return str_replace('<br />', '', $m[0]);
    }, $text);
    $text = preg_replace_callback('/<ul[^>]*>.*?<\/ul>/is', function($m) {
        return str_replace('<br />', '', $m[0]);
    }, $text);
    $text = preg_replace_callback('/<ol[^>]*>.*?<\/ol>/is', function($m) {
        return str_replace('<br />', '', $m[0]);
    }, $text);
    $text = preg_replace_callback('/<li[^>]*>.*?<\/li>/is', function($m) {
        return str_replace('<br />', '', $m[0]);
    }, $text);
    return $text;
}

// 基础Markdown解析（不依赖外部库，允许混用HTML）
function simple_markdown($text) {
    // 标题 #
    $text = preg_replace('/^###### (.*)$/m', '<h6>$1</h6>', $text);
    $text = preg_replace('/^##### (.*)$/m', '<h5>$1</h5>', $text);
    $text = preg_replace('/^#### (.*)$/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $text);
    // 粗体 **text** 或 __text__
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.*?)__/s', '<strong>$1</strong>', $text);
    // 斜体 *text* 或 _text_
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
    $text = preg_replace('/_(.*?)_/s', '<em>$1</em>', $text);
    // 行内代码 `code`
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    // 链接 [text](url)
    $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank">$1</a>', $text);
    // 换行
    $text = nl2br($text);
    return $text;
}

function updateUserLevel($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT posts_count, user_level FROM users WHERE id=?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $posts = $row['posts_count'];
    $current_level = $row['user_level'];
    // 管理员（建筑大师）不自动降级
    if ($current_level === '建筑大师') return;
    $levels = [
        200 => '红石工程师',
        80  => '钻石大师',
        30  => '铁器专家',
        10  => '石器时代',
        0   => '新手矿工'
    ];
    foreach ($levels as $min => $level) {
        if ($posts >= $min) {
            $stmt = $pdo->prepare("UPDATE users SET user_level=? WHERE id=?");
            $stmt->execute([$level, $user_id]);
            break;
        }
    }
}
?>
