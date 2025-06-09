<?php
require_once 'config/database.php';

try {
    echo "Estructura de la tabla 'periodos_nomina':<br>";
    $sql = "DESCRIBE periodos_nomina";
    $estructura = fetchAll($sql);
    foreach ($estructura as $campo) {
        echo "- " . $campo['Field'] . " (" . $campo['Type'] . ")<br>";
    }
    
    echo "<br>Datos en la tabla 'periodos_nomina':<br>";
    $sql = "SELECT * FROM periodos_nomina ORDER BY fecha_inicio DESC";
    $periodos = fetchAll($sql);
    
    if (empty($periodos)) {
        echo "No hay datos en la tabla periodos_nomina.";
    } else {
        foreach ($periodos as $periodo) {
            echo "ID: " . $periodo['id_periodo'] . 
                 " | Fechas: " . $periodo['fecha_inicio'] . " - " . $periodo['fecha_fin'] . 
                 " | Descripción: " . $periodo['descripcion'] . 
                 " | Estado: " . $periodo['estado'] . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 