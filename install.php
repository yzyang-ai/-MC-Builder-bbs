<?php
// 显示所有错误，便于调试
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 升级入口：如已安装则允许升级数据库（可自定义数据库连接信息）
if (file_exists('config.php')) {
    // 默认从 config.php 读取
    $default_db_host = '';
    $default_db_name = '';
    $default_db_user = '';
    $default_db_pass = '';
    if (file_exists('config.php')) {
        $config = file_get_contents('config.php');
        if (preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $config, $m)) $default_db_host = $m[1];
        if (preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $config, $m)) $default_db_name = $m[1];
        if (preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $config, $m)) $default_db_user = $m[1];
        if (preg_match("/define\('DB_PASS',\s*'([^']*)'\)/", $config, $m)) $default_db_pass = $m[1];
    }
    if (isset($_GET['upgrade']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $db_host = trim($_POST['db_host'] ?? '');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = $_POST['db_pass'] ?? '';
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // 自动修复 settings 表结构，升级前先删除旧表
            $pdo->exec("DROP TABLE IF EXISTS settings;");
            $sql = file_get_contents('database.sql');
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
            // 跳过 DELIMITER 和 TRIGGER 相关语句
            $lines = explode("\n", $sql);
            $buffer = '';
            $in_trigger = false;
            foreach ($lines as $line) {
                if (stripos($line, 'DELIMITER') !== false) continue;
                if (preg_match('/CREATE\s+TRIGGER/i', $line)) $in_trigger = true;
                if ($in_trigger) {
                    if (stripos($line, 'END') !== false) $in_trigger = false;
                    continue;
                }
                $buffer .= $line . "\n";
            }
            $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $buffer)));
            foreach ($statements as $stmt) {
                if ($stmt) {
                    try {
                        $pdo->exec($stmt);
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate') === false &&
                            strpos($e->getMessage(), 'already exists') === false &&
                            strpos($e->getMessage(), '1060') === false &&
                            strpos($e->getMessage(), '1061') === false &&
                            strpos($e->getMessage(), '1062') === false
                        ) {
                            throw $e;
                        }
                    }
                }
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
            echo '<div style="max-width:600px;margin:60px auto;padding:40px;background:#fff;border-radius:8px;text-align:center;box-shadow:0 2px 8px #eee;">';
            echo '<h2 style="color:#28a745;">数据库升级完成！</h2>';
            echo '<a href="index.php" style="display:inline-block;margin-top:30px;padding:10px 30px;background:#FFD700;color:#222;border-radius:6px;text-decoration:none;font-weight:bold;">访问首页</a>';
            echo '</div>';
            exit;
        } catch (Exception $e) {
            echo '<div style="max-width:600px;margin:60px auto;padding:40px;background:#fff;border-radius:8px;text-align:center;box-shadow:0 2px 8px #eee;">';
            echo '数据库连接失败: ' . $e->getMessage();
            echo '</div>';
            exit;
        }
    } elseif (isset($_GET['upgrade'])) {
        // 显示升级表单
        echo '<div style="max-width:600px;margin:60px auto;padding:40px;background:#fff;border-radius:8px;text-align:center;box-shadow:0 2px 8px #eee;">';
        echo '<h2 style="color:#FFD700;">数据库升级</h2>';
        echo '<form method="post" style="margin:30px auto 0 auto;max-width:350px;text-align:left;">';
        echo '<label>数据库主机：<input type="text" name="db_host" value="'.htmlspecialchars($default_db_host).'" required></label><br><br>';
        echo '<label>数据库名称：<input type="text" name="db_name" value="'.htmlspecialchars($default_db_name).'" required></label><br><br>';
        echo '<label>数据库账号：<input type="text" name="db_user" value="'.htmlspecialchars($default_db_user).'" required></label><br><br>';
        echo '<label>数据库密码：<input type="password" name="db_pass" value="'.htmlspecialchars($default_db_pass).'" required></label><br><br>';
        echo '<button type="submit" style="padding:10px 30px;background:#FFD700;color:#222;border-radius:6px;text-decoration:none;font-weight:bold;">开始升级</button>';
        echo '</form>';
        echo '</div>';
        exit;
    } else {
        echo '<div style="max-width:600px;margin:60px auto;padding:40px;background:#fff;border-radius:8px;text-align:center;box-shadow:0 2px 8px #eee;">';
        echo '<h2 style="color:#FFD700;">检测到已有安装</h2>';
        echo '<a href="install.php?upgrade=1" style="display:inline-block;margin-top:30px;padding:10px 30px;background:#FFD700;color:#222;border-radius:6px;text-decoration:none;font-weight:bold;">升级数据库</a>';
        echo '</div>';
        exit;
    }
}

