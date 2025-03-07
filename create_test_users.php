<?php
require_once 'config.php';

try {
    // Crear usuarios de prueba
    $users = [
        [
            'username' => 'profesor1',
            'password' => 'profesor123',
            'full_name' => 'Juan Pérez',
            'role' => 'teacher'
        ],
        [
            'username' => 'alumno1',
            'password' => 'alumno123',
            'full_name' => 'María García',
            'role' => 'student'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");

    foreach ($users as $user) {
        // Encriptar la contraseña
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $stmt->execute([
            $user['username'],
            $hashedPassword,
            $user['full_name'],
            $user['role']
        ]);
        
        echo "Usuario {$user['username']} creado exitosamente.<br>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
