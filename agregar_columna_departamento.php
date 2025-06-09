<?php
require_once 'config/database.php';

try {
    // Añadir la columna id_departamento a la tabla planillas
    $sql = "ALTER TABLE planillas 
            ADD COLUMN id_departamento INT NULL 
            AFTER id_periodo";
    
    query($sql);
    echo "Se ha añadido la columna 'id_departamento' a la tabla 'planillas'.<br>";
    
    // Añadir índice a la columna
    $sql = "ALTER TABLE planillas ADD INDEX idx_departamento (id_departamento)";
    query($sql);
    echo "Se ha añadido un índice a la columna 'id_departamento'.<br>";
    
    // Verificar la estructura actualizada
    echo "<br>Estructura actualizada de la tabla 'planillas':<br>";
    $sql = "DESCRIBE planillas";
    $estructura = fetchAll($sql);
    foreach ($estructura as $campo) {
        echo "- " . $campo['Field'] . " (" . $campo['Type'] . ")<br>";
    }
    
    // Verificar que la consulta problemática ahora funciona
    echo "<br>Probando la consulta que generaba el error:<br>";
    $sqlPrueba = "SELECT id_planilla FROM planillas 
                  WHERE id_periodo = :id_periodo 
                  AND (id_departamento = :id_departamento OR (:id_departamento = 0 AND id_departamento IS NULL))";
    
    // Usamos valores de prueba
    $params = [
        ':id_periodo' => 1,
        ':id_departamento' => 0
    ];
    
    try {
        $resultado = fetchAll($sqlPrueba, $params);
        echo "Consulta ejecutada correctamente. Número de resultados: " . count($resultado);
    } catch (Exception $e) {
        echo "Error al ejecutar la consulta: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 