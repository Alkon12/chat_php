<?php
session_start();
require_once 'config.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener la lista de usuarios disponibles para chatear, ordenados por último mensaje
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
$users = $stmt->fetchAll();

// Obtener mensajes y usuario seleccionado
$messages = [];
$selected_user = null;

if (isset($_GET['user_id'])) {
    // Obtener información del usuario seleccionado
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['user_id']]);
    $selected_user = $stmt->fetch();

    // Buscar si existe una conversación entre estos usuarios
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
        // Si existe una conversación, obtener los mensajes
        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.full_name 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE m.conversation_id = ? 
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$conversation['id']]);
        $messages = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-container {
            height: 70vh;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 10px;
        }
        .message-sent {
            background-color: #007bff;
            color: white;
            margin-left: 20%;
        }
        .message-received {
            background-color: #e9ecef;
            margin-right: 20%;
        }
        .user-list {
            height: 70vh;
            overflow-y: auto;
        }
        .selected-user {
            background-color: #e3f2fd !important;
        }
        .unread-badge {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #dc3545;
            border-radius: 50%;
            margin-left: 5px;
            vertical-align: middle;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Chat Escolar</a>
            <div class="navbar-text text-white">
                Bienvenido, <?php echo htmlspecialchars($_SESSION['full_name']); ?> 
                (<?php echo $_SESSION['role'] === 'teacher' ? 'Profesor' : 'Alumno'; ?>)
                <a href="logout.php" class="btn btn-outline-light btn-sm ms-3">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Lista de usuarios -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Contactos</h5>
                    </div>
                    <div class="card-body user-list p-0">
                        <div class="list-group list-group-flush" id="userList">
                            <?php foreach ($users as $user): ?>
                                <a href="?user_id=<?php echo $user['id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo ($selected_user && $selected_user['id'] == $user['id']) ? 'selected-user' : ''; ?>"
                                   data-user-id="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                    <span class="unread-indicator"></span>
                                    <small class="text-muted d-block">
                                        <?php echo $user['role'] === 'teacher' ? 'Profesor' : 'Alumno'; ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área de chat -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <?php if ($selected_user): ?>
                                Chat con <?php echo htmlspecialchars($selected_user['full_name']); ?>
                            <?php else: ?>
                                Selecciona un usuario para chatear
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body chat-container" id="chatMessages">
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'message-sent' : 'message-received'; ?>">
                                <small class="d-block"><?php echo htmlspecialchars($message['full_name']); ?></small>
                                <?php echo htmlspecialchars($message['message_text']); ?>
                                <small class="d-block text-end">
                                    <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($selected_user): ?>
                        <div class="card-footer">
                            <form id="messageForm" action="send_message.php" method="POST">
                                <input type="hidden" name="receiver_id" value="<?php echo $selected_user['id']; ?>">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="message" 
                                           placeholder="Escribe tu mensaje..." required>
                                    <button type="submit" class="btn btn-primary">Enviar</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para actualizar la lista de usuarios
        function updateUserList() {
            const currentUserId = new URLSearchParams(window.location.search).get('user_id');
            
            fetch('get_users.php')
                .then(response => response.json())
                .then(data => {
                    if (data.users) {
                        const userList = document.getElementById('userList');
                        let html = '';
                        
                        data.users.forEach(user => {
                            const isSelected = currentUserId === user.id.toString() ? 'selected-user' : '';
                            html += `
                                <a href="?user_id=${user.id}" 
                                   class="list-group-item list-group-item-action ${isSelected}"
                                   data-user-id="${user.id}">
                                    ${user.full_name}
                                    <span class="unread-indicator"></span>
                                    <small class="text-muted d-block">
                                        ${user.role === 'teacher' ? 'Profesor' : 'Alumno'}
                                    </small>
                                </a>
                            `;
                        });
                        
                        userList.innerHTML = html;
                        updateUnreadIndicators();
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Función para actualizar mensajes
        function updateMessages() {
            const urlParams = new URLSearchParams(window.location.search);
            const userId = urlParams.get('user_id');
            
            if (!userId) return;

            fetch(`get_messages.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages) {
                        const chatMessages = document.getElementById('chatMessages');
                        let html = '';
                        
                        data.messages.forEach(message => {
                            html += `
                                <div class="message ${message.is_mine ? 'message-sent' : 'message-received'}">
                                    <small class="d-block">${message.sender_name}</small>
                                    ${message.text}
                                    <small class="d-block text-end">${message.time}</small>
                                </div>
                            `;
                        });
                        
                        chatMessages.innerHTML = html;
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Función para marcar mensajes como leídos
        function markAsRead(userId) {
            return fetch('mark_as_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `sender_id=${userId}`
            });
        }

        // Función para actualizar indicadores de mensajes no leídos
        function updateUnreadIndicators() {
            fetch('get_unread.php')
                .then(response => response.json())
                .then(data => {
                    if (data.unread) {
                        // Limpiar todos los indicadores
                        document.querySelectorAll('.unread-indicator').forEach(el => el.innerHTML = '');
                        
                        // Agregar indicadores donde hay mensajes no leídos
                        for (const [senderId, count] of Object.entries(data.unread)) {
                            const userElement = document.querySelector(`[data-user-id="${senderId}"] .unread-indicator`);
                            if (userElement && count > 0) {
                                userElement.innerHTML = '<span class="unread-badge"></span>';
                            }
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Actualizar todo cada 2 segundos
        setInterval(() => {
            updateMessages();
            updateUserList();
            
            // Marcar como leídos los mensajes del usuario seleccionado
            const urlParams = new URLSearchParams(window.location.search);
            const userId = urlParams.get('user_id');
            if (userId) {
                markAsRead(userId);
            }
        }, 2000);

        // También actualizar cuando se envía un mensaje
        document.getElementById('messageForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    form.reset();
                    updateMessages();
                    updateUserList();
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Marcar como leídos al cargar la página si hay un usuario seleccionado
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('user_id');
        if (userId) {
            markAsRead(userId);
        }

        // Hacer scroll al final del chat al cargar
        const chatContainer = document.getElementById('chatMessages');
        chatContainer.scrollTop = chatContainer.scrollHeight;

        // Actualizar indicadores y lista de usuarios inmediatamente
        updateUserList();
    </script>
</body>
</html>
