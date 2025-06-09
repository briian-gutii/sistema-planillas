image.png<?php
require_once 'config/database.php';

try {
    echo "Estructura de la tabla 'departamentos':<br>";
    $sql = "DESCRIBE departamentos";
    $estructura = fetchAll($sql);
    foreach ($estructura as $campo) {
        echo "- " . $campo['Field'] . " (" . $campo['Type'] . ")<br>";
    }
    
    echo "<br>Datos en la tabla 'departamentos':<br>";
    $sql = "SELECT * FROM departamentos";
    $departamentos = fetchAll($sql);
    
    if (empty($departamentos)) {
        echo "No hay datos en la tabla departamentos.";
    } else {
        foreach ($departamentos as $departamento) {
            echo "ID: " . $departamento['id_departamento'] . 
                 " | Nombre: " . $departamento['nombre'] . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 