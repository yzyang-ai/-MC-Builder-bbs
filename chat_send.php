<?php
require_once 'functions.php';
if (!isLoggedIn()) {
    echo json_encode(['success'=>false, 'error'=>'请先登录']);
    exit;
}
$user = getCurrentUser();
$content = trim($_POST['content'] ?? '');
if ($content === '' || mb_strlen($content) > 200) {
    echo json_encode(['success'=>false, 'error'=>'消息不能为空且不超过200字']);
    exit;
}
$stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, username, content) VALUES (?, ?, ?)");
$stmt->execute([$user['id'], $user['username'], htmlspecialchars($content)]);
echo json_encode(['success'=>true]); 