<?php
include 'header.php';
// 标记已处理
if (isset($_GET['done'])) {
    $id = intval($_GET['done']);
    $pdo->prepare("UPDATE feedback SET status='已处理' WHERE id=?")->execute([$id]);
    echo '<div class="alert alert-success">已标记为已处理</div>';
    echo '<meta http-equiv="refresh" content="1;url=feedback.php">';
}
// 删除反馈
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM feedback WHERE id=?")->execute([$id]);
    echo '<div class="alert alert-success">反馈已删除</div>';
    echo '<meta http-equiv="refresh" content="1;url=feedback.php">';
}
// 获取所有反馈
$stmt = $pdo->prepare("SELECT * FROM feedback ORDER BY created_at DESC");
$stmt->execute();
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>问题反馈管理</h2>
<table border="1" cellpadding="8" style="width:100%;background:#fff;">
    <tr><th>ID</th><th>用户</th><th>内容</th><th>时间</th><th>状态</th><th>操作</th></tr>
    <?php foreach ($feedbacks as $f): ?>
    <tr>
        <td><?php echo $f['id']; ?></td>
        <td><?php echo htmlspecialchars($f['username'] ?: '匿名'); ?></td>
        <td><?php echo htmlspecialchars($f['content']); ?></td>
        <td><?php echo $f['created_at']; ?></td>
        <td><?php echo $f['status']; ?></td>
        <td>
            <?php if ($f['status'] === '未处理'): ?>
                <a href="feedback.php?done=<?php echo $f['id']; ?>">标记已处理</a> |
            <?php endif; ?>
            <a href="feedback.php?delete=<?php echo $f['id']; ?>" onclick="return confirm('确定删除？');">删除</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include 'footer.php'; ?> 