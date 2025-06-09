<?php
require_once 'config/database.php';

try {
    // Añadir la columna estado a la tabla departamentos
    $sql = "ALTER TABLE departamentos 
            ADD COLUMN estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo' 
            AFTER nombre";
    
    query($sql);
    echo "Se ha añadido la columna 'estado' a la tabla 'departamentos'.<br>";
    
    // Actualizar todos los departamentos a estado 'Activo'
    $sql = "UPDATE departamentos SET estado = 'Activo'";
    query($sql);
    echo "Se han actualizado todos los departamentos a estado 'Activo'.<br>";
    
    // Verificar la estructura actualizada
    echo "<br>Estructura actualizada de la tabla 'departamentos':<br>";
    $sql = "DESCRIBE departamentos";
    $estructura = fetchAll($sql);
    foreach ($estructura as $campo) {
        echo "- " . $campo['Field'] . " (" . $campo['Type'] . ")<br>";
    }
    
    // Verificar los datos actualizados
    echo "<br>Datos actualizados en la tabla 'departamentos':<br>";
    $sql = "SELECT * FROM departamentos";
    $departamentos = fetchAll($sql);
    
    if (empty($departamentos)) {
        echo "No hay datos en la tabla departamentos.";
    } else {
        foreach ($departamentos as $departamento) {
            echo "ID: " . $departamento['id_departamento'] . 
                 " | Nombre: " . $departamento['nombre'] . 
                 " | Estado: " . $departamento['estado'] . "<br>";
        }
    }
    
    // Probar la consulta específica utilizada en generar.php
    $sql = "SELECT id_departamento, nombre FROM departamentos WHERE estado = 'Activo' ORDER BY nombre";
    $departamentos = fetchAll($sql);
    
    echo "<br>Resultado de la consulta usada en planillas/generar.php:<br>";
    echo "<pre>";
    print_r($departamentos);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 