<?php
include 'header.php';
// 只允许管理员访问，header.php 已做判断

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from', 'smtp_secure', 'enable_email_verification'
    ];
    foreach ($fields as $f) {
        $v = isset($_POST[$f]) ? trim($_POST[$f]) : '';
        if ($f === 'enable_email_verification') $v = $v ? '1' : '0';
        $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$f, $v]);
    }
    echo '<div class="alert alert-success">SMTP配置已保存</div>';
}
// 读取现有配置
$settings = [];
$res = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'smtp_%' OR setting_key='enable_email_verification'");
foreach ($res as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<h2>SMTP 邮件配置</h2>
<form method="post" style="max-width:500px;">
    <label>SMTP服务器: <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" required></label><br><br>
    <label>端口: <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? ''); ?>" required></label><br><br>
    <label>账号: <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>" required></label><br><br>
    <label>密码: <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>" required></label><br><br>
    <label>发件人邮箱: <input type="email" name="smtp_from" value="<?php echo htmlspecialchars($settings['smtp_from'] ?? ''); ?>" required></label><br><br>
    <label>加密方式:
        <select name="smtp_secure">
            <option value="" <?php if(($settings['smtp_secure'] ?? '')=='') echo 'selected'; ?>>无</option>
            <option value="ssl" <?php if(($settings['smtp_secure'] ?? '')=='ssl') echo 'selected'; ?>>SSL</option>
            <option value="tls" <?php if(($settings['smtp_secure'] ?? '')=='tls') echo 'selected'; ?>>TLS</option>
        </select>
    </label><br><br>
    <label style="display:block;margin-top:18px;">
        <input type="checkbox" name="enable_email_verification" value="1" <?php if(($settings['enable_email_verification'] ?? '0')=='1') echo 'checked'; ?>> 启用邮箱验证（注册需验证邮箱）
    </label>
    <button type="submit">保存配置</button>
</form>
<?php include 'footer.php'; ?> 