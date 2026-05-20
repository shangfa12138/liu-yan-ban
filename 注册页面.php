<?php
include 'pdo_connect.php';
require "清洁函数.php";
$errors = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = superClean($_POST["name"] ?? "");
    $password = $_POST["password"] ?? "";
    $email = superClean($_POST["email"] ?? "");
    if (empty($name)) {
        $errors = "用户名不能为空";
    } elseif (!preg_match("/^[a-zA-Z0-9_]{3,10}$/", $name)) {
        $errors = "用户名必须3-10位字母/数字/下划线";
    } elseif (empty($password)) {
        $errors = "密码不能为空";
    } elseif (strlen($password) < 6) {
        $errors = "密码至少6位";
    } elseif (empty($email)) {
        $errors = "邮箱不能为空";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors = "邮箱格式错误";
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM username WHERE name = :name");
        $stmt->execute(['name' => $name]);
        if ($stmt->rowCount() > 0) {
            $errors = "用户名已存在";
        }
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM username WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->rowCount() > 0) {
            $errors = "邮箱已被注册";
        }
    }
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO username (name, password, email, role) 
                VALUES(:name, :pwd, :email, :role)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute(['name' => $name, 'pwd' => $hashed, 'email' => $email, 'role' => 'user'])) {
            header("Location: login.php");
            exit;
        } else {
            $errors = "注册失败";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注册</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <h2>注册新账号</h2>
    <?php if (!empty($errors)) : ?>
        <p class="error"><?php echo superClean($errors); ?></p>
    <?php endif; ?>
    <form method="post">
        <p>
            <label for="reg_name">用户名</label>
            <input id="reg_name" type="text" name="name" required autocomplete="username" minlength="3" maxlength="10" pattern="[a-zA-Z0-9_]{3,10}" title="3-10 位字母、数字或下划线">
        </p>
        <p>
            <label for="reg_email">邮箱</label>
            <input id="reg_email" type="email" name="email" required autocomplete="email">
        </p>
        <p>
            <label for="reg_password">密码</label>
            <input id="reg_password" type="password" name="password" required autocomplete="new-password" minlength="6">
        </p>
        <p class="btn-row">
            <button type="submit">注册</button>
            <button type="button" class="btn-secondary" onclick="location.href='login.php'">返回登录</button>
        </p>
    </form>
</div>
</body>
</html>