<?php
require "清洁函数.php";
include 'pdo_connect.php';
$errors = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = superClean($_POST["name"] ?? "");
    $email = superClean($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $password2 = $_POST["password2"] ?? "";
    if (empty($name) || empty($email)) {
        $errors = "用户名和邮箱不能为空";
    } elseif (empty($password) || empty($password2)) {
        $errors = "密码不能为空";
    } elseif ($password !== $password2) {
        $errors = "两次密码不一致";
    } elseif (strlen($password) < 6) {
        $errors = "密码至少6位";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM username WHERE name = :name AND email = :email");
        $stmt->execute(['name' => $name, 'email' => $email]);

        if ($stmt->rowCount() == 0) {
            $errors = "用户名或邮箱错误";
        }
    }

    if (empty($errors)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);

        $up = $pdo->prepare("UPDATE username SET password = :pwd WHERE name = :name AND email = :email");

        if ($up->execute([
                'pwd' => $newHash,
                'name' => $name,
                'email' => $email
        ])) {
            header("Location: login.php");
            exit;
        } else {
            $errors = "重置失败，请稍后重试";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>重置密码</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <h2>重置密码</h2>
    <?php if (!empty($errors)) : ?>
        <p class="error"><?php echo superClean($errors); ?></p>
    <?php endif; ?>
    <form method="post" onsubmit="return confirm('确认修改密码吗？')">
        <p>
            <label for="reset_name">用户名</label>
            <input id="reset_name" type="text" name="name" required autocomplete="username">
        </p>
        <p>
            <label for="reset_email">邮箱</label>
            <input id="reset_email" type="email" name="email" required autocomplete="email">
        </p>
        <p>
            <label for="reset_password">新密码</label>
            <input id="reset_password" type="password" name="password" required autocomplete="new-password" minlength="6">
        </p>
        <p>
            <label for="reset_password2">确认密码</label>
            <input id="reset_password2" type="password" name="password2" required autocomplete="new-password" minlength="6">
        </p>
        <p class="btn-row">
            <button type="submit">重置密码</button>
            <button type="button" class="btn-secondary" onclick="location.href='login.php'">返回登录</button>
        </p>
    </form>
</div>
</body>
</html>