// 安装锁定
if (file_exists('config_installed.lock')) {
    die('网站已经安装过了！如需重新安装，请删除 config_installed.lock 文件。');
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// 步骤1：环境检测
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $requirements = [
        'PHP版本 >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO扩展' => extension_loaded('pdo'),
        'PDO MySQL驱动' => extension_loaded('pdo_mysql'),
        'Session支持' => function_exists('session_start'),
        '文件写入权限' => is_writable('.'),
        'GD图像处理' => extension_loaded('gd')
    ];
    $all_ok = array_reduce($requirements, function($a, $b) { return $a && $b; }, true);
    if ($all_ok) {
        header('Location: install.php?step=2');
        exit;
    } else {
        $error = '环境检测未通过，请解决所有问题后再继续。';
    }
}

// 步骤2：数据库配置
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $site_name = trim($_POST['site_name'] ?? 'MC Builder 论坛');
    $site_url = trim($_POST['site_url'] ?? '');
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("set names utf8mb4");
        // 写入config.php
        $config_content = "<?php\n// 数据库配置\ndefine('DB_HOST', '".addslashes($db_host)."');\ndefine('DB_USER', '".addslashes($db_user)."');\ndefine('DB_PASS', '".addslashes($db_pass)."');\ndefine('DB_NAME', '".addslashes($db_name)."');\n// 网站设置\ndefine('SITE_NAME', '".addslashes($site_name)."');\ndefine('SITE_URL', '".addslashes($site_url)."');\n\nsession_start();\n";
        if (file_put_contents('config.php', $config_content)) {
            header('Location: install.php?step=3');
            exit;
        } else {
            $error = '无法写入config.php，请检查目录权限。';
        }
    } catch (PDOException $e) {
        $error = '数据库连接失败: ' . $e->getMessage();
    }
}

// 步骤3：导入SQL
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = file_get_contents('database.sql');
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
        // 跳过 DELIMITER 和 TRIGGER 相关语句
        $lines = explode("\n", $sql);
        $buffer = '';
        $in_trigger = false;
        foreach ($lines as $line) {
            if (stripos($line, 'DELIMITER') !== false) continue;
            if (preg_match('/CREATE\s+TRIGGER/i', $line)) $in_trigger = true;
            if ($in_trigger) {
                if (stripos($line, 'END') !== false) $in_trigger = false;
                continue;
            }
            $buffer .= $line . "\n";
        }
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $buffer)));
        foreach ($statements as $stmt) {
            if ($stmt) {
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $e) {
                    // 只忽略“已存在”类错误，其它错误仍然抛出
                    if (strpos($e->getMessage(), 'Duplicate') === false &&
                        strpos($e->getMessage(), 'already exists') === false &&
                        strpos($e->getMessage(), '1060') === false && // Duplicate column
                        strpos($e->getMessage(), '1061') === false && // Duplicate key
                        strpos($e->getMessage(), '1062') === false    // Duplicate entry
                    ) {
                        throw $e;
                    }
                    // 否则忽略
                }
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
        // 补充创建公告表（如未在SQL中）
        // $pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
        //   id INT AUTO_INCREMENT PRIMARY KEY,
        //   title VARCHAR(255) NOT NULL,
        //   content TEXT NOT NULL,
        //   created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        // )");
        // 补充邮箱验证字段（如未在SQL中）
        // $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0");
        // $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verification_code VARCHAR(10) DEFAULT NULL");
        // $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verification_expire DATETIME DEFAULT NULL");
        // SMTP 配置项（如不存在则插入默认值）
        // $smtp_keys = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from', 'smtp_secure'];
        // foreach ($smtp_keys as $k) {
        //   $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value, setting_type) VALUES ('$k', '', 'string')");
        // }
        header('Location: install.php?step=4');
        exit;
    } catch (Exception $e) {
        $error = '导入数据库失败: ' . $e->getMessage();
    }
}

