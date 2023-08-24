<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require 'pdo_connection.php'; // Include your PDO connection

class ChatMessage {
    public $sender;
    public $content;

    public function __construct($sender, $content) {
        $this->sender = $sender;
        $this->content = $content;
    }
}

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $messageHistory;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->messageHistory = array();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        // Send message history to the new client
        foreach ($this->messageHistory as $message) {
            $conn->send(json_encode($message));
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection(s)' . "\n",
            $from->resourceId, $msg, $numRecv);

        // Parse the JSON message received from the client
        $messageData = json_decode($msg, true);

        // Store the message in the history with sender information
        $message = new ChatMessage($messageData['sender'], $messageData['content']);
        $this->messageHistory[] = $message;

        // Broadcast the message to all connected clients in the same room
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send(json_encode($message));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

try {
    // Fetch rooms from the database
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE port IS NULL");
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $portCounter = 8080;

    // Create WebSocket servers for each room
    foreach ($rooms as $room) {
        $port = $portCounter++;

        // Update the room record with the assigned port
        $updateStmt = $pdo->prepare("UPDATE rooms SET port = :port WHERE id = :id");
        $updateStmt->bindParam(':port', $port);
        $updateStmt->bindParam(':id', $room['id']);
        $updateStmt->execute();

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new Chat()
                )
            ),
            $port
        );

        echo "WebSocket server for room '{$room['name']}' listening on port {$port}\n";

        $server->run();
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}