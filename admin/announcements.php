<?php
include 'header.php';
// 仅“建筑大师”可访问，header.php 已做判断

// 编辑公告逻辑
$edit_announcement = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($title && $content) {
            $stmt = $pdo->prepare("UPDATE announcements SET title=?, content=? WHERE id=?");
            $stmt->execute([$title, $content, $edit_id]);
            echo '<div class="alert alert-success">公告已更新</div>';
            echo '<meta http-equiv="refresh" content="1;url=announcements.php">';
            exit;
        } else {
            echo '<div class="alert alert-error">标题和内容不能为空</div>';
        }
    }
}
// 添加公告
if (isset($_POST['add'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($title && $content) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
        $stmt->execute([$title, $content]);
        echo '<div class="alert alert-success">公告已发布</div>';
    } else {
        echo '<div class="alert alert-error">标题和内容不能为空</div>';
    }
}
// 删除公告
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$del_id]);
    echo '<div class="alert alert-success">公告已删除</div>';
}
// 获取所有公告
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>公告管理</h2>
<?php if ($edit_announcement): ?>
<form method="post" style="margin-bottom:20px;">
    <input type="hidden" name="edit_id" value="<?php echo $edit_announcement['id']; ?>">
    <input type="text" name="title" value="<?php echo htmlspecialchars($edit_announcement['title']); ?>" required style="width:200px;">
    <textarea name="content" required style="width:400px;height:60px;"><?php echo htmlspecialchars($edit_announcement['content']); ?></textarea>
    <button type="submit">保存修改</button>
    <a href="announcements.php" class="btn">取消</a>
</form>
<?php endif; ?>
<form method="post" style="margin-bottom:20px;">
    <input type="text" name="title" placeholder="公告标题" required style="width:200px;"> 
    <textarea name="content" placeholder="公告内容" required style="width:400px;height:60px;"></textarea>
    <button type="submit" name="add">发布公告</button>
</form>
<table border="1" cellpadding="8" style="width:100%;background:#fff;">
    <tr><th>ID</th><th>标题</th><th>内容</th><th>发布时间</th><th>操作</th></tr>
    <?php foreach ($announcements as $a): ?>
    <tr>
        <td><?php echo $a['id']; ?></td>
        <td><?php echo htmlspecialchars($a['title']); ?></td>
        <td><?php echo nl2br(htmlspecialchars($a['content'])); ?></td>
        <td><?php echo $a['created_at']; ?></td>
        <td>
            <a href="?edit=<?php echo $a['id']; ?>">编辑</a>
            <a href="?delete=<?php echo $a['id']; ?>" onclick="return confirm('确定删除该公告？');">删除</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include 'footer.php'; ?> 