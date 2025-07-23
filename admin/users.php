<?php
include 'header.php';
// 获取所有用户
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY id DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
// 删除用户操作
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id != $user['id']) { // 不允许自删
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$del_id]);
        echo '<div class="alert alert-success">用户已删除</div>';
        // 刷新页面
        echo '<meta http-equiv="refresh" content="1;url=users.php">';
    } else {
        echo '<div class="alert alert-error">不能删除自己</div>';
    }
}
?>
<h2>用户管理</h2>
<table border="1" cellpadding="8" style="width:100%;background:#fff;">
    <tr><th>ID</th><th>用户名</th><th>邮箱</th><th>等级</th><th>注册时间</th><th>操作</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?php echo $u['id']; ?></td>
        <td><?php echo htmlspecialchars($u['username']); ?></td>
        <td><?php echo htmlspecialchars($u['email']); ?></td>
        <td><?php echo htmlspecialchars($u['user_level']); ?></td>
        <td><?php echo $u['join_date']; ?></td>
        <td>
            <?php if ($u['id'] != $user['id']): ?>
            <a href="users.php?delete=<?php echo $u['id']; ?>" onclick="return confirm('确定删除该用户？');">删除</a>
            <?php else: ?>
            --
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include 'footer.php'; ?> 