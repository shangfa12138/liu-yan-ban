<?php
session_start();
include 'pdo_connect.php';
require "清洁函数.php";
include 'check.php';
require_once __DIR__ . '/ensure_liuyanban_append.php';
require_once __DIR__ . '/ensure_username_avatar.php';
require_once __DIR__ . '/user_content_purge.php';
ensure_liuyanban_append_table($pdo);
ensure_username_avatar_column($pdo);
if ($_SESSION['role'] !== 'admin') die("无权访问");
$msg = $x = '';
$imgPath = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user_role'])) {
        $pdo->prepare("UPDATE username SET role = :role WHERE id = :id AND id != 1")
                ->execute(['role' => $_POST['role'], 'id' => (int)$_POST['user_id']]);
        $msg = "用户角色已更新";
    }
    elseif (isset($_POST['delete_user'])) {
        $uid = (int)$_POST['user_id'];
        if ($uid === (int)$_SESSION['user_id']) {
            $msg = "不能删除自己";
        } elseif ($uid === 1) {
            $msg = "不能删除该账号";
        } else {
            $avStmt = $pdo->prepare('SELECT avatar FROM username WHERE id = ? AND id != 1');
            $avStmt->execute([$uid]);
            $oldAvatar = $avStmt->fetchColumn();
            purge_user_messages_and_appends($pdo, $uid);
            $pdo->prepare('DELETE FROM username WHERE id = :id AND id != 1')->execute(['id' => $uid]);
            delete_upload_file_if_safe($oldAvatar !== false ? (string)$oldAvatar : null);
            $msg = "用户及其所有留言、追加记录已删除";
        }
    }
    elseif (isset($_POST['update_message'])) {
        $pdo->prepare("UPDATE liuyanban SET content = :content WHERE id = :id")
                ->execute(['content' => superClean($_POST['content']), 'id' => (int)$_POST['message_id']]);
        $msg = "留言已更新";
    }
    elseif (isset($_POST['delete_message'])) {
        $mid = (int)$_POST['message_id'];
        $pdo->prepare("DELETE FROM liuyanban_append WHERE message_id = :id")->execute(['id' => $mid]);
        $pdo->prepare("DELETE FROM liuyanban WHERE id = :id")->execute(['id' => $mid]);
        $msg = "留言已删除";
    }
    elseif (isset($_FILES['myfile']) && $_FILES['myfile']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['myfile'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $x = "上传失败";
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $type = finfo_file($finfo, $file['tmp_name']);
            $info = @getimagesize($file['tmp_name']);
            $content = file_get_contents($file['tmp_name']);
            $errors = [];
            if (!in_array($ext, ['jpg','jpeg','png']))
                $errors[] = "扩展名不允许";
            if (!in_array($type, ['image/jpeg','image/png']))
                $errors[] = "MIME类型不允许";
            if ($info === false) $errors[] = "不是图片";
            if ($info && ($info[0] > 5000 || $info[1] > 5000))
                $errors[] = "分辨率过大";
            if ($file['size'] > 20 * 1024 * 1024)
                $errors[] = "文件太大";
            if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false)
                $errors[] = "疑似恶意文件";
            if (empty($errors)) {
                $uploadDir = __DIR__ . "/upload/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $newname = bin2hex(random_bytes(16)) . "." . $ext;
                $target = $uploadDir . $newname;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $imgPath = "upload/" . $newname;
                    $x = "上传成功";
                } else {
                    $x = "保存失败";
                }
            } else {
                $x = implode("；", $errors);
            }
        }
    }
}
$users = $pdo->query("SELECT id, name, email, role, created_at FROM username ORDER BY id")->fetchAll();
$messages = $pdo->query("SELECT l.*, u.name as real_name FROM liuyanban l LEFT JOIN username u ON l.user_id = u.id ORDER BY l.id DESC")->fetchAll();
$appendsByMessage = [];
if (count($messages) > 0) {
    $msgIds = array_map('intval', array_column($messages, 'id'));
    $placeholders = implode(',', array_fill(0, count($msgIds), '?'));
    $apStmt = $pdo->prepare("SELECT a.*, u.name AS append_author FROM liuyanban_append a
        LEFT JOIN username u ON a.user_id = u.id
        WHERE a.message_id IN ($placeholders) ORDER BY a.id ASC");
    $apStmt->execute($msgIds);
    foreach ($apStmt->fetchAll(PDO::FETCH_ASSOC) as $ap) {
        $appendsByMessage[(int)$ap['message_id']][] = $ap;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理后台</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>管理后台</h2>
    <div class="toolbar">
        <button type="button" class="btn-secondary" onclick="location.href='message_board.php'">返回留言板</button>
        <button type="button" class="btn-secondary" onclick="location.href='logout.php'">退出登录</button>
    </div>
<?php if ($msg): ?>
    <p class="success"><?= superClean($msg) ?></p>
<?php endif; ?>

<h3>用户管理</h3>
<table>
    <tr><th>ID</th><th>用户名</th><th>邮箱</th><th>角色</th><th>时间</th><th>操作</th></tr>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= superClean($u['name']) ?></td>
            <td><?= superClean($u['email']) ?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <select name="role">
                        <option value="user" <?= $u['role']=='user'?'selected':'' ?>>user</option>
                        <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>admin</option>
                    </select>
                    <button name="update_user_role">修改</button>
                </form>
            </td>
            <td><?= $u['created_at'] ?></td>
            <td>
                <form method="post" onsubmit="return confirm('确定删除该用户？其所有主留言、追加内容及头像文件将一并删除。')">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button name="delete_user" <?= ($u['id']==$_SESSION['user_id'] || (int)$u['id']===1)?'disabled':'' ?>>删除用户</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<h3>留言管理</h3>
<table>
    <tr><th>ID</th><th>用户</th><th>内容</th><th>时间</th><th>操作</th></tr>
    <?php foreach ($messages as $m): ?>
        <tr>
            <td><?= $m['id'] ?></td>
            <td><?= superClean($m['real_name'] ?? '') ?></td>
            <td>
                <form method="post" class="inline-form">
                    <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                    <input type="text" name="content" value="<?= superClean($m['content']) ?>" class="form-input">
                    <button name="update_message" class="btn-inline">修改</button>
                </form>
                <?php if (!empty($appendsByMessage[(int)$m['id']])): ?>
                    <div class="appends-list">
                        <?php foreach ($appendsByMessage[(int)$m['id']] as $ap): ?>
                            <div class="append-item">
                                <span class="append-author"><?= superClean($ap['append_author'] ?? '') ?></span>
                                <span class="append-time"><?= htmlspecialchars($ap['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                <p><?= nl2br(superClean($ap['content'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </td>
            <td><?= $m['created_at'] ?></td>
            <td>
                <form method="post" onsubmit="return confirm('删除？')">
                    <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                    <button name="delete_message">删除</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<h3>图片上传</h3>
<form method="POST" enctype="multipart/form-data" class="upload-form">
    <input type="file" name="myfile" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required>
    <button type="submit">上传</button>
</form>
<?php if ($x !== ''): ?>
    <p class="<?= $x === '上传成功' ? 'success' : 'error' ?>"><?= superClean($x) ?></p>
<?php endif; ?>

<?php if (!empty($imgPath)): ?>
    <p class="upload-preview-label">上传预览</p>
    <img class="upload-preview" src="<?= superClean($imgPath) ?>" alt="上传的图片" width="200">
<?php endif; ?>
</div>
</body>
</html>