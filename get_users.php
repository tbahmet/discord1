<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Oturum bulunamadı']);
    exit();
}

try {
    $query = "SELECT id, username, profile_image, color FROM users ORDER BY username";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'profile_image' => $row['profile_image'],
            'color' => $row['color'],
            'is_current_user' => ($row['id'] == $_SESSION['user_id'])
        ];
    }

    echo json_encode($users);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 