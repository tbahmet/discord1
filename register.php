<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check_email = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    
    if ($check_email->get_result()->num_rows > 0) {
        $error = "Bu e-posta adresi zaten kullanımda!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);
        
        if ($stmt->execute()) {
            header("Location: login.php?registered=true");
            exit();
        } else {
            $error = "Kayıt sırasında bir hata oluştu!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Discord Benzeri</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Kayıt Ol</h2>
        <?php if (isset($error)) echo "<p style='color: red; text-align: center;'>$error</p>"; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Kayıt Ol</button>
            </div>
        </form>
        <p style="text-align: center; margin-top: 20px;">
            Zaten hesabın var mı? <a href="login.php" style="color: #7289da;">Giriş Yap</a>
        </p>
    </div>
</body>
</html> 