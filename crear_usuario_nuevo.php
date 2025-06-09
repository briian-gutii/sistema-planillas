<?php
require_once 'config/database.php';

try {
    // Verificar si la tabla de usuarios existe
    $sql = "SHOW TABLES LIKE 'usuarios_sistema'";
    $tablaExiste = fetchAll($sql);
    
    if (empty($tablaExiste)) {
        // Crear la tabla de usuarios si no existe
        $sql = "CREATE TABLE IF NOT EXISTS usuarios_sistema (
            id_usuario INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            nombre_completo VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            rol ENUM('Administrador', 'Contador', 'RRHH', 'Consulta') NOT NULL,
            estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
            ultimo_acceso DATETIME,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        query($sql);
        echo "Se ha creado la tabla 'usuarios_sistema'.<br>";
    }
    
    // Verificar si el usuario ya existe
    $sql = "SELECT id_usuario FROM usuarios_sistema WHERE username = :username";
    $usuarioExistente = fetchRow($sql, [':username' => 'bgutierrez1']);
    
    if ($usuarioExistente) {
        echo "El usuario 'bgutierrez1' ya existe en el sistema.<br>";
    } else {
        // Crear nuevo usuario
        $password = password_hash('123456', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios_sistema (username, password, nombre_completo, email, rol, estado) 
                VALUES (:username, :password, :nombre_completo, :email, :rol, 'Activo')";
        
        query($sql, [
            ':username' => 'bgutierrez1',
            ':password' => $password,
            ':nombre_completo' => 'Byron Gutierrez',
            ':email' => 'bgutierrez@example.com',
            ':rol' => 'Administrador'
        ]);
        
        echo "Se ha creado el usuario 'bgutierrez1' con contraseña '123456'.<br>";
    }
    
    // Verificar si existe el usuario admin
    $sql = "SELECT id_usuario FROM usuarios_sistema WHERE username = :username";
    $usuarioExistente = fetchRow($sql, [':username' => 'admin']);
    
    if ($usuarioExistente) {
        echo "El usuario 'admin' ya existe en el sistema.<br>";
    } else {
        // Crear nuevo usuario admin
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios_sistema (username, password, nombre_completo, email, rol, estado) 
                VALUES (:username, :password, :nombre_completo, :email, :rol, 'Activo')";
        
        query($sql, [
            ':username' => 'admin',
            ':password' => $password,
            ':nombre_completo' => 'Administrador del Sistema',
            ':email' => 'admin@ejemplo.com',
            ':rol' => 'Administrador'
        ]);
        
        echo "Se ha creado el usuario 'admin' con contraseña 'admin123'.<br>";
    }
    
    // Verificar los usuarios creados
    echo "<br>Usuarios disponibles en el sistema:<br>";
    $sql = "SELECT id_usuario, username, nombre_completo, rol, estado FROM usuarios_sistema";
    $usuarios = fetchAll($sql);
    
    foreach ($usuarios as $usuario) {
        echo "ID: " . $usuario['id_usuario'] . 
             " | Usuario: " . $usuario['username'] . 
             " | Nombre: " . $usuario['nombre_completo'] . 
             " | Rol: " . $usuario['rol'] . 
             " | Estado: " . $usuario['estado'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 