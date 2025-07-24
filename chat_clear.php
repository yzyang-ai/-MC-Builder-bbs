<?php
require_once 'functions.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$user = getCurrentUser();
if ($user['user_level'] !== '建筑大师') {
    die('无权限操作');
}
$pdo->exec("DELETE FROM chat_messages");
header('Location: chat.php?cleared=1');
exit; 