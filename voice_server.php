<?php
require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;

class VoiceServer implements MessageComponentInterface {
    protected $clients;
    protected $users;
    protected $channels;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        $this->channels = [];
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Yeni bağlantı! ({$conn->resourceId})\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        
        switch($data->type) {
            case 'join':
                $this->users[$from->resourceId] = $data->userId;
                if (!isset($this->channels[$data->channel])) {
                    $this->channels[$data->channel] = [];
                }
                $this->channels[$data->channel][] = $from->resourceId;
                
                // Diğer kullanıcılara bildir
                foreach ($this->clients as $client) {
                    if ($from !== $client && in_array($client->resourceId, $this->channels[$data->channel])) {
                        $client->send(json_encode([
                            'type' => 'user-joined',
                            'userId' => $data->userId
                        ]));
                    }
                }
                break;
                
            case 'offer':
            case 'answer':
            case 'ice-candidate':
                // İlgili kullanıcıya ilet
                foreach ($this->clients as $client) {
                    if ($this->users[$client->resourceId] == $data->to) {
                        $client->send($msg);
                        break;
                    }
                }
                break;
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        $userId = $this->users[$conn->resourceId] ?? null;
        
        if ($userId) {
            // Diğer kullanıcılara ayrılma bilgisi gönder
            foreach ($this->clients as $client) {
                if ($conn !== $client) {
                    $client->send(json_encode([
                        'type' => 'user-left',
                        'userId' => $userId
                    ]));
                }
            }
            
            unset($this->users[$conn->resourceId]);
            
            // Kanallardan temizle
            foreach ($this->channels as &$channel) {
                $key = array_search($conn->resourceId, $channel);
                if ($key !== false) {
                    unset($channel[$key]);
                }
            }
        }
        
        $this->clients->detach($conn);
        echo "Bağlantı kapandı! ({$conn->resourceId})\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Hata: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new VoiceServer()
        )
    ),
    8080
);

echo "Ses sunucusu başlatıldı (port: 8080)\n";
$server->run(); 