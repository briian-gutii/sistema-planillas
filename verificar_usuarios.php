<?php
require_once 'config/database.php';

try {
    // Verificar si la tabla existe
    $sql = "SHOW TABLES LIKE 'usuarios_sistema'";
    $tablaExiste = fetchAll($sql);
    
    if (empty($tablaExiste)) {
        echo "La tabla 'usuarios_sistema' no existe.";
    } else {
        echo "Estructura de la tabla 'usuarios_sistema':<br>";
        $sql = "DESCRIBE usuarios_sistema";
        $estructura = fetchAll($sql);
        foreach ($estructura as $campo) {
            echo "- " . $campo['Field'] . " (" . $campo['Type'] . ")<br>";
        }
        
        echo "<br>Datos en la tabla 'usuarios_sistema':<br>";
        $sql = "SELECT * FROM usuarios_sistema";
        $usuarios = fetchAll($sql);
        
        if (empty($usuarios)) {
            echo "No hay datos en la tabla usuarios_sistema.";
        } else {
            foreach ($usuarios as $usuario) {
                echo "ID: " . $usuario['id_usuario'] . " | ";
                
                // Mostrar todas las columnas disponibles
                foreach ($usuario as $campo => $valor) {
                    if ($campo != 'id_usuario' && $campo != 'password' && $campo != 'contrasena') {
                        echo $campo . ": " . $valor . " | ";
                    }
                }
                echo "<br>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 