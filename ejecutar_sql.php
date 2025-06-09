<?php
// Este script ejecuta directamente el SQL necesario para solucionar el problema
// Es una solución completa que no depende de archivos adicionales

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Solución Completa para Planillas - Terminal</h1>";

try {
    // Conectar a la base de datos
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // 1. Determinar qué tabla de periodos existe
    $checkPeriodos = $db->query("SHOW TABLES LIKE 'periodos_pago'");
    $tablaPeriodos = 'periodos_pago';
    
    if ($checkPeriodos->rowCount() == 0) {
        $checkPeriodosNomina = $db->query("SHOW TABLES LIKE 'periodos_nomina'");
        if ($checkPeriodosNomina->rowCount() > 0) {
            $tablaPeriodos = 'periodos_nomina';
        } else {
            throw new Exception("No se encontró ninguna tabla de periodos");
        }
    }
    
    echo "<p>Usando tabla de periodos: <strong>{$tablaPeriodos}</strong></p>";
    
    // 2. Verificar si existen periodos
    $periodoExistente = $db->query("SELECT id_periodo FROM {$tablaPeriodos} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($periodoExistente) {
        $idPeriodo = $periodoExistente['id_periodo'];
        echo "<p>Se usará el periodo existente ID: <strong>{$idPeriodo}</strong></p>";
    } else {
        // Crear un periodo nuevo
        $insertPeriodo = $db->prepare("
            INSERT INTO {$tablaPeriodos} (nombre, fecha_inicio, fecha_fin, estado)
            VALUES (:nombre, :fecha_inicio, :fecha_fin, :estado)
        ");
        
        $insertPeriodo->execute([
            ':nombre' => 'Periodo Terminal - ' . date('Y-m'),
            ':fecha_inicio' => date('Y-m-01'),
            ':fecha_fin' => date('Y-m-t'),
            ':estado' => 'Activo'
        ]);
        
        $idPeriodo = $db->lastInsertId();
        echo "<p style='color: green;'>✓ Periodo creado con ID: <strong>{$idPeriodo}</strong></p>";
    }
    
    // 3. Crear una planilla
    $descripcionPlanilla = "Planilla Terminal Final - " . date('Y-m-d H:i:s');
    
    $insertPlanilla = $db->prepare("
        INSERT INTO Planillas (descripcion, fecha_generacion, id_periodo, estado)
        VALUES (:descripcion, NOW(), :id_periodo, 'Generada')
    ");
    
    $insertPlanilla->execute([
        ':descripcion' => $descripcionPlanilla,
        ':id_periodo' => $idPeriodo
    ]);
    
    $idPlanilla = $db->lastInsertId();
    echo "<p style='color: green;'>✓ Planilla creada con ID: <strong>{$idPlanilla}</strong></p>";
    
    // 4. Obtener empleados (activos o cualquiera si no hay activos)
    $empleados = $db->query("
        SELECT id_empleado, salario_base
        FROM empleados 
        WHERE estado = 'Activo'
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($empleados) == 0) {
        $empleados = $db->query("
            SELECT id_empleado, salario_base
            FROM empleados 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $totalEmpleados = count($empleados);
    echo "<p>Total de empleados encontrados: <strong>{$totalEmpleados}</strong></p>";
    
    // 5. Insertar detalles de planilla
    $detallesInsertados = 0;
    
    foreach ($empleados as $empleado) {
        $idEmpleado = $empleado['id_empleado'];
        $salarioBase = isset($empleado['salario_base']) && is_numeric($empleado['salario_base']) 
            ? $empleado['salario_base'] 
            : 5000;
        
        $bonificacion = 250;
        $igss = round($salarioBase * 0.0483, 2);
        $salarioTotal = $salarioBase + $bonificacion;
        $liquidoRecibir = $salarioTotal - $igss;
        
        $insertDetalle = $db->prepare("
            INSERT INTO Detalle_Planilla (
                id_planilla, id_empleado, dias_trabajados, salario_base, 
                bonificacion_incentivo, salario_total, igss_laboral,
                total_deducciones, liquido_recibir
            ) VALUES (
                :id_planilla, :id_empleado, 30, :salario_base,
                :bonificacion, :salario_total, :igss,
                :total_deducciones, :liquido_recibir
            )
        ");
        
        $insertDetalle->execute([
            ':id_planilla' => $idPlanilla,
            ':id_empleado' => $idEmpleado,
            ':salario_base' => $salarioBase,
            ':bonificacion' => $bonificacion,
            ':salario_total' => $salarioTotal,
            ':igss' => $igss,
            ':total_deducciones' => $igss,
            ':liquido_recibir' => $liquidoRecibir
        ]);
        
        $detallesInsertados++;
    }
    
    // 6. Mostrar resultados
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    
    if ($detallesInsertados > 0) {
        echo "<h2 style='color: #155724;'>¡Operación Exitosa!</h2>";
        echo "<p>La planilla ID <strong>{$idPlanilla}</strong> fue creada con <strong>{$detallesInsertados}</strong> detalles de empleados.</p>";
        
        echo "<p>Para ver la planilla, haga clic aquí:</p>";
        echo "<a href='index.php?page=planillas/ver&id={$idPlanilla}' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Ver Planilla #{$idPlanilla}</a>";
        echo "<p><a href='index.php?page=planillas/listar' style='display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Ver Todas las Planillas</a></p>";
    } else {
        echo "<h2 style='color: #721c24;'>No se pudieron insertar detalles</h2>";
        echo "<p>La planilla se creó pero no se pudieron agregar detalles.</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

// Muestra un botón para volver a ejecutar
echo "<div style='margin-top: 20px;'>";
echo "<button onclick='window.location.reload()' style='padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;'>Ejecutar de Nuevo</button>";
echo "</div>";
?> 