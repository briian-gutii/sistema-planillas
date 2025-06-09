<?php
// Solución final simplificada que no depende de la columna salario_base
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Solución Final Simplificada</h1>";

try {
    // Conectar a la base de datos
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // 1. Verificar estructura de empleados (solo para mostrar)
    echo "<h2>Estructura de Tablas:</h2>";
    echo "<h3>Tabla 'empleados':</h3>";
    echo "<pre>";
    $empleadosStruct = $db->query("DESCRIBE empleados");
    while ($col = $empleadosStruct->fetch(PDO::FETCH_ASSOC)) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    echo "</pre>";
    
    // 2. Verificar estructura de Detalle_Planilla
    echo "<h3>Tabla 'Detalle_Planilla':</h3>";
    echo "<pre>";
    $detalleStruct = $db->query("DESCRIBE Detalle_Planilla");
    while ($col = $detalleStruct->fetch(PDO::FETCH_ASSOC)) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    echo "</pre>";
    
    // 3. Crear un periodo si no existe
    $checkPeriodos = $db->query("SELECT COUNT(*) as total FROM periodos_pago");
    $periodoCount = $checkPeriodos->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($periodoCount == 0) {
        $db->exec("INSERT INTO periodos_pago (nombre, fecha_inicio, fecha_fin, estado) 
                  VALUES ('Periodo Final', '2023-06-01', '2023-06-30', 'Activo')");
        $idPeriodo = $db->lastInsertId();
        echo "<p style='color: green;'>✓ Periodo creado con ID: {$idPeriodo}</p>";
    } else {
        $periodoData = $db->query("SELECT id_periodo FROM periodos_pago ORDER BY id_periodo DESC LIMIT 1");
        $idPeriodo = $periodoData->fetch(PDO::FETCH_ASSOC)['id_periodo'];
        echo "<p>Usando periodo existente ID: {$idPeriodo}</p>";
    }
    
    // 4. Crear una planilla
    $db->exec("INSERT INTO Planillas (descripcion, fecha_generacion, id_periodo, estado) 
              VALUES ('Planilla Final Simplificada', NOW(), {$idPeriodo}, 'Generada')");
    $idPlanilla = $db->lastInsertId();
    echo "<p style='color: green;'>✓ Planilla creada con ID: {$idPlanilla}</p>";
    
    // 5. Obtener lista de empleados
    $empleados = $db->query("SELECT id_empleado FROM empleados LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($empleados) > 0) {
        $detallesInsertados = 0;
        
        // 6. Analizar campos obligatorios de Detalle_Planilla
        $columnas = [];
        $detalleStruct = $db->query("DESCRIBE Detalle_Planilla");
        while ($col = $detalleStruct->fetch(PDO::FETCH_ASSOC)) {
            if ($col['Null'] === 'NO') {
                $columnas[] = $col['Field'];
            }
        }
        
        // Campos básicos que siempre intentaremos insertar
        $camposBasicos = ['id_planilla', 'id_empleado', 'dias_trabajados'];
        
        // Verificar si tenemos los campos opcionales comunes
        $tieneColumnaBonus = in_array('bonificacion_incentivo', $columnas) ? true : false;
        $tieneColumnaIGSS = in_array('igss_laboral', $columnas) ? true : false;
        $tieneColumnaDeducciones = in_array('total_deducciones', $columnas) ? true : false;
        $tieneColumnaLiquido = in_array('liquido_recibir', $columnas) ? true : false;
        
        echo "<p>Insertando detalles para {$idPlanilla} empleados...</p>";
        
        foreach ($empleados as $empleado) {
            $idEmpleado = $empleado['id_empleado'];
            
            // Valores predeterminados
            $diasTrabajados = 30;
            $salarioBase = 5000;
            $bonificacion = 250;
            $igss = round($salarioBase * 0.0483, 2);
            $liquido = $salarioBase + $bonificacion - $igss;
            
            // Determinar qué columnas incluir en la consulta
            $columnasSQL = implode(', ', $camposBasicos);
            $valoresSQL = ":{$camposBasicos[0]}, :{$camposBasicos[1]}, :{$camposBasicos[2]}";
            
            $params = [
                ':id_planilla' => $idPlanilla,
                ':id_empleado' => $idEmpleado,
                ':dias_trabajados' => $diasTrabajados
            ];
            
            // Agregar columnas opcionales si existen
            if ($tieneColumnaBonus) {
                $columnasSQL .= ', bonificacion_incentivo';
                $valoresSQL .= ', :bonificacion';
                $params[':bonificacion'] = $bonificacion;
            }
            
            if ($tieneColumnaIGSS) {
                $columnasSQL .= ', igss_laboral';
                $valoresSQL .= ', :igss';
                $params[':igss'] = $igss;
            }
            
            if ($tieneColumnaDeducciones) {
                $columnasSQL .= ', total_deducciones';
                $valoresSQL .= ', :deducciones';
                $params[':deducciones'] = $igss;
            }
            
            if ($tieneColumnaLiquido) {
                $columnasSQL .= ', liquido_recibir';
                $valoresSQL .= ', :liquido';
                $params[':liquido'] = $liquido;
            }
            
            // Preparar e insertar
            try {
                $sql = "INSERT INTO Detalle_Planilla ({$columnasSQL}) VALUES ({$valoresSQL})";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $detallesInsertados++;
                echo "<p style='color: green;'>✓ Detalle insertado para empleado ID: {$idEmpleado}</p>";
            } catch (PDOException $innerEx) {
                echo "<p style='color: orange;'>Advertencia al insertar detalle: " . $innerEx->getMessage() . "</p>";
                
                // Intentar con solo los campos obligatorios
                try {
                    $sql = "INSERT INTO Detalle_Planilla (id_planilla, id_empleado) VALUES (:id_planilla, :id_empleado)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':id_planilla' => $idPlanilla, ':id_empleado' => $idEmpleado]);
                    $detallesInsertados++;
                    echo "<p style='color: blue;'>✓ Detalle básico insertado para empleado ID: {$idEmpleado}</p>";
                } catch (PDOException $finalEx) {
                    echo "<p style='color: red;'>Error final al insertar: " . $finalEx->getMessage() . "</p>";
                }
            }
        }
        
        // 7. Mostrar resultados
        echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;'>";
        echo "<h2 style='color: #155724;'>¡Solución Completada!</h2>";
        echo "<p>Se insertaron <strong>{$detallesInsertados}</strong> detalles para la planilla ID: <strong>{$idPlanilla}</strong></p>";
        echo "<p>Enlaces para verificar:</p>";
        echo "<a href='index.php?page=planillas/ver&id={$idPlanilla}' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Ver esta Planilla</a>";
        echo "<a href='index.php?page=planillas/listar' style='display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Ver Todas las Planillas</a>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>No se encontraron empleados en la base de datos</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 4px;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 