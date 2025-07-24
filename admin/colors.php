<?php
require_once '../functions.php';

// 默认配色
$default_colors = [
    'primary' => '#FFD700',
    'background' => '#fff',
    'button' => '#8B4513'
];

// 获取当前配色
$stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = 'colors'");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$colors = $row ? json_decode($row['value'], true) : $default_colors;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $colors = [
        'primary' => $_POST['primary'],
        'background' => $_POST['background'],
        'button' => $_POST['button']
    ];
    $stmt = $pdo->prepare("REPLACE INTO settings (`key`, `value`) VALUES ('colors', ?)");
    $stmt->execute([json_encode($colors)]);
    echo '<div style="color:green;">保存成功！</div>';
}
?>
<h2>配色管理</h2>
<form method="post">
    主色: <input type="color" name="primary" value="<?php echo htmlspecialchars($colors['primary']); ?>"><br>
    背景色: <input type="color" name="background" value="<?php echo htmlspecialchars($colors['background']); ?>"><br>
    按钮色: <input type="color" name="button" value="<?php echo htmlspecialchars($colors['button']); ?>"><br>
    <button type="submit">保存</button>
</form>