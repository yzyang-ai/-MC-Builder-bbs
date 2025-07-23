<?php
include 'header.php';

// 处理新增分类
if (isset($_POST['add'])) {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? '📁');
    $sort = intval($_POST['sort_order'] ?? 0);
    if ($name) {
        $pdo->prepare("INSERT INTO categories (name, description, icon, sort_order) VALUES (?, ?, ?, ?)")
            ->execute([$name, $desc, $icon, $sort]);
        echo '<div class="alert alert-success">分类已添加</div>';
        echo '<meta http-equiv="refresh" content="1;url=categories.php">';
    } else {
        echo '<div class="alert alert-error">分类名称不能为空</div>';
    }
}

// 处理编辑分类
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? '📁');
    $sort = intval($_POST['sort_order'] ?? 0);
    if ($name) {
        $pdo->prepare("UPDATE categories SET name=?, description=?, icon=?, sort_order=? WHERE id=?")
            ->execute([$name, $desc, $icon, $sort, $id]);
        echo '<div class="alert alert-success">分类已修改</div>';
        echo '<meta http-equiv="refresh" content="1;url=categories.php">';
    } else {
        echo '<div class="alert alert-error">分类名称不能为空</div>';
    }
}

// 删除分类操作
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$del_id]);
    echo '<div class="alert alert-success">分类已删除</div>';
    echo '<meta http-equiv="refresh" content="1;url=categories.php">';
}

// 获取所有分类
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY sort_order ASC, id ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 编辑表单数据
$edit_cat = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($categories as $c) {
        if ($c['id'] == $edit_id) {
            $edit_cat = $c;
            break;
        }
    }
}
?>
<h2>分类管理</h2>
<!-- 新增/编辑分类表单 -->
<div style="margin-bottom:30px;">
    <form method="post" style="display:inline-block;min-width:320px;">
        <?php if ($edit_cat): ?>
            <input type="hidden" name="id" value="<?php echo $edit_cat['id']; ?>">
        <?php endif; ?>
        <label>名称 <input type="text" name="name" value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['name']) : ''; ?>" required></label>
        <label>描述 <input type="text" name="description" value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['description']) : ''; ?>"></label>
        <label>图标 <input type="text" name="icon" value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['icon']) : '📁'; ?>" style="width:3em;"></label>
        <label>排序 <input type="number" name="sort_order" value="<?php echo $edit_cat ? $edit_cat['sort_order'] : 0; ?>" style="width:5em;"></label>
        <button type="submit" name="<?php echo $edit_cat ? 'edit' : 'add'; ?>" class="btn btn-primary"><?php echo $edit_cat ? '保存修改' : '添加分类'; ?></button>
        <?php if ($edit_cat): ?>
            <a href="categories.php" class="btn">取消</a>
        <?php endif; ?>
    </form>
</div>
<table border="1" cellpadding="8" style="width:100%;background:#fff;">
    <tr><th>ID</th><th>名称</th><th>描述</th><th>图标</th><th>排序</th><th>操作</th></tr>
    <?php foreach ($categories as $c): ?>
    <tr>
        <td><?php echo $c['id']; ?></td>
        <td><?php echo htmlspecialchars($c['name']); ?></td>
        <td><?php echo htmlspecialchars($c['description']); ?></td>
        <td><?php echo htmlspecialchars($c['icon']); ?></td>
        <td><?php echo $c['sort_order']; ?></td>
        <td>
            <a href="categories.php?edit=<?php echo $c['id']; ?>">编辑</a> |
            <a href="categories.php?delete=<?php echo $c['id']; ?>" onclick="return confirm('确定删除该分类？');">删除</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include 'footer.php'; ?> 