<?php
include 'header.php';
// 删除帖子操作
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM threads WHERE id = ?")->execute([$del_id]);
    echo '<div class="alert alert-success">帖子已删除</div>';
    echo '<meta http-equiv="refresh" content="1;url=threads.php">';
}
// 获取所有帖子
$stmt = $pdo->prepare("SELECT t.*, u.username, c.name as category_name FROM threads t JOIN users u ON t.author_id = u.id JOIN categories c ON t.category_id = c.id ORDER BY t.created_at DESC");
$stmt->execute();
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>帖子管理</h2>
<table border="1" cellpadding="8" style="width:100%;background:#fff;">
    <tr><th>ID</th><th>标题</th><th>作者</th><th>分类</th><th>创建时间</th><th>操作</th></tr>
    <?php foreach ($threads as $t): ?>
    <tr>
        <td><?php echo $t['id']; ?></td>
        <td><?php echo htmlspecialchars($t['title']); ?></td>
        <td><?php echo htmlspecialchars($t['username']); ?></td>
        <td><?php echo htmlspecialchars($t['category_name']); ?></td>
        <td><?php echo $t['created_at']; ?></td>
        <td>
            <a href="threads.php?delete=<?php echo $t['id']; ?>" onclick="return confirm('确定删除该帖子？');">删除</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include 'footer.php'; ?> 