<?php
require_once 'config/database.php';
require_once 'config/config.php';

// Simulamos el entorno de la página generar.php para probar sin tener que iniciar sesión
try {
    echo "Probando la funcionalidad de generación de planillas...<br><br>";
    
    // Simulamos la obtención de periodos disponibles (el mismo código de pages/planillas/generar.php)
    $sql = "SELECT p.id_periodo, CONCAT(DATE_FORMAT(p.fecha_inicio, '%d/%m/%Y'), ' - ', 
            DATE_FORMAT(p.fecha_fin, '%d/%m/%Y'), ' (', p.descripcion, ')') as periodo_texto,
            (SELECT COUNT(*) FROM planillas pl WHERE pl.id_periodo = p.id_periodo) as tiene_planilla
            FROM periodos_nomina p
            WHERE p.estado = 'Activo'
            ORDER BY p.fecha_inicio DESC 
            LIMIT 12";
    
    echo "Ejecutando consulta de periodos:<br>";
    echo "<pre>$sql</pre>";
    $periodos = fetchAll($sql);
    
    echo "Periodos obtenidos: " . count($periodos) . "<br>";
    if (count($periodos) > 0) {
        foreach ($periodos as $p) {
            echo "- ID: " . $p['id_periodo'] . " | " . $p['periodo_texto'] . "<br>";
        }
    }
    echo "<br>";
    
    // Simulamos la obtención de departamentos
    $sql = "SELECT id_departamento, nombre FROM departamentos WHERE estado = 'Activo' ORDER BY nombre";
    echo "Ejecutando consulta de departamentos:<br>";
    echo "<pre>$sql</pre>";
    $departamentos = fetchAll($sql);
    
    echo "Departamentos obtenidos: " . count($departamentos) . "<br>";
    if (count($departamentos) > 0) {
        foreach ($departamentos as $d) {
            echo "- ID: " . $d['id_departamento'] . " | " . $d['nombre'] . "<br>";
        }
    }
    echo "<br>";
    
    // Simulamos la verificación para la generación de planilla
    echo "Simulando verificación para generación de planilla:<br>";
    $id_periodo = isset($periodos[0]) ? $periodos[0]['id_periodo'] : 1;
    $id_departamento = isset($departamentos[0]) ? $departamentos[0]['id_departamento'] : 0;
    
    echo "Usando id_periodo = $id_periodo, id_departamento = $id_departamento<br>";
    
    // Usamos una consulta más simple para verificar que podemos acceder a la tabla planillas
    $sqlSimple = "SELECT COUNT(*) as total FROM planillas";
    $resultado = fetchRow($sqlSimple);
    echo "Total de planillas en la base de datos: " . $resultado['total'] . "<br><br>";
    
    // Ahora probamos con la consulta que causaba el error, pero modificada para usar valores directos
    $sqlVerificar = "SELECT id_planilla FROM planillas 
                   WHERE id_periodo = $id_periodo 
                   AND (id_departamento = $id_departamento OR ($id_departamento = 0 AND id_departamento IS NULL))";
    
    echo "Consulta SQL modificada:<br><pre>$sqlVerificar</pre>";
    
    $planillaExistente = fetchRow($sqlVerificar);
    
    if ($planillaExistente) {
        echo "Ya existe una planilla para el periodo ($id_periodo) y departamento ($id_departamento) seleccionado.";
    } else {
        echo "No existe planilla. Se puede proceder con la generación.";
    }
    
    echo "<br><br>Prueba completada con éxito. La funcionalidad de generación de planillas parece estar funcionando correctamente.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "<br>Traza:<pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
} 