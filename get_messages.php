<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    exit(json_encode(['error' => 'No autorizado']));
}

// Buscar la conversación entre los usuarios
$stmt = $pdo->prepare("
    SELECT c.id 
    FROM conversations c
    JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
    JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
    WHERE (cp1.user_id = ? AND cp2.user_id = ?)
    OR (cp1.user_id = ? AND cp2.user_id = ?)
    GROUP BY c.id
    HAVING COUNT(DISTINCT cp1.user_id) = 2
");

$stmt->execute([
    $_SESSION['user_id'], 
    $_GET['user_id'],
    $_GET['user_id'],
    $_SESSION['user_id']
]);

$conversation = $stmt->fetch();

if ($conversation) {
    // Obtener mensajes
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.full_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.conversation_id = ? 
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversation['id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear mensajes para la respuesta
    $formattedMessages = array_map(function($message) {
        return [
            'text' => $message['message_text'],
            'sender_name' => $message['full_name'],
            'is_mine' => $message['sender_id'] == $_SESSION['user_id'],
            'time' => date('H:i', strtotime($message['created_at']))
        ];
    }, $messages);
    
    echo json_encode(['messages' => $formattedMessages]);
} else {
    echo json_encode(['messages' => []]);
}
?>
