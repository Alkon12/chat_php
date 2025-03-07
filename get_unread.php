<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['error' => 'No autorizado']));
}

try {
    // Obtener conteo de mensajes no leídos por usuario
    $stmt = $pdo->prepare("
        SELECT 
            m.sender_id,
            COUNT(um.message_id) as unread_count
        FROM messages m
        JOIN unread_messages um ON m.id = um.message_id
        WHERE um.user_id = ?
        GROUP BY m.sender_id
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $unread = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo json_encode(['unread' => $unread]);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
