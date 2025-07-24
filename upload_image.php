<?php
// upload_image.php
session_start();
header('Content-Type: application/json');

$upload_dir = __DIR__ . '/uploads/';
$max_size = 2 * 1024 * 1024; // 2MB

if (!isset($_FILES['image'])) {
    echo json_encode(['error' => '未选择文件']);
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => '上传失败']);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(['error' => '图片不能超过2MB']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['error' => '仅支持jpg/png/gif/webp']);
    exit;
}

if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

$filename = date('YmdHis') . '_' . uniqid() . '.' . $ext;
$target = $upload_dir . $filename;
if (move_uploaded_file($file['tmp_name'], $target)) {
    $url = 'uploads/' . $filename;
    echo json_encode(['success' => 1, 'url' => $url]);
} else {
    echo json_encode(['error' => '保存失败']);
} 