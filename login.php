<?php
session_start();
include 'pdo_connect.php';
require "清洁函数.php";
$errors = "";
$username = "";
if (isset($_COOKIE["username"])) {
    $username = $_COOKIE["username"];
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $login_input = superClean($_POST["name"] ?? "");
    $password = $_POST["password"] ?? "";
    $remember = isset($_POST["remember"]);
    if (empty($login_input)) {
        $errors = "用户名/邮箱不能为空";
    } elseif (empty($password)) {
        $errors = "密码不能为空";
    } elseif (strlen($password) < 6) {
        $errors = "密码至少6位";
    }

    if (empty($errors)) {
        if (filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT id, name, password, role FROM username WHERE email = :login_input";
        } else {
            if (!preg_match("/^[a-zA-Z0-9_]{3,10}$/", $login_input)) {
                $errors = "用户名必须3-10位字母/数字/下划线";
            } else {
                $sql = "SELECT id, name, password, role FROM username WHERE name = :login_input";
            }
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['login_input' => $login_input]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                if ($remember) {
                    setcookie("username", $user['name'], time() + 7 * 24 * 60 * 60);
                } else {
                    setcookie("username", "", time() - 3600);
                }
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                header("Location: message_board.php");
                exit();
            } else {
                $errors = "用户名或密码错误";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>登录</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <h2>登录</h2>
    <?php if (!empty($errors)) : ?>
        <p class="error"><?php echo superClean($errors); ?></p>
    <?php endif; ?>
    <form action="" method="post">
        <p>
            <label for="login_name">用户名/邮箱</label>
            <input id="login_name" type="text" name="name" value="<?php echo superClean($username); ?>" required autocomplete="username">
        </p>
        <p>
            <label for="login_password">密码</label>
            <input id="login_password" type="password" name="password" required autocomplete="current-password">
        </p>
        <p class="remember-row">
            <label class="checkbox-label">
                <input type="checkbox" name="remember" <?php if (isset($_COOKIE["username"])) echo "checked"; ?>>
                记住用户名
            </label>
        </p>
        <p class="btn-row">
            <button type="submit" name="login" value="1">登录</button>
            <button type="button" class="btn-secondary" onclick="location.href='注册页面.php'">注册</button>
            <button type="button" class="btn-secondary" onclick="location.href='重置密码.php'">重置密码</button>
        </p>
    </form>
</div>
</body>
</html>