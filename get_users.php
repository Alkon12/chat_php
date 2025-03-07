<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['error' => 'No autorizado']));
}

try {
    // Obtener la lista de usuarios ordenados por último mensaje
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            u.id, 
            u.username, 
            u.full_name, 
            u.role,
            COALESCE(MAX(m.created_at), '1900-01-01') as last_message
        FROM users u
        LEFT JOIN conversation_participants cp1 ON u.id = cp1.user_id
        LEFT JOIN conversations c ON cp1.conversation_id = c.id
        LEFT JOIN messages m ON c.id = m.conversation_id
        WHERE u.id != ?
        GROUP BY u.id, u.username, u.full_name, u.role
        ORDER BY last_message DESC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['users' => $users]);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
