<?php
require_once 'functions.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>在线聊天室</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .chat-box { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px #eee; }
        .chat-messages { height: 320px; overflow-y: auto; border: 1px solid #eee; padding: 12px; background: #fafafa; margin-bottom: 16px; }
        .chat-input { display: flex; gap: 8px; }
        .chat-input input { flex: 1; padding: 8px; }
        .chat-input button { padding: 8px 18px; }
        .chat-msg-user { color: #FFD700; font-weight: bold; }
        .chat-msg-time { color: #aaa; font-size: 0.9em; margin-left: 8px; }
    </style>
</head>
<body>
<div class="chat-box">
    <h2>在线聊天室</h2>
    <?php if (isLoggedIn() && getCurrentUser()['user_level'] === '建筑大师'): ?>
        <form method="post" action="chat_clear.php" style="display:inline;">
            <button type="submit" class="btn btn-danger" onclick="return confirm('确定要清空所有聊天记录吗？');">清空聊天室</button>
        </form>
    <?php endif; ?>
    <?php if (isset($_GET['cleared'])): ?>
        <div class="alert alert-success">聊天室已清空！</div>
    <?php endif; ?>
    <div id="chat-messages" class="chat-messages"></div>
    <form id="chat-form" class="chat-input" autocomplete="off">
        <input type="text" id="chat-content" maxlength="200" placeholder="输入消息..." required>
        <button type="submit">发送</button>
    </form>
</div>
<script>
function fetchMessages() {
    fetch('chat_fetch.php')
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(msg => {
                html += `<div><span class="chat-msg-user">${msg.username}</span><span class="chat-msg-time">${msg.created_at}</span>: ${msg.content}</div>`;
            });
            document.getElementById('chat-messages').innerHTML = html;
            let box = document.getElementById('chat-messages');
            box.scrollTop = box.scrollHeight;
        });
}
fetchMessages();
setInterval(fetchMessages, 3000);

document.getElementById('chat-form').onsubmit = function(e) {
    e.preventDefault();
    let content = document.getElementById('chat-content').value.trim();
    if (!content) return;
    fetch('chat_send.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'content=' + encodeURIComponent(content)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            document.getElementById('chat-content').value = '';
            fetchMessages();
        } else {
            alert(data.error || '发送失败');
        }
    });
};
</script>
</body>
</html> 