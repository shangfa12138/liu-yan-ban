<?php
/**
 * 确保「追加留言」子表存在（无则自动创建）。
 */
function ensure_liuyanban_append_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS liuyanban_append (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        message_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_message_id (message_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
