<?php
// Hata raporlamayı aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Veritabanı bağlantı bilgileri
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "discord_clone";

// Global değişken olarak tanımla
global $conn;

try {
    // Bağlantıyı oluştur
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Bağlantı hatasını kontrol et
    if ($conn->connect_error) {
        die("Veritabanı bağlantı hatası: " . $conn->connect_error);
    }
    
    // Türkçe karakter desteği
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Hata: " . $e->getMessage());
}

// Bağlantıyı test et
if (!$conn->ping()) {
    die("Veritabanı bağlantısı kopuk!");
}
?> 