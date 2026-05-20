<?php
/**
 * 为 username 表增加头像字段（若不存在）。
 */
function ensure_username_avatar_column(PDO $pdo): void
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $chk = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $chk->execute([$db, 'username', 'avatar']);
    if ((int) $chk->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE username ADD COLUMN avatar VARCHAR(512) NULL DEFAULT NULL');
    }
}
