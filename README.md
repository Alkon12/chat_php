# chat_php
Crea un sistema de chat en tiempo real usando PHP y Bootstrap con las siguientes características:

1. Estructura Base:
- Usar Docker con contenedores para PHP, MySQL y phpMyAdmin
- Implementar autenticación de usuarios (profesores y estudiantes)
- Interfaz responsiva usando Bootstrap 5

2. Base de Datos:
- Tablas para usuarios, conversaciones, participantes y mensajes
- Tabla adicional para mensajes no leídos
- Scripts SQL para la creación de la base de datos y usuarios de prueba

3. Funcionalidades del Chat:
- Mensajes en tiempo real usando AJAX (actualización cada 2 segundos)
- Lista de contactos ordenada por mensajes más recientes
- Indicador visual (punto rojo) para mensajes no leídos
- Los mensajes se marcan como leídos al abrir la conversación
- Envío de mensajes sin recargar la página

4. Archivos Necesarios:
- config.php: Configuración de la base de datos
- chat.php: Interfaz principal del chat
- send_message.php: Endpoint para enviar mensajes
- get_messages.php: Endpoint para obtener mensajes
- get_users.php: Endpoint para obtener lista de usuarios actualizada
- get_unread.php: Endpoint para obtener mensajes no leídos
- mark_as_read.php: Endpoint para marcar mensajes como leídos

5. Características de la UI:
- Lista de contactos a la izquierda
- Área de chat a la derecha
- Mensajes enviados alineados a la derecha en azul
- Mensajes recibidos alineados a la izquierda en gris
- Scroll automático al último mensaje
- Nombre y rol del usuario en la barra de navegación

6. Seguridad:
- Sesiones de usuario
- Sanitización de inputs
- Consultas preparadas para prevenir SQL injection
- Contraseñas hasheadas con bcrypt

El sistema debe funcionar en contenedores Docker con los siguientes puertos:
- PHP: 9000
- MySQL: 9001
- phpMyAdmin: 9002

Proporciona el código completo y documentado para cada archivo, incluyendo los archivos Docker y scripts SQL necesarios.
