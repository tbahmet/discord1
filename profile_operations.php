<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum açık değil']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'update_profile') {
    $user_id = $_SESSION['user_id'];
    $updates = [];
    $params = [];
    $types = "";

    // Renk güncelleme
    if (isset($_POST['color'])) {
        $updates[] = "color = ?";
        $params[] = $_POST['color'];
        $types .= "s";
    }

    // Profil resmi güncelleme
    if (isset($_FILES['profile_image'])) {
        $file = $_FILES['profile_image'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = "profile_" . $user_id . "_" . time() . "." . $ext;
        $target_path = "uploads/" . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $updates[] = "profile_image = ?";
            $params[] = $new_filename;
            $types .= "s";
        }
    }

    if (!empty($updates)) {
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Güncellenecek veri yok']);
    }
}
?> 