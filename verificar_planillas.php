<?php
require_once 'config/database.php';

try {
    echo "Estructura de la tabla 'planillas':<br>";
    $sql = "DESCRIBE planillas";
    $estructura = fetchAll($sql);
    foreach ($estructura as $campo) {
        echo "- " . $campo['Field'] . " (" . $campo['Type'] . ")<br>";
    }
    
    echo "<br>Datos en la tabla 'planillas':<br>";
    $sql = "SELECT * FROM planillas LIMIT 5";
    $planillas = fetchAll($sql);
    
    if (empty($planillas)) {
        echo "No hay datos en la tabla planillas.";
    } else {
        foreach ($planillas as $planilla) {
            echo "ID: " . $planilla['id_planilla'] . 
                 " | Periodo: " . $planilla['id_periodo'] . 
                 " | Descripción: " . $planilla['descripcion'] . 
                 " | Estado: " . $planilla['estado'] . "<br>";
        }
    }
    
    // Mostrar la consulta SQL que está generando el error
    echo "<br>Consulta SQL utilizada en verificar planilla (planillas/generar.php):<br>";
    echo "<pre>
    SELECT id_planilla FROM planillas 
    WHERE id_periodo = :id_periodo 
    AND (id_departamento = :id_departamento OR (:id_departamento = 0 AND id_departamento IS NULL))
    </pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 