<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: discord.php");
            exit();
        } else {
            $error = "Hatalı şifre!";
        }
    } else {
        $error = "Kullanıcı bulunamadı!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Discord Benzeri</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Giriş Yap</h2>
        <?php if (isset($error)) echo "<p style='color: red; text-align: center;'>$error</p>"; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Giriş Yap</button>
            </div>
        </form>
        <p style="text-align: center; margin-top: 20px;">
            Hesabın yok mu? <a href="register.php" style="color: #7289da;">Kayıt Ol</a>
        </p>
    </div>
</body>
</html> 