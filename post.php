<?php
require_once 'functions.php';
global $pdo;
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$user = getCurrentUser();

// 获取分类列表
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY sort_order ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    if (mb_strlen($title) < 3) {
        $error = '标题至少3个字符';
    } elseif (mb_strlen($content) < 10) {
        $error = '内容至少10个字符';
    } elseif (!$category_id) {
        $error = '请选择分类';
    } else {
        $stmt = $pdo->prepare("INSERT INTO threads (title, content, author_id, category_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $content, $user['id'], $category_id]);
        $thread_id = $pdo->lastInsertId();
        header("Location: thread.php?id=$thread_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>发布新帖子 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="main-content card" style="max-width:600px;margin:40px auto;">
        <h2 style="color:#FFD700;">✏️ 发布新帖子</h2>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="title">标题</label>
                <input type="text" id="title" name="title" required minlength="3">
            </div>
            <div class="form-group">
                <label for="category_id">分类</label>
                <select id="category_id" name="category_id" required>
                    <option value="">请选择分类</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <input type="file" id="img-upload" accept="image/*" style="display:none;">
                <button type="button" class="btn" onclick="document.getElementById('img-upload').click();">上传图片</button>
            </div>
            <script>
                document.getElementById('img-upload').addEventListener('change', function(){
                    var file = this.files[0];
                    if (!file) return;
                    var formData = new FormData();
                    formData.append('image', file);
                    fetch('upload_image.php', {method:'POST', body:formData})
                        .then(r=>r.json()).then(res=>{
                            if(res.url){
                                var ta = document.getElementById('content');
                                var imgTag = '\n<img src="'+res.url+'" alt="图片" style="max-width:100%;">\n';
                                ta.value += imgTag;
                                alert('图片上传成功！');
                            }else{
                                alert(res.error || '上传失败');
                            }
                        });
                });
            </script>
            <div class="form-group">
                <label for="content">内容</label>
                <textarea id="content" name="content" rows="8" required minlength="10"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">发布</button>
            <a href="index.php" class="btn">取消</a>
        </form>
    </div>
</div>
</body>
</html>
