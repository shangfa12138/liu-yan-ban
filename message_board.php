<?php
session_start();
include 'pdo_connect.php';
require "清洁函数.php";
include 'check.php';
require_once __DIR__ . '/ensure_liuyanban_append.php';
require_once __DIR__ . '/ensure_username_avatar.php';
require_once __DIR__ . '/image_upload_store.php';
require_once __DIR__ . '/user_content_purge.php';
ensure_liuyanban_append_table($pdo);
ensure_username_avatar_column($pdo);
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];
$success = '';
$error = '';
if (isset($_POST['upload_avatar']) && isset($_FILES['avatar'])) {
    $up = store_validated_profile_image($_FILES['avatar']);
    if (!$up['ok']) {
        $error = $up['error'] ?? '头像上传失败';
    } else {
        $oldStmt = $pdo->prepare('SELECT avatar FROM username WHERE id = ?');
        $oldStmt->execute([$current_user_id]);
        $prev = $oldStmt->fetchColumn();
        delete_upload_file_if_safe($prev !== false ? (string)$prev : null);
        $pdo->prepare('UPDATE username SET avatar = ? WHERE id = ?')->execute([$up['path'], $current_user_id]);
        $success = '头像已更新';
    }
}
if (isset($_POST['change_password'])) {
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (empty($new) || empty($confirm)) {
        $error = "密码不能为空";
    } elseif ($new !== $confirm) {
        $error = "两次密码不一致";
    } elseif (strlen($new) < 6) {
        $error = "密码至少6位";
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE username SET password=? WHERE id=?")
                ->execute([$newHash, $current_user_id]);
        $success = "密码修改成功";
    }
}
if (isset($_POST['content'])) {
    $name = $_SESSION['name'] ;
    $content = superClean($_POST['content']);
    if (!empty($content)) {
        $pdo->prepare("INSERT INTO liuyanban (name,user_id, content) VALUES (?,?,?)")
                ->execute([$name,$current_user_id, $content]);
        $success = "留言成功";
    } else {
        $error = "内容不能为空";
    }
}
if (isset($_POST['append_submit'])) {
    $append_mid = (int)($_POST['append_message_id'] ?? 0);
    $append_raw = trim($_POST['append_content'] ?? '');
    if ($append_mid <= 0) {
        $error = "无效的留言";
    } elseif ($append_raw === '') {
        $error = "追加内容不能为空";
    } else {
        $st = $pdo->prepare("SELECT 1 FROM liuyanban WHERE id = ?");
        $st->execute([$append_mid]);
        if (!$st->fetchColumn()) {
            $error = "留言不存在";
        } else {
            $append_text = superClean($append_raw);
            $pdo->prepare("INSERT INTO liuyanban_append (message_id, user_id, content) VALUES (?,?,?)")
                    ->execute([$append_mid, $current_user_id, $append_text]);
            $success = "追加成功";
        }
    }
}
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT user_id FROM liuyanban WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && ($row['user_id'] == $current_user_id || $current_user_role == 'admin')) {
        $pdo->prepare("DELETE FROM liuyanban_append WHERE message_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM liuyanban WHERE id=?")->execute([$id]);
        $success = "删除成功";
    }
}
$stmt = $pdo->query("SELECT l.*, u.name AS real_name, u.avatar AS author_avatar FROM liuyanban l 
LEFT JOIN username u ON l.user_id = u.id 
ORDER BY l.id DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
$appendsByMessage = [];
if (count($messages) > 0) {
    $msgIds = array_map('intval', array_column($messages, 'id'));
    $placeholders = implode(',', array_fill(0, count($msgIds), '?'));
    $apStmt = $pdo->prepare("SELECT a.*, u.name AS append_author, u.avatar AS append_author_avatar FROM liuyanban_append a
        LEFT JOIN username u ON a.user_id = u.id
        WHERE a.message_id IN ($placeholders) ORDER BY a.id ASC");
    $apStmt->execute($msgIds);
    foreach ($apStmt->fetchAll(PDO::FETCH_ASSOC) as $ap) {
        $appendsByMessage[(int)$ap['message_id']][] = $ap;
    }
}
$meAvatarStmt = $pdo->prepare('SELECT avatar FROM username WHERE id = ?');
$meAvatarStmt->execute([$current_user_id]);
$current_user_avatar = $meAvatarStmt->fetchColumn();
$current_user_avatar = ($current_user_avatar !== false && $current_user_avatar !== null && $current_user_avatar !== '')
    ? (string)$current_user_avatar : '';
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>留言板</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>留言板</h2>
    <div class="profile-panel">
        <div class="profile-avatar-row">
            <div class="profile-avatar-wrap">
                <?php
                $myName = (string)($_SESSION['name'] ?? '');
                $myInitial = mb_substr($myName !== '' ? $myName : '?', 0, 1, 'UTF-8');
                ?>
                <?php if ($current_user_avatar !== ''): ?>
                    <img class="user-avatar-lg" src="<?= htmlspecialchars($current_user_avatar, ENT_QUOTES, 'UTF-8') ?>" alt="头像" width="72" height="72">
                <?php else: ?>
                    <span class="user-avatar-placeholder user-avatar-lg" aria-hidden="true"><?= htmlspecialchars($myInitial, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <div class="profile-text">
                <p class="user-meta user-meta-inline">
                    当前用户：<strong><?= superClean($_SESSION['name']) ?></strong>
                    <?php if ($current_user_role == 'admin'): ?>
                        <span class="badge-admin">管理员</span>
                    <?php endif; ?>
                </p>
                <form method="post" enctype="multipart/form-data" class="avatar-upload-form">
                    <label for="avatar_file">更换头像（JPG / PNG，最大约 5MB）</label>
                    <div class="avatar-upload-actions">
                        <input type="file" id="avatar_file" name="avatar" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required>
                        <button type="submit" name="upload_avatar" value="1">上传头像</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="toolbar">
        <button type="button" class="btn-secondary" onclick="location.href='logout.php'">退出登录</button>
        <?php if ($current_user_role == 'admin'): ?>
            <button type="button" onclick="location.href='admin.php'">管理员后台</button>
        <?php endif; ?>
    </div>
    <?php if ($success): ?>
        <p class="success"><?= superClean($success) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?= superClean($error) ?></p>
    <?php endif; ?>

    <h3>发表留言</h3>
    <form method="post" class="compose-form">
        <textarea name="content" rows="4" placeholder="写点什么..."></textarea>
        <button type="submit">发表</button>
    </form>

    <h3>最新留言</h3>
    <?php if (count($messages) > 0): ?>
        <div class="message-list">
            <?php foreach ($messages as $row): ?>
                <article class="message-item">
                    <div class="message-meta">
                        <div class="message-meta-main">
                            <?php
                            $authorAv = isset($row['author_avatar']) ? trim((string)$row['author_avatar']) : '';
                            $authorName = (string)($row['real_name'] ?? '');
                            $authorInitial = mb_substr($authorName !== '' ? $authorName : '?', 0, 1, 'UTF-8');
                            ?>
                            <?php if ($authorAv !== ''): ?>
                                <img class="user-avatar-sm" src="<?= htmlspecialchars($authorAv, ENT_QUOTES, 'UTF-8') ?>" alt="" width="40" height="40">
                            <?php else: ?>
                                <span class="user-avatar-placeholder user-avatar-sm" aria-hidden="true"><?= htmlspecialchars($authorInitial, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <div class="message-meta-text">
                                <span class="message-author"><?= superClean($row['real_name']) ?></span>
                                <span class="message-time"><?= htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                        <?php if ($row['user_id'] == $current_user_id || $current_user_role == 'admin'): ?>
                            <a class="message-delete" href="?id=<?= (int)$row['id'] ?>" onclick="return confirm('确定删除这条留言？')">删除</a>
                        <?php endif; ?>
                    </div>
                    <div class="message-body"><?= nl2br(superClean($row['content'])) ?></div>
                    <?php if (!empty($appendsByMessage[(int)$row['id']])): ?>
                        <div class="message-appends" role="list">
                            <?php foreach ($appendsByMessage[(int)$row['id']] as $ap): ?>
                                <div class="message-append-block" role="listitem">
                                    <div class="message-append-meta">
                                        <?php
                                        $apAv = isset($ap['append_author_avatar']) ? trim((string)$ap['append_author_avatar']) : '';
                                        $apName = (string)($ap['append_author'] ?? '');
                                        $apInitial = mb_substr($apName !== '' ? $apName : '?', 0, 1, 'UTF-8');
                                        ?>
                                        <?php if ($apAv !== ''): ?>
                                            <img class="user-avatar-xs" src="<?= htmlspecialchars($apAv, ENT_QUOTES, 'UTF-8') ?>" alt="" width="28" height="28">
                                        <?php else: ?>
                                            <span class="user-avatar-placeholder user-avatar-xs" aria-hidden="true"><?= htmlspecialchars($apInitial, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                        <span class="message-append-tag">追加</span>
                                        <span class="message-append-author"><?= superClean($ap['append_author'] ?? '') ?></span>
                                        <span class="message-append-time"><?= htmlspecialchars($ap['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="message-append-body"><?= nl2br(superClean($ap['content'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" class="append-form">
                        <input type="hidden" name="append_message_id" value="<?= (int)$row['id'] ?>">
                        <label class="append-label" for="append_content_<?= (int)$row['id'] ?>">追加一条</label>
                        <textarea id="append_content_<?= (int)$row['id'] ?>" name="append_content" rows="2" placeholder="在此留言下补充或回复…"></textarea>
                        <button type="submit" name="append_submit" value="1">追加发表</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-hint">暂无留言，来发第一条吧。</p>
    <?php endif; ?>

    <h3>修改密码</h3>
    <form method="post" class="password-form">
        <label for="new_password">新密码</label>
        <input id="new_password" type="password" name="new_password" autocomplete="new-password" required>
        <label for="confirm_password">确认密码</label>
        <input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" required>
        <button type="submit" name="change_password" value="1">修改密码</button>
    </form>
</div>
</body>
</html>