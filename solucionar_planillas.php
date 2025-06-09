<?php
// Script para solucionar de una vez por todas el problema de planillas
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Solución Completa de Planillas</h1>";

try {
    // 1. Conectar a la base de datos
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // 2. Verificar si ya existen planillas con detalles
    $planillasConDetalles = $db->query("
        SELECT p.id_planilla, p.descripcion, COUNT(dp.id_detalle) as num_detalles
        FROM Planillas p
        LEFT JOIN Detalle_Planilla dp ON p.id_planilla = dp.id_planilla
        GROUP BY p.id_planilla
        HAVING num_detalles > 0
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($planillasConDetalles)) {
        echo "<div style='padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb;'>";
        echo "<h3 style='color: #155724;'>Ya existen planillas con detalles</h3>";
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID Planilla</th><th>Descripción</th><th>Número de Detalles</th></tr>";
        
        foreach ($planillasConDetalles as $planilla) {
            echo "<tr>";
            echo "<td>{$planilla['id_planilla']}</td>";
            echo "<td>{$planilla['descripcion']}</td>";
            echo "<td>{$planilla['num_detalles']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<p>Puede ver estas planillas desde: <a href='index.php?page=planillas/listar'>Lista de Planillas</a></p>";
        echo "</div>";
        
        echo "<hr>";
        echo "<h3>Continuar con la creación de nuevas planillas...</h3>";
    }
    
    // 3. Verificar o crear periodo de pago
    $periodoExistente = $db->query("SELECT id_periodo FROM periodos_pago LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    $idPeriodo = null;
    
    if ($periodoExistente) {
        $idPeriodo = $periodoExistente['id_periodo'];
        echo "<p>Se usará el periodo existente ID: $idPeriodo</p>";
    } else {
        // Intentar crear un periodo de pago
        echo "<p>No se encontraron periodos de pago. Creando uno nuevo...</p>";
        
        // Verificar si la tabla se llama 'periodos_pago' o 'periodos_nomina'
        $tablasPeriodos = [];
        $checkTabla1 = $db->query("SHOW TABLES LIKE 'periodos_pago'")->fetch();
        if ($checkTabla1) $tablasPeriodos[] = 'periodos_pago';
        
        $checkTabla2 = $db->query("SHOW TABLES LIKE 'periodos_nomina'")->fetch();
        if ($checkTabla2) $tablasPeriodos[] = 'periodos_nomina';
        
        if (empty($tablasPeriodos)) {
            throw new Exception("No se encontró ninguna tabla de periodos");
        }
        
        $tablaPeriodos = $tablasPeriodos[0];
        echo "<p>Usando tabla: $tablaPeriodos</p>";
        
        // Intentar obtener la estructura de la tabla
        $columnasTabla = $db->query("SHOW COLUMNS FROM $tablaPeriodos")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas disponibles: " . implode(", ", $columnasTabla) . "</p>";
        
        // Preparar los campos según la estructura
        $campos = [];
        $valores = [];
        
        if (in_array('nombre', $columnasTabla)) {
            $campos[] = 'nombre';
            $valores[] = "'Período Automático " . date('Y-m') . "'";
        }
        
        if (in_array('fecha_inicio', $columnasTabla)) {
            $campos[] = 'fecha_inicio';
            $valores[] = "'" . date('Y-m-01') . "'";
        }
        
        if (in_array('fecha_fin', $columnasTabla)) {
            $campos[] = 'fecha_fin';
            $valores[] = "'" . date('Y-m-t') . "'";
        }
        
        if (in_array('estado', $columnasTabla)) {
            $campos[] = 'estado';
            $valores[] = "'Activo'";
        }
        
        // Crear el periodo
        $sqlInsertPeriodo = "INSERT INTO $tablaPeriodos (" . implode(", ", $campos) . ") VALUES (" . implode(", ", $valores) . ")";
        
        echo "<p>SQL para crear periodo: $sqlInsertPeriodo</p>";
        
        $db->exec($sqlInsertPeriodo);
        $idPeriodo = $db->lastInsertId();
        
        echo "<p style='color: green;'>✓ Periodo creado con ID: $idPeriodo</p>";
    }
    
    // 4. Crear una nueva planilla
    $descripcionPlanilla = "Planilla Solucionada - " . date('Y-m-d H:i:s');
    
    $insertPlanilla = $db->prepare("
        INSERT INTO Planillas (descripcion, fecha_generacion, id_periodo, estado)
        VALUES (:descripcion, NOW(), :id_periodo, 'Generada')
    ");
    
    $insertPlanilla->bindParam(':descripcion', $descripcionPlanilla);
    $insertPlanilla->bindParam(':id_periodo', $idPeriodo);
    $insertPlanilla->execute();
    
    $idPlanilla = $db->lastInsertId();
    
    echo "<p style='color: green;'>✓ Planilla creada con ID: $idPlanilla</p>";
    
    // 5. Obtener empleados activos
    $empleados = $db->query("
        SELECT id_empleado, salario_base
        FROM empleados 
        WHERE estado = 'Activo'
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $numEmpleados = count($empleados);
    
    if ($numEmpleados == 0) {
        echo "<p style='color: orange;'>⚠ No se encontraron empleados activos. Intento alternativo...</p>";
        
        // Si no hay empleados activos, intentar tomar cualquier empleado
        $empleados = $db->query("
            SELECT id_empleado, salario_base
            FROM empleados 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $numEmpleados = count($empleados);
        
        if ($numEmpleados == 0) {
            throw new Exception("No se encontraron empleados en la base de datos");
        }
    }
    
    echo "<p>Se generarán detalles para $numEmpleados empleados.</p>";
    
    // 6. Verificar la estructura de Detalle_Planilla
    $columnasDetalle = $db->query("SHOW COLUMNS FROM Detalle_Planilla")->fetchAll(PDO::FETCH_COLUMN);
    
    // 7. Insertar detalles de empleados
    $detallesCreados = 0;
    $erroresDetalle = 0;
    
    // Primero, construir un SQL dinámico basado en las columnas reales
    $camposDetalle = [];
    $marcadoresDetalle = [];
    
    // Campos obligatorios
    $camposDetalle[] = 'id_planilla';
    $marcadoresDetalle[] = ':id_planilla';
    
    $camposDetalle[] = 'id_empleado';
    $marcadoresDetalle[] = ':id_empleado';
    
    // Campos opcionales comunes
    $camposOpcionales = [
        'dias_trabajados', 'salario_base', 'bonificacion_incentivo', 
        'horas_extra', 'monto_horas_extra', 'comisiones',
        'bonificaciones_adicionales', 'salario_total', 'igss_laboral', 
        'isr_retenido', 'otras_deducciones', 'anticipos', 
        'prestamos', 'descuentos_judiciales', 'total_deducciones', 
        'liquido_recibir'
    ];
    
    foreach ($camposOpcionales as $campo) {
        if (in_array($campo, $columnasDetalle)) {
            $camposDetalle[] = $campo;
            $marcadoresDetalle[] = ':' . $campo;
        }
    }
    
    $sqlInsertDetalle = "
        INSERT INTO Detalle_Planilla (" . implode(", ", $camposDetalle) . ")
        VALUES (" . implode(", ", $marcadoresDetalle) . ")
    ";
    
    echo "<p>SQL para insertar detalles: " . $sqlInsertDetalle . "</p>";
    
    $insertDetalle = $db->prepare($sqlInsertDetalle);
    
    foreach ($empleados as $empleado) {
        try {
            $idEmpleado = $empleado['id_empleado'];
            $salarioBase = isset($empleado['salario_base']) && is_numeric($empleado['salario_base']) 
                ? $empleado['salario_base'] 
                : 5000; // Valor predeterminado
            
            // Datos base
            $diasTrabajados = 30;
            $bonificacion = 250; // Bonificación incentivo fija
            $horasExtra = 0;
            $montoHorasExtra = 0;
            $comisiones = 0;
            $bonificacionesAdicionales = 0;
            
            $salarioTotal = $salarioBase + $bonificacion;
            
            // Deducciones
            $igss = round($salarioBase * 0.0483, 2); // 4.83% del salario base
            $isr = 0;
            $otrasDeduciones = 0;
            $anticipos = 0;
            $prestamos = 0;
            $descuentosJudiciales = 0;
            
            $totalDeducciones = $igss;
            $liquidoRecibir = $salarioTotal - $totalDeducciones;
            
            // Bind parámetros obligatorios
            $insertDetalle->bindParam(':id_planilla', $idPlanilla);
            $insertDetalle->bindParam(':id_empleado', $idEmpleado);
            
            // Bind parámetros opcionales según su existencia
            if (in_array('dias_trabajados', $columnasDetalle)) {
                $insertDetalle->bindParam(':dias_trabajados', $diasTrabajados);
            }
            
            if (in_array('salario_base', $columnasDetalle)) {
                $insertDetalle->bindParam(':salario_base', $salarioBase);
            }
            
            if (in_array('bonificacion_incentivo', $columnasDetalle)) {
                $insertDetalle->bindParam(':bonificacion_incentivo', $bonificacion);
            }
            
            if (in_array('horas_extra', $columnasDetalle)) {
                $insertDetalle->bindParam(':horas_extra', $horasExtra);
            }
            
            if (in_array('monto_horas_extra', $columnasDetalle)) {
                $insertDetalle->bindParam(':monto_horas_extra', $montoHorasExtra);
            }
            
            if (in_array('comisiones', $columnasDetalle)) {
                $insertDetalle->bindParam(':comisiones', $comisiones);
            }
            
            if (in_array('bonificaciones_adicionales', $columnasDetalle)) {
                $insertDetalle->bindParam(':bonificaciones_adicionales', $bonificacionesAdicionales);
            }
            
            if (in_array('salario_total', $columnasDetalle)) {
                $insertDetalle->bindParam(':salario_total', $salarioTotal);
            }
            
            if (in_array('igss_laboral', $columnasDetalle)) {
                $insertDetalle->bindParam(':igss_laboral', $igss);
            }
            
            if (in_array('isr_retenido', $columnasDetalle)) {
                $insertDetalle->bindParam(':isr_retenido', $isr);
            }
            
            if (in_array('otras_deducciones', $columnasDetalle)) {
                $insertDetalle->bindParam(':otras_deducciones', $otrasDeduciones);
            }
            
            if (in_array('anticipos', $columnasDetalle)) {
                $insertDetalle->bindParam(':anticipos', $anticipos);
            }
            
            if (in_array('prestamos', $columnasDetalle)) {
                $insertDetalle->bindParam(':prestamos', $prestamos);
            }
            
            if (in_array('descuentos_judiciales', $columnasDetalle)) {
                $insertDetalle->bindParam(':descuentos_judiciales', $descuentosJudiciales);
            }
            
            if (in_array('total_deducciones', $columnasDetalle)) {
                $insertDetalle->bindParam(':total_deducciones', $totalDeducciones);
            }
            
            if (in_array('liquido_recibir', $columnasDetalle)) {
                $insertDetalle->bindParam(':liquido_recibir', $liquidoRecibir);
            }
            
            $insertDetalle->execute();
            $detallesCreados++;
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error insertando detalle para empleado ID {$empleado['id_empleado']}: " . $e->getMessage() . "</p>";
            $erroresDetalle++;
        }
    }
    
    // 8. Mostrar resultados finales
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    
    if ($detallesCreados > 0) {
        echo "<h2 style='color: #155724;'>¡Éxito!</h2>";
        echo "<p>Se ha creado la planilla ID $idPlanilla con $detallesCreados detalles de empleados.</p>";
        
        if ($erroresDetalle > 0) {
            echo "<p style='color: orange;'>Hubo $erroresDetalle errores que se omitieron.</p>";
        }
        
        echo "<p><a href='index.php?page=planillas/ver&id=$idPlanilla' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Ver la planilla creada</a></p>";
        
        echo "<p><a href='index.php?page=planillas/listar' style='display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Ver todas las planillas</a></p>";
    } else {
        echo "<h2 style='color: #721c24;'>No se pudieron crear detalles</h2>";
        echo "<p>La planilla se creó pero no se pudieron generar los detalles.</p>";
        
        echo "<p>Para un diagnóstico detallado, vaya a: <a href='debug_planilla.php?id=$idPlanilla'>Diagnóstico de planilla</a></p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 