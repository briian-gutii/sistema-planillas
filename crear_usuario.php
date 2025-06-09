<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Datos del usuario a crear
$usuario = [
    'nombre_usuario' => 'admin',
    'contrasena' => password_hash('admin123', PASSWORD_DEFAULT), // Contraseña hasheada
    'correo' => 'admin@planillasgt.com',
    'rol' => ROL_ADMIN,
    'fecha_creacion' => date('Y-m-d H:i:s'),
    'estado' => 'Activo'
];

try {
    // Verificar si el usuario ya existe
    $sqlVerificar = "SELECT id_usuario FROM usuarios_sistema WHERE nombre_usuario = :nombre_usuario";
    $usuarioExistente = fetchRow($sqlVerificar, [':nombre_usuario' => $usuario['nombre_usuario']]);
    
    if ($usuarioExistente) {
        echo "El usuario '{$usuario['nombre_usuario']}' ya existe en el sistema.<br>";
    } else {
        // Insertar el nuevo usuario
        $sql = "INSERT INTO usuarios_sistema (nombre_usuario, contrasena, correo, rol, fecha_creacion, estado) 
                VALUES (:nombre_usuario, :contrasena, :correo, :rol, :fecha_creacion, :estado)";
        
        query($sql, [
            ':nombre_usuario' => $usuario['nombre_usuario'],
            ':contrasena' => $usuario['contrasena'],
            ':correo' => $usuario['correo'],
            ':rol' => $usuario['rol'],
            ':fecha_creacion' => $usuario['fecha_creacion'],
            ':estado' => $usuario['estado']
        ]);
        
        echo "Usuario '{$usuario['nombre_usuario']}' creado exitosamente.<br>";
        echo "Rol: {$usuario['rol']}<br>";
        echo "Correo: {$usuario['correo']}<br>";
        echo "Contraseña: admin123 (no compartir)<br>";
    }
    
    echo "<br><a href='login.php'>Ir a la página de inicio de sesión</a>";
    
} catch (PDOException $e) {
    echo "Error al crear el usuario: " . $e->getMessage();
}
?> 