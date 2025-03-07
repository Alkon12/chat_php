<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['message']) || !isset($_POST['receiver_id'])) {
    header("Location: chat.php");
    exit();
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // Buscar si ya existe una conversación entre estos usuarios
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
        $_POST['receiver_id'],
        $_POST['receiver_id'],
        $_SESSION['user_id']
    ]);
    
    $conversation = $stmt->fetch();
    $conversation_id = null;

    if ($conversation) {
        $conversation_id = $conversation['id'];
    } else {
        // Crear nueva conversación
        $stmt = $pdo->prepare("INSERT INTO conversations () VALUES ()");
        $stmt->execute();
        $conversation_id = $pdo->lastInsertId();
        
        // Agregar participantes
        $stmt = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)");
        $stmt->execute([
            $conversation_id, 
            $_SESSION['user_id'], 
            $conversation_id, 
            $_POST['receiver_id']
        ]);
    }
    
    // Insertar el mensaje
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)");
    $stmt->execute([
        $conversation_id,
        $_SESSION['user_id'],
        $_POST['message']
    ]);
    
    $message_id = $pdo->lastInsertId();
    
    // Marcar el mensaje como no leído para el receptor
    $stmt = $pdo->prepare("INSERT INTO unread_messages (message_id, user_id) VALUES (?, ?)");
    $stmt->execute([$message_id, $_POST['receiver_id']]);
    
    // Confirmar transacción
    $pdo->commit();

    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    // Revertir transacción en caso de error
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
?>
