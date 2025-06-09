<?php
require_once 'config/database.php';

try {
    // Ver qué tablas existen
    $sql = "SHOW TABLES";
    $tablas = fetchAll($sql);
    
    echo "Tablas existentes en la base de datos:<br>";
    foreach ($tablas as $tabla) {
        echo "- " . reset($tabla) . "<br>";
    }
    
    // Verificar estructura de empleados si existe
    if (in_array('empleados', array_map('reset', $tablas))) {
        echo "<br>Estructura de la tabla 'empleados':<br>";
        $sql = "DESCRIBE empleados";
        $estructura = fetchAll($sql);
        foreach ($estructura as $campo) {
            echo "- " . $campo['Field'] . " (" . $campo['Type'] . ")<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 