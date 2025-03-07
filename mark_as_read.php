<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['sender_id'])) {
    exit(json_encode(['error' => 'No autorizado']));
}

try {
    // Marcar como leídos todos los mensajes del remitente para este usuario
    $stmt = $pdo->prepare("
        DELETE um FROM unread_messages um
        JOIN messages m ON um.message_id = m.id
        WHERE um.user_id = ? AND m.sender_id = ?
    ");
    
    $stmt->execute([$_SESSION['user_id'], $_POST['sender_id']]);
    
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