// 步骤4：创建管理员
if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    $username = trim($_POST['admin_username'] ?? '');
    $email = trim($_POST['admin_email'] ?? '');
    $password = $_POST['admin_password'] ?? '';
    if (strlen($username) < 3 || strlen($password) < 6) {
        $error = '用户名至少3个字符，密码至少6个字符';
    } else {
        try {
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_level) VALUES (?, ?, ?, '建筑大师')");
            $stmt->execute([$username, $email, $hashed_password]);
            file_put_contents('config_installed.lock', date('Y-m-d H:i:s'));
            header('Location: install.php?step=5');
            exit;
        } catch (Exception $e) {
            $error = '创建管理员失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>MC Builder 论坛 - 安装向导</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .install-container { max-width: 700px; margin: 50px auto; padding: 0 20px; }
        .install-card { background: #222; border-radius: 12px; padding: 40px; border: 4px solid #8B4513; box-shadow: 0 0 30px rgba(0,0,0,0.7); }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 40px; }
        .step { width: 40px; height: 40px; border-radius: 50%; background: #444; color: #AAA; margin: 0 10px; font-weight: bold; display: flex; align-items: center; justify-content: center; }
        .step.active { background: #8B4513; color: #FFD700; }
        .step.completed { background: #28a745; color: white; }
        .alert { padding: 10px 20px; border-radius: 6px; margin-bottom: 20px; }
        .alert-error { background: #dc3545; color: #fff; }
        .alert-success { background: #28a745; color: #fff; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; color: #FFD700; }
        input, button { padding: 8px 12px; border-radius: 4px; border: 1px solid #888; }
        input { width: 100%; }
        button, .btn { background: #8B4513; color: #FFD700; border: none; cursor: pointer; font-weight: bold; }
        button:hover, .btn:hover { background: #FFD700; color: #8B4513; }
    </style>
</head>
<body>
<div class="install-container">
    <div class="install-card">
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="color: #FFD700; font-size: 1.5em; margin-bottom: 10px;">⛏️ MC Builder 论坛</h1>
            <p style="color: #AAAAAA;">欢迎使用安装向导</p>
        </div>
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? ($step == 1 ? 'active' : 'completed') : ''; ?>">1</div>
            <div class="step <?php echo $step >= 2 ? ($step == 2 ? 'active' : 'completed') : ''; ?>">2</div>
            <div class="step <?php echo $step >= 3 ? ($step == 3 ? 'active' : 'completed') : ''; ?>">3</div>
            <div class="step <?php echo $step >= 4 ? ($step == 4 ? 'active' : 'completed') : ''; ?>">4</div>
            <div class="step <?php echo $step >= 5 ? 'active' : ''; ?>">5</div>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($step === 1): ?>
            <h2 style="color: #FFD700; margin-bottom: 25px;">🔍 第一步：环境检测</h2>
            <form method="POST">
                <ul style="color:#fff;">
                    <li>PHP版本 >= 7.4 (当前: <?php echo PHP_VERSION; ?>)</li>
                    <li>PDO扩展: <?php echo extension_loaded('pdo') ? '已启用' : '未启用'; ?></li>
                    <li>PDO MySQL驱动: <?php echo extension_loaded('pdo_mysql') ? '已启用' : '未启用'; ?></li>
                    <li>Session支持: <?php echo function_exists('session_start') ? '支持' : '不支持'; ?></li>
                    <li>文件写入权限: <?php echo is_writable('.') ? '可写' : '不可写'; ?></li>
                    <li>GD图像处理: <?php echo extension_loaded('gd') ? '已启用' : '未启用'; ?></li>
                </ul>
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn">继续安装</button>
                </div>
            </form>
        <?php elseif ($step === 2): ?>
            <h2 style="color: #FFD700; margin-bottom: 25px;">🗄️ 第二步：数据库配置</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="db_host">数据库主机</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label for="db_name">数据库名称</label>
                    <input type="text" id="db_name" name="db_name" placeholder="minecraft_forum" required>
                </div>
                <div class="form-group">
                    <label for="db_user">数据库用户名</label>
                    <input type="text" id="db_user" name="db_user" required>
                </div>
                <div class="form-group">
                    <label for="db_pass">数据库密码</label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>
                <div class="form-group">
                    <label for="site_name">网站名称</label>
                    <input type="text" id="site_name" name="site_name" value="MC Builder 论坛" required>
                </div>
                <div class="form-group">
                    <label for="site_url">网站地址</label>
                    <input type="url" id="site_url" name="site_url" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?>" required>
                </div>
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn">测试连接并继续</button>
                </div>
            </form>
        <?php elseif ($step === 3): ?>
            <h2 style="color: #FFD700; margin-bottom: 25px;">🏗️ 第三步：导入数据库结构</h2>
            <form method="POST">
                <div style="text-align: center; margin: 40px 0;">
                    <div class="loading" style="margin: 0 auto 20px;"></div>
                    <p>点击下方按钮导入数据库表和初始数据。</p>
                </div>
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn">导入数据库</button>
                </div>
            </form>
        <?php elseif ($step === 4): ?>
            <h2 style="color: #FFD700; margin-bottom: 25px;">👑 第四步：创建管理员账户</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="admin_username">管理员用户名</label>
                    <input type="text" id="admin_username" name="admin_username" placeholder="admin" required minlength="3">
                </div>
                <div class="form-group">
                    <label for="admin_email">管理员邮箱</label>
                    <input type="email" id="admin_email" name="admin_email" placeholder="admin@example.com" required>
                </div>
                <div class="form-group">
                    <label for="admin_password">管理员密码</label>
                    <input type="password" id="admin_password" name="admin_password" placeholder="至少6个字符" required minlength="6">
                </div>
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn">创建管理员账户</button>
                </div>
            </form>
        <?php elseif ($step === 5): ?>
            <div style="text-align: center;">
                <h2 style="color: #28a745; margin-bottom: 25px;">🎉 安装完成！</h2>
                <div style="font-size: 4em; margin: 30px 0;">⛏️</div>
                <p style="font-size: 1.2em; margin-bottom: 30px;">MC Builder 论坛已经成功安装！</p>
                <div style="background: rgba(40, 40, 40, 0.8); padding: 25px; border-radius: 8px; margin: 30px 0; text-align: left;">
                    <h3 style="color: #FFD700; margin-bottom: 15px;">📋 下一步操作：</h3>
                    <ul style="line-height: 1.8;">
                        <li>🗑️ <strong>删除安装文件</strong>：为了安全，请删除 <code>install.php</code> 文件</li>
                        <li>📁 <strong>上传头像图片</strong>：在 <code>images/avatars/</code> 目录放入Minecraft皮肤头像</li>
                        <li>🎨 <strong>自定义主题</strong>：编辑 <code>css/style.css</code> 调整颜色和样式</li>
                        <li>⚙️ <strong>配置服务器</strong>：设置URL重写和文件权限</li>
                        <li>🔧 <strong>安装插件</strong>：根据需要添加更多功能插件</li>
                    </ul>
                </div>
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <a href="index.php" class="btn">🏠 访问首页</a>
                </div>
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #444; color: #AAAAAA; font-size: 0.9em;">
                    <p>感谢选择 MC Builder 论坛！祝您的 Minecraft 社区蓬勃发展！</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<p>本项目采用Apache-2.0协议开源，请遵守开源协议</p>
</body>
</html>
