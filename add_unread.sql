-- Agregar tabla para mensajes no leídos
CREATE TABLE IF NOT EXISTS unread_messages (
    message_id INT,
    user_id INT,
    PRIMARY KEY (message_id, user_id),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
