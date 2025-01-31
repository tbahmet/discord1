<?php
session_start();
require_once 'config.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum açık değil']);
    exit();
}

// POST istekleri için
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // action parametresi kontrolü
    if (!isset($_POST['action'])) {
        echo json_encode(['success' => false, 'error' => 'Action parametresi eksik']);
        exit();
    }

    // Mesaj gönderme
    if ($_POST['action'] == 'send') {
        if (!isset($_POST['message']) || !isset($_POST['channel'])) {
            echo json_encode(['success' => false, 'error' => 'Eksik parametreler']);
            exit();
        }

        $message = $conn->real_escape_string($_POST['message']);
        $channel = $conn->real_escape_string($_POST['channel']);
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO messages (user_id, channel, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $channel, $message);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }

    // Mesaj silme
    if ($_POST['action'] == 'delete') {
        $message_id = $conn->real_escape_string($_POST['message_id']);
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $message_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
}

// Mesajları getirme
if ($_GET['action'] == 'get') {
    $channel = $conn->real_escape_string($_GET['channel']);
    
    $query = "SELECT messages.*, users.username, users.profile_image, users.color 
              FROM messages 
              JOIN users ON messages.user_id = users.id 
              WHERE channel = ? 
              ORDER BY created_at ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $channel);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'message' => $row['message'],
            'created_at' => $row['created_at'],
            'is_own' => $row['user_id'] == $_SESSION['user_id'],
            'profile_image' => $row['profile_image'],
            'color' => $row['color']
        ];
    }
    
    echo json_encode($messages);
}
?> 