<?php
include 'header.php';
// 删除回复操作
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM replies WHERE id = ?")->execute([$del_id]);
    echo '<div class="alert alert-success">回复已删除</div>';
    echo '<meta http-equiv="refresh" content="1;url=replies.php">';
}
// 获取所有回复
$stmt = $pdo->prepare("SELECT r.*, u.username, t.title as thread_title FROM replies r JOIN users u ON r.author_id = u.id JOIN threads t ON r.thread_id = t.id ORDER BY r.created_at DESC");
$stmt->execute();
$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>回复管理</h2>
<table border="1" cellpadding="8" style="width:100%;background:#fff;">
    <tr><th>ID</th><th>内容</th><th>作者</th><th>所属帖子</th><th>时间</th><th>操作</th></tr>
    <?php foreach ($replies as $r): ?>
    <tr>
        <td><?php echo $r['id']; ?></td>
        <td><?php echo htmlspecialchars(mb_substr($r['content'],0,30)); ?></td>
        <td><?php echo htmlspecialchars($r['username']); ?></td>
        <td><?php echo htmlspecialchars($r['thread_title']); ?></td>
        <td><?php echo $r['created_at']; ?></td>
        <td>
            <a href="replies.php?delete=<?php echo $r['id']; ?>" onclick="return confirm('确定删除该回复？');">删除</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include 'footer.php'; ?> 