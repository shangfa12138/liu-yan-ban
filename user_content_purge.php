<?php
/**
 * 仅允许删除项目 upload 目录下由本系统生成的相对路径文件。
 */
function delete_upload_file_if_safe(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }
    $relativePath = str_replace('\\', '/', trim($relativePath));
    if (strpos($relativePath, '..') !== false) {
        return;
    }
    if (!preg_match('#^upload/[a-f0-9]{32}\.(?:jpe?g|png)$#i', $relativePath)) {
        return;
    }
    $full = __DIR__ . '/' . $relativePath;
    if (is_file($full)) {
        @unlink($full);
    }
}

/**
 * 删除某用户的所有主留言、相关追加，以及该用户在他人的留言下的追加记录。
 */
function purge_user_messages_and_appends(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('SELECT id FROM liuyanban WHERE user_id = ?');
    $stmt->execute([$userId]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM liuyanban_append WHERE message_id IN ($placeholders)")->execute($ids);
    }
    $pdo->prepare('DELETE FROM liuyanban WHERE user_id = ?')->execute([$userId]);
    $pdo->prepare('DELETE FROM liuyanban_append WHERE user_id = ?')->execute([$userId]);
}
