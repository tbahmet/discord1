<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Profil resmi yükleme
    if (isset($_FILES['profile_image'])) {
        $target_dir = "uploads/";
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = "profile_" . $user_id . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $new_filename, $user_id);
            $stmt->execute();
        }
    }

    // Renk güncelleme
    if (isset($_POST['color'])) {
        $color = $_POST['color'];
        $stmt = $conn->prepare("UPDATE users SET color = ? WHERE id = ?");
        $stmt->bind_param("si", $color, $user_id);
        $stmt->execute();
    }

    header("Location: discord.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Ayarları</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="profile-container">
        <h2>Profil Ayarları</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Profil Resmi</label>
                <div class="profile-image-preview">
                    <img src="uploads/<?php echo $user['profile_image']; ?>" alt="Profil" id="preview">
                </div>
                <input type="file" name="profile_image" accept="image/*" onchange="previewImage(this)">
            </div>
            <div class="form-group">
                <label>İsim Rengi</label>
                <input type="color" name="color" value="<?php echo $user['color']; ?>">
            </div>
            <div class="form-group">
                <button type="submit">Kaydet</button>
                <a href="discord.php" class="btn-secondary">Geri Dön</a>
            </div>
        </form>
    </div>

    <script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html> 