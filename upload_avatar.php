<?php
require_once 'functions.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$user = getCurrentUser();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            if ($file['size'] <= 2*1024*1024) {
                $filename = $user['id'] . '_' . time() . '.' . $ext;
                $target_dir = 'images/avatars/';
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $target = $target_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $stmt = $pdo->prepare("UPDATE users SET avatar=? WHERE id=?");
                    $stmt->execute([$filename, $user['id']]);
                    $msg = '头像上传成功！';
                } else {
                    $msg = '上传失败，请重试。';
                }
            } else {
                $msg = '文件过大，最大2MB。';
            }
        } else {
            $msg = '仅支持jpg/png/gif格式。';
        }
    } else {
        $msg = '上传出错。';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>上传头像</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container" style="max-width:400px;margin:40px auto;">
    <div class="card" style="padding:30px;">
        <h2>上传头像</h2>
        <?php if ($msg): ?><div class="alert"><?php echo $msg; ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="avatar" accept="image/*" required>
            <button type="submit" class="btn btn-primary" style="margin-top:16px;">上传</button>
        </form>
        <div style="margin-top:20px;">
            <a href="profile.php?id=<?php echo $user['id']; ?>" class="btn">返回个人主页</a>
        </div>
    </div>
</div>
</body>
</html> 