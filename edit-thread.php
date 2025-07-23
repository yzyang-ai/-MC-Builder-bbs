<?php
require_once 'functions.php';
global $pdo;

if (!isset($_GET['id'])) {
    die('缺少帖子ID');
}
$thread_id = intval($_GET['id']);

// 查询帖子信息
$stmt = $pdo->prepare("SELECT * FROM threads WHERE id = ?");
$stmt->execute([$thread_id]);
$thread = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$thread) {
    die('帖子不存在');
}

// 权限校验：仅允许管理员或原作者编辑
$user = isLoggedIn() ? getCurrentUser() : null;
if (!$user || ($user['user_level'] !== '建筑大师' && $user['id'] != $thread['author_id'])) {
    die('无权限编辑该帖子');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (mb_strlen($title) < 3) {
        $error = '标题至少3个字符';
    } elseif (mb_strlen($content) < 10) {
        $error = '内容至少10个字符';
    } else {
        $stmt = $pdo->prepare("UPDATE threads SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $content, $thread_id]);
        header("Location: thread.php?id=$thread_id");
        exit;
    }
}

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑帖子 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>编辑帖子</h1>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label for="title">标题</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($thread['title']); ?>" required minlength="3" style="width:100%;">
        </div>
        <div class="form-group">
            <label for="content">内容</label>
            <textarea id="content" name="content" rows="10" required minlength="10" style="width:100%;"><?php echo htmlspecialchars($thread['content']); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">保存修改</button>
        <a href="thread.php?id=<?php echo $thread_id; ?>" class="btn">取消</a>
    </form>
</div>
</body>
</html> 