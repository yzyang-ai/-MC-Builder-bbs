<?php
require_once 'functions.php';
$stmt = $pdo->prepare("SELECT username, content, created_at FROM chat_messages ORDER BY id DESC LIMIT 50");
$stmt->execute();
$msgs = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
header('Content-Type: application/json');
echo json_encode($msgs); 