<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);

    if ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 4) {
        $error = 'Пароль должен содержать не менее 4 символов';
    } else {
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);

        if ($checkStmt->rowCount() > 0) {
            $error = 'Пользователь с таким именем или email уже существует';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, full_name, phone)
                                   VALUES (?, ?, ?, ?, ?)");

            if ($stmt->execute([$username, $email, $password_hash, $full_name, $phone])) {
                $success = 'Успешная регистрация! Теперь вы можете войти';
                header("refresh:2;url=login.php");
            } else {
                $error = 'Ошибка при регистрации';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - CyberXFood</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>CyberX<span>Food</span></h1>
            <h2>Регистрация</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Имя пользователя " required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email " required>
                </div>
                <div class="form-group">
                    <input type="text" name="full_name" placeholder="Полное имя">
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Телефон">
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Пароль " required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Подтвердите пароль " required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
            </form>
            <p class="auth-link">Уже есть аккаунт? <a href="login.php">Войти</a></p>
        </div>
    </div>
</body>
</html>
