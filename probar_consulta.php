<?php
require_once 'config/database.php';

try {
    // Consulta de periodos_nomina que se usa en planillas/generar.php
    $sql = "SELECT p.id_periodo, CONCAT(DATE_FORMAT(p.fecha_inicio, '%d/%m/%Y'), ' - ', 
            DATE_FORMAT(p.fecha_fin, '%d/%m/%Y'), ' (', p.descripcion, ')') as periodo_texto,
            (SELECT COUNT(*) FROM planillas pl WHERE pl.id_periodo = p.id_periodo) as tiene_planilla
            FROM periodos_nomina p
            WHERE p.estado = 'Activo'
            ORDER BY p.fecha_inicio DESC 
            LIMIT 12";
    $periodos = fetchAll($sql);
    
    echo "Resultado de la consulta:<br>";
    echo "<pre>";
    print_r($periodos);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 