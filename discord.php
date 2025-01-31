<?php
// Hata raporlamayı aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum başlat
session_start();

// Veritabanı bağlantısını dahil et
require_once 'config.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Global bağlantı değişkenini kullan
global $conn;

// Bağlantı kontrolü
if (!isset($conn)) {
    die("Veritabanı bağlantısı bulunamadı!");
}

try {
    // Kullanıcı bilgilerini al
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Sorgu hazırlanamadı: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user = $result->fetch_assoc();
    
    if (!$current_user) {
        throw new Exception("Kullanıcı bilgileri alınamadı!");
    }
    
} catch (Exception $e) {
    die("Hata: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Benzeri - Ana Sayfa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .discord-container {
            display: flex;
            height: 100vh;
            background-color: #36393f;
        }

        .server-list {
            width: 72px;
            background-color: #202225;
            padding: 12px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .server-icon {
            width: 48px;
            height: 48px;
            background-color: #36393f;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-radius 0.2s;
            position: relative;
        }

        .server-icon:hover {
            border-radius: 16px;
            background-color: #5865f2;
        }

        .server-icon.active {
            border-radius: 16px;
            background-color: #5865f2;
        }

        .server-icon.active::before {
            content: '';
            position: absolute;
            left: -16px;
            width: 8px;
            height: 40px;
            background-color: white;
            border-radius: 0 4px 4px 0;
        }

        .channels-container {
            width: 240px;
            background-color: #2f3136;
            display: flex;
            flex-direction: column;
        }

        .server-header {
            padding: 16px;
            border-bottom: 1px solid #202225;
            font-weight: bold;
            font-size: 16px;
        }

        .channel-list {
            padding: 8px;
            flex: 1;
            overflow-y: auto;
        }

        .channel-category {
            margin: 16px 0 8px;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 600;
            color: #96989d;
        }

        .channel {
            display: flex;
            align-items: center;
            padding: 6px 8px;
            margin: 2px 0;
            border-radius: 4px;
            cursor: pointer;
            color: #96989d;
            transition: background-color 0.2s;
        }

        .channel:hover {
            background-color: #36393f;
            color: #dcddde;
        }

        .channel.active {
            background-color: #393c43;
            color: #fff;
        }

        .channel i {
            margin-right: 6px;
        }

        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            height: 48px;
            padding: 0 16px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #202225;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }

        .messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message {
            display: flex;
            gap: 16px;
            padding: 2px 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
            position: relative;
        }

        .message:hover {
            background-color: #32353b;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .message-content {
            flex: 1;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .message-info {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .message-actions {
            display: none;
            position: absolute;
            right: 10px;
            top: 0;
            background-color: #36393f;
            border-radius: 4px;
            padding: 4px;
            box-shadow: 0 0 8px rgba(0,0,0,0.2);
        }

        .message:hover .message-actions {
            display: flex;
            align-items: center;
        }

        .delete-btn {
            background: none;
            border: none;
            color: #dcddde;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 3px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .delete-btn:hover {
            color: #f04747;
            background-color: rgba(240, 71, 71, 0.1);
        }

        .delete-btn i {
            font-size: 14px;
        }

        .message-input-container {
            padding: 16px;
            margin: 0 16px 16px;
            background-color: #40444b;
            border-radius: 8px;
        }

        .message-input {
            width: 100%;
            padding: 10px;
            background: transparent;
            border: none;
            color: #dcddde;
            font-size: 1rem;
        }

        .message-input:focus {
            outline: none;
        }

        .members-list {
            width: 240px;
            background-color: #2f3136;
            padding: 16px;
            overflow-y: auto;
        }

        .members-category {
            margin: 16px 0 8px;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 600;
            color: #96989d;
        }

        .member {
            display: flex;
            align-items: center;
            padding: 6px 8px;
            margin: 2px 0;
            border-radius: 4px;
            cursor: pointer;
        }

        .member:hover {
            background-color: #36393f;
        }

        .member-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .member-name {
            font-size: 14px;
            font-weight: 500;
        }

        .user-profile {
            height: 52px;
            padding: 0 8px;
            background-color: #292b2f;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .user-profile:hover {
            background-color: #232529;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: #36393f;
            border-radius: 8px;
            width: 440px;
            box-shadow: 0 0 20px rgba(0,0,0,0.4);
        }

        .modal-header {
            padding: 16px;
            border-bottom: 1px solid #202225;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: #fff;
            margin: 0;
            font-size: 20px;
        }

        .modal-close {
            background: none;
            border: none;
            color: #dcddde;
            cursor: pointer;
            font-size: 20px;
            padding: 4px;
        }

        .modal-close:hover {
            color: #fff;
        }

        .modal-body {
            padding: 16px;
        }

        .profile-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .profile-image-section {
            text-align: center;
        }

        #profile-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 16px;
        }

        .upload-btn {
            background-color: #5865f2;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }

        .upload-btn:hover {
            background-color: #4752c4;
        }

        .profile-info-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .color-picker-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .color-picker-section label {
            color: #dcddde;
        }

        #userColorPicker {
            width: 100%;
            height: 40px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn {
            background-color: #5865f2;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 16px;
            transition: background-color 0.2s;
        }

        .save-btn:hover {
            background-color: #4752c4;
        }

        /* Sesli sohbet stilleri */
        .voice-chat-container {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 250px;
            background-color: #36393f;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .voice-chat-header {
            padding: 12px;
            background-color: #2f3136;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .leave-voice-btn {
            background-color: #f04747;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .voice-users-list {
            padding: 12px;
            max-height: 200px;
            overflow-y: auto;
        }

        .voice-user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px;
            border-radius: 4px;
        }

        .voice-user img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .voice-controls {
            padding: 12px;
            border-top: 1px solid #202225;
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .voice-controls button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background-color: #2f3136;
            color: white;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .voice-controls button:hover {
            background-color: #40444b;
        }

        .voice-controls button.muted {
            background-color: #f04747;
        }

        .voice-controls button.muted:hover {
            background-color: #d84040;
        }

        /* Bildirim stilleri */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            animation: slideIn 0.3s ease-out;
            z-index: 9999;
        }

        .notification.success {
            background-color: #43b581;
        }

        .notification.error {
            background-color: #f04747;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Ses kanalı aktif stil */
        .channel.voice-active {
            background-color: #3ba55c !important;
            color: white !important;
        }

        /* Ses kontrolü stilleri */
        .voice-controls button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background-color: #36393f;
            color: #dcddde;
            cursor: pointer;
            transition: all 0.2s;
        }

        .voice-controls button:hover {
            background-color: #40444b;
        }

        .voice-controls button.muted {
            background-color: #f04747;
            color: white;
        }

        .voice-controls button.muted:hover {
            background-color: #d84040;
        }
    </style>
</head>
<body>
    <div class="discord-container">
        <!-- Sunucu Listesi -->
        <div class="server-list">
            <div class="server-icon active">
                <i class="fas fa-users fa-lg" style="color: white;"></i>
            </div>
        </div>

        <!-- Kanallar -->
        <div class="channels-container">
            <div class="server-header">
                Ahmetler
            </div>
            <div class="channel-list">
                <div class="channel-category">Metin Kanalları</div>
                <div class="channel active">
                    <i class="fas fa-hashtag"></i>
                    genel
                </div>
                <div class="channel-category">Ses Kanalları</div>
                <div class="channel" onclick="handleVoiceChannel(this)" data-channel="Sesli Sohbet">
                    <i class="fas fa-volume-up"></i>
                    Sesli Sohbet
                </div>
            </div>
            
            <!-- Kullanıcı Profili -->
            <div class="user-profile">
                <img src="uploads/<?php echo $current_user['profile_image']; ?>" alt="Profil" class="member-avatar">
                <div class="member-name" style="color: <?php echo $current_user['color']; ?>">
                    <?php echo $current_user['username']; ?>
                </div>
            </div>
        </div>

        <!-- Sohbet Alanı -->
        <div class="chat-container">
            <div class="chat-header">
                <i class="fas fa-hashtag" style="margin-right: 8px; color: #72767d;"></i>
                <span id="current-channel">genel</span>
            </div>
            
            <div class="messages" id="messages">
                <!-- Mesajlar buraya gelecek -->
            </div>
            
            <div class="message-input-container">
                <input type="text" id="message-input" class="message-input" 
                       placeholder="Mesaj gönder..." autocomplete="off">
            </div>
        </div>

        <!-- Üye Listesi -->
        <div class="members-list">
            <div class="members-category">Çevrimiçi — <span id="online-count">0</span></div>
            <div id="users-container">
                <!-- Kullanıcılar buraya gelecek -->
            </div>
        </div>
    </div>

    <!-- Profil ve Renk Seçici Modal -->
    <div id="profileModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Profil Ayarları</h3>
                <button class="modal-close" onclick="closeProfileModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="profile-section">
                    <div class="profile-image-section">
                        <img src="uploads/<?php echo $current_user['profile_image']; ?>" alt="Profil" id="profile-preview">
                        <label for="profile-upload" class="upload-btn">
                            <i class="fas fa-camera"></i>
                            Resim Değiştir
                        </label>
                        <input type="file" id="profile-upload" hidden accept="image/*">
                    </div>
                    <div class="profile-info-section">
                        <div class="color-picker-section">
                            <label>İsim Rengi</label>
                            <input type="color" id="userColorPicker" value="<?php echo $current_user['color']; ?>">
                        </div>
                        <button onclick="saveProfileSettings()" class="save-btn">
                            <i class="fas fa-save"></i> Kaydet
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sesli sohbet için yeni div ekleyelim -->
    <div id="voice-chat-container" class="voice-chat-container" style="display: none;">
        <div class="voice-chat-header">
            <span>🔊 Sesli Sohbet</span>
            <button onclick="leaveVoiceChat()" class="leave-voice-btn">
                <i class="fas fa-phone-slash"></i> Ayrıl
            </button>
        </div>
        <div class="voice-users-list">
            <!-- Sesli sohbetteki kullanıcılar burada listelenecek -->
        </div>
        <div class="voice-controls">
            <button id="muteBtn" onclick="toggleMute()">
                <i class="fas fa-microphone"></i>
            </button>
        </div>
    </div>

    <script>
        let currentChannel = 'genel';
        const messagesDiv = document.getElementById('messages');
        const messageInput = document.getElementById('message-input');
        const currentChannelSpan = document.getElementById('current-channel');

        // Mesaj gönderme fonksiyonu
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && this.value.trim()) {
                const message = this.value.trim();
                
                fetch('message_operations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send&message=${encodeURIComponent(message)}&channel=${encodeURIComponent(currentChannel)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.value = '';
                        loadMessages();
                    } else {
                        alert('Mesaj gönderilemedi!');
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    alert('Mesaj gönderilirken bir hata oluştu!');
                });
            }
        });

        // Mesajları yükle
        function loadMessages() {
            fetch(`message_operations.php?action=get&channel=${currentChannel}`)
                .then(response => response.json())
                .then(messages => {
                    messagesDiv.innerHTML = messages.map(msg => `
                        <div class="message">
                            <img src="uploads/${msg.profile_image}" alt="${msg.username}" class="message-avatar">
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="message-username" style="color: ${msg.color}">${msg.username}</span>
                                    <span class="message-timestamp">${new Date(msg.created_at).toLocaleString()}</span>
                                    ${msg.is_own ? `<button onclick="deleteMessage(${msg.id})" class="delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>` : ''}
                                </div>
                                <div class="message-text">${msg.message}</div>
                            </div>
                        </div>
                    `).join('');
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                });
        }

        // Kullanıcıları yükle
        function loadUsers() {
            fetch('get_users.php')
                .then(response => response.json())
                .then(users => {
                    const usersContainer = document.getElementById('users-container');
                    const onlineCount = document.querySelector('.members-category');
                    
                    if (users.length > 0) {
                        onlineCount.textContent = `Çevrimiçi — ${users.length}`;
                        usersContainer.innerHTML = users.map(user => `
                            <div class="member" ${user.is_current_user ? 'onclick="openProfileModal()"' : ''}>
                                <img src="uploads/${user.profile_image}" alt="${user.username}" class="member-avatar">
                                <div class="member-info">
                                    <span class="member-name" style="color: ${user.color}">
                                        ${user.username}${user.is_current_user ? ' (sen)' : ''}
                                    </span>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        usersContainer.innerHTML = '<div class="no-users">Çevrimiçi kullanıcı yok</div>';
                    }
                })
                .catch(error => {
                    console.error('Kullanıcılar yüklenirken hata:', error);
                });
        }

        // Mesaj silme fonksiyonu
        function deleteMessage(messageId) {
            if (confirm('Bu mesajı silmek istediğinize emin misiniz?')) {
                fetch('message_operations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&message_id=${messageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadMessages();
                    } else {
                        alert('Mesaj silinemedi!');
                    }
                });
            }
        }

        // Kanal değiştirme
        function changeChannel(channelName) {
            currentChannel = channelName;
            document.getElementById('current-channel').textContent = channelName;
            loadMessages();
            
            // Aktif kanalı vurgula
            document.querySelectorAll('.channel').forEach(ch => {
                ch.classList.remove('active');
                if (ch.textContent.trim() === channelName) {
                    ch.classList.add('active');
                }
            });
        }

        // Sayfa yüklendiğinde ve periyodik olarak güncelle
        document.addEventListener('DOMContentLoaded', () => {
            loadMessages();
            loadUsers();
            setInterval(loadMessages, 5000);
            setInterval(loadUsers, 10000);
        });

        // Profil modalını aç
        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'flex';
        }

        // Profil modalını kapat
        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
        }

        // Profil ayarlarını kaydet
        function saveProfileSettings() {
            const color = document.getElementById('userColorPicker').value;
            const formData = new FormData();
            formData.append('action', 'update_profile');
            formData.append('color', color);

            const fileInput = document.getElementById('profile-upload');
            if (fileInput.files.length > 0) {
                formData.append('profile_image', fileInput.files[0]);
            }

            fetch('profile_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadUsers();
                    loadMessages();
                    closeProfileModal();
                } else {
                    alert('Ayarlar kaydedilemedi!');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                alert('Ayarlar kaydedilirken bir hata oluştu!');
            });
        }

        // Profil resmi önizleme
        document.getElementById('profile-upload').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Modal dışına tıklayınca kapat
        document.getElementById('profileModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProfileModal();
            }
        });

        // ESC tuşu ile modalı kapat
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProfileModal();
            }
        });

        // Profil bar tıklama olayı
        document.querySelector('.user-profile').addEventListener('click', openProfileModal);

        // WebRTC değişkenleri ve ses sohbeti için JavaScript kodları
        let localStream = null;
        let peerConnections = {};
        let voiceChannel = null;
        let isMuted = false;

        // WebRTC konfigürasyonu
        const configuration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };

        // Peer bağlantılarını oluştur
        async function createPeerConnection(remoteUserId) {
            if (!peerConnections[remoteUserId]) {
                const pc = new RTCPeerConnection(configuration);
                peerConnections[remoteUserId] = pc;

                // Yerel ses akışını ekle
                if (localStream) {
                    localStream.getTracks().forEach(track => {
                        pc.addTrack(track, localStream);
                    });
                }

                // Uzak ses akışını al
                pc.ontrack = (event) => {
                    const remoteAudio = document.createElement('audio');
                    remoteAudio.id = `remote-audio-${remoteUserId}`;
                    remoteAudio.autoplay = true;
                    remoteAudio.srcObject = event.streams[0];
                    document.body.appendChild(remoteAudio);
                };

                // ICE adaylarını gönder
                pc.onicecandidate = (event) => {
                    if (event.candidate) {
                        voiceSocket.send(JSON.stringify({
                            type: 'ice-candidate',
                            candidate: event.candidate,
                            to: remoteUserId,
                            from: <?php echo $_SESSION['user_id']; ?>
                        }));
                    }
                };

                // Bağlantı durumunu izle
                pc.onconnectionstatechange = () => {
                    console.log(`Bağlantı durumu (${remoteUserId}):`, pc.connectionState);
                };
            }
            return peerConnections[remoteUserId];
        }

        // Teklif oluştur ve gönder
        async function createAndSendOffer(remoteUserId) {
            const pc = await createPeerConnection(remoteUserId);
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            
            voiceSocket.send(JSON.stringify({
                type: 'offer',
                offer: offer,
                to: remoteUserId,
                from: <?php echo $_SESSION['user_id']; ?>
            }));
        }

        // Teklifi işle
        async function handleOffer(data) {
            const pc = await createPeerConnection(data.from);
            await pc.setRemoteDescription(new RTCSessionDescription(data.offer));
            
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            
            voiceSocket.send(JSON.stringify({
                type: 'answer',
                answer: answer,
                to: data.from,
                from: <?php echo $_SESSION['user_id']; ?>
            }));
        }

        // Cevabı işle
        async function handleAnswer(data) {
            const pc = peerConnections[data.from];
            if (pc) {
                await pc.setRemoteDescription(new RTCSessionDescription(data.answer));
            }
        }

        // ICE adayını işle
        async function handleIceCandidate(data) {
            const pc = peerConnections[data.from];
            if (pc) {
                await pc.addIceCandidate(new RTCIceCandidate(data.candidate));
            }
        }

        // WebSocket mesajlarını işle
        function handleVoiceServerMessage(data) {
            switch(data.type) {
                case 'user-joined':
                    if (data.userId !== <?php echo $_SESSION['user_id']; ?>) {
                        createAndSendOffer(data.userId);
                        updateVoiceUsersList();
                    }
                    break;
                    
                case 'user-left':
                    if (peerConnections[data.userId]) {
                        peerConnections[data.userId].close();
                        delete peerConnections[data.userId];
                        const remoteAudio = document.getElementById(`remote-audio-${data.userId}`);
                        if (remoteAudio) remoteAudio.remove();
                        updateVoiceUsersList();
                    }
                    break;
                    
                case 'offer':
                    handleOffer(data);
                    break;
                    
                case 'answer':
                    handleAnswer(data);
                    break;
                    
                case 'ice-candidate':
                    handleIceCandidate(data);
                    break;
            }
        }

        // WebSocket bağlantısını güncelle
        function connectToVoiceServer() {
            voiceSocket = new WebSocket('ws://localhost:8080');
            
            voiceSocket.onopen = () => {
                console.log('Ses sunucusuna bağlanıldı');
                voiceSocket.send(JSON.stringify({
                    type: 'join',
                    channel: voiceChannel,
                    userId: <?php echo $_SESSION['user_id']; ?>
                }));
            };
            
            voiceSocket.onmessage = async (event) => {
                const data = JSON.parse(event.data);
                await handleVoiceServerMessage(data);
            };

            voiceSocket.onerror = (error) => {
                console.error('WebSocket hatası:', error);
                showError('Ses sunucusuna bağlanırken hata oluştu');
            };

            voiceSocket.onclose = () => {
                console.log('WebSocket bağlantısı kapandı');
                setTimeout(connectToVoiceServer, 5000); // Yeniden bağlanmayı dene
            };
        }

        // Ses seviyesi göstergesi
        function setupVoiceMeter() {
            if (localStream) {
                const audioContext = new AudioContext();
                const source = audioContext.createMediaStreamSource(localStream);
                const analyser = audioContext.createAnalyser();
                analyser.fftSize = 256;
                
                source.connect(analyser);
                const bufferLength = analyser.frequencyBinCount;
                const dataArray = new Uint8Array(bufferLength);
                
                function updateMeter() {
                    analyser.getByteFrequencyData(dataArray);
                    let sum = 0;
                    for(let i = 0; i < bufferLength; i++) {
                        sum += dataArray[i];
                    }
                    const average = sum / bufferLength;
                    
                    // Konuşma göstergesini güncelle
                    const speakingIndicator = document.querySelector('.speaking-indicator');
                    if (speakingIndicator) {
                        speakingIndicator.style.opacity = average > 30 ? '1' : '0';
                    }
                    
                    requestAnimationFrame(updateMeter);
                }
                
                updateMeter();
            }
        }

        // Ses kanalına katılma fonksiyonu
        async function joinVoiceChat(channelName) {
            try {
                // Önce tarayıcı desteğini kontrol et
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Tarayıcınız WebRTC desteklemiyor!');
                }

                // Mikrofon izinlerini kontrol et ve iste
                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    },
                    video: false
                });

                localStream = stream;
                voiceChannel = channelName;
                
                // Ses konteynerini göster
                document.getElementById('voice-chat-container').style.display = 'block';
                
                // Aktif ses kanalını vurgula
                document.querySelectorAll('.channel').forEach(ch => {
                    ch.classList.remove('voice-active');
                    if (ch.textContent.includes(channelName)) {
                        ch.classList.add('voice-active');
                    }
                });

                // Kullanıcı listesini güncelle
                updateVoiceUsersList();
                
                console.log('Ses kanalına başarıyla katıldınız:', channelName);
                
                // Başarılı katılım mesajı göster
                showNotification('Ses kanalına katıldınız: ' + channelName);

            } catch (error) {
                console.error('Mikrofon erişim hatası:', error);
                
                let errorMessage = 'Mikrofona erişilemedi! ';
                if (error.name === 'NotAllowedError') {
                    errorMessage += 'Lütfen tarayıcı izinlerini kontrol edin.';
                } else if (error.name === 'NotFoundError') {
                    errorMessage += 'Mikrofon bulunamadı.';
                } else {
                    errorMessage += error.message;
                }
                
                showError(errorMessage);
            }
        }

        // Bildirim gösterme fonksiyonu
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'notification success';
            notification.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        // Hata gösterme fonksiyonu
        function showError(message) {
            const error = document.createElement('div');
            error.className = 'notification error';
            error.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(error);
            setTimeout(() => error.remove(), 5000);
        }

        // Ses kanalından ayrılma
        function leaveVoiceChat() {
            if (localStream) {
                localStream.getTracks().forEach(track => {
                    track.stop();
                });
                localStream = null;
            }
            
            document.getElementById('voice-chat-container').style.display = 'none';
            
            // Aktif ses kanalı vurgusunu kaldır
            document.querySelectorAll('.channel').forEach(ch => {
                ch.classList.remove('voice-active');
            });
            
            voiceChannel = null;
            showNotification('Ses kanalından ayrıldınız');
        }

        // Mikrofon kontrolü
        function toggleMute() {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                isMuted = !isMuted;
                audioTrack.enabled = !isMuted;
                
                const muteBtn = document.getElementById('muteBtn');
                muteBtn.innerHTML = isMuted ? 
                    '<i class="fas fa-microphone-slash"></i>' : 
                    '<i class="fas fa-microphone"></i>';
                muteBtn.classList.toggle('muted', isMuted);
                
                showNotification(isMuted ? 'Mikrofon kapatıldı' : 'Mikrofon açıldı');
            }
        }

        // Ses kanalındaki kullanıcıları güncelle
        function updateVoiceUsersList() {
            const usersList = document.querySelector('.voice-users-list');
            // Aktif kullanıcıları getir ve listele
            fetch('get_voice_users.php?channel=' + voiceChannel)
                .then(response => response.json())
                .then(users => {
                    usersList.innerHTML = users.map(user => `
                        <div class="voice-user">
                            <img src="uploads/${user.profile_image}" alt="${user.username}">
                            <span style="color: ${user.color}">${user.username}</span>
                            ${user.is_speaking ? '<i class="fas fa-signal"></i>' : ''}
                        </div>
                    `).join('');
                });
        }

        // Ses kanalı tıklama işleyicisi
        function handleVoiceChannel(element) {
            const channelName = element.dataset.channel;
            
            if (voiceChannel === channelName) {
                leaveVoiceChat();
            } else {
                joinVoiceChat(channelName);
            }
        }
    </script>
</body>
</html> 