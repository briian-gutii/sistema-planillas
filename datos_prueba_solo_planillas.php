<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Generación de Planillas y Detalles</h1>";

try {
    // Conectar a la base de datos
    $db = getDB();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // 1. Verificar que existan periodos de pago
    echo "<h2>Verificando periodos de pago...</h2>";
    
    $countPeriodos = $db->query("SELECT COUNT(*) FROM periodos_nomina")->fetchColumn();
    echo "<p>Periodos existentes: $countPeriodos</p>";
    
    if ($countPeriodos == 0) {
        echo "<h3>Creando periodos de pago básicos...</h3>";
        
        $periodos = [
            ['nombre' => 'Mayo 2023', 'fecha_inicio' => '2023-05-01', 'fecha_fin' => '2023-05-31', 'estado' => 'Activo']
        ];
        
        $periodosCreados = 0;
        $insertPeriodo = $db->prepare("
            INSERT INTO periodos_nomina (nombre, fecha_inicio, fecha_fin, estado)
            VALUES (:nombre, :fecha_inicio, :fecha_fin, :estado)
        ");
        
        foreach ($periodos as $periodo) {
            $insertPeriodo->bindParam(':nombre', $periodo['nombre']);
            $insertPeriodo->bindParam(':fecha_inicio', $periodo['fecha_inicio']);
            $insertPeriodo->bindParam(':fecha_fin', $periodo['fecha_fin']);
            $insertPeriodo->bindParam(':estado', $periodo['estado']);
            $insertPeriodo->execute();
            $periodosCreados++;
        }
        
        echo "<p>Periodos creados: $periodosCreados</p>";
    }
    
    // 2. Verificar que existan empleados
    echo "<h2>Verificando empleados...</h2>";
    
    $countEmpleados = $db->query("SELECT COUNT(*) FROM empleados WHERE estado = 'Activo'")->fetchColumn();
    echo "<p>Empleados activos: $countEmpleados</p>";
    
    if ($countEmpleados == 0) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
        echo "<p>No hay empleados activos en el sistema. Se necesitan empleados para crear planillas.</p>";
        echo "</div>";
        exit;
    }
    
    // 3. Obtener IDs de periodos para las planillas
    $periodosIDs = $db->query("SELECT id_periodo FROM periodos_nomina")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($periodosIDs)) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
        echo "<p>No se encontraron periodos de nómina para asociar a las planillas.</p>";
        echo "</div>";
        exit;
    }
    
    // 4. Crear planillas de prueba
    echo "<h2>Creando planillas de prueba...</h2>";
    
    // Crear 3 planillas nuevas
    $planillasCreadas = 0;
    $insertPlanilla = $db->prepare("
        INSERT INTO Planillas (
            descripcion, fecha_generacion, id_periodo, estado
        ) VALUES (
            :descripcion, :fecha_generacion, :id_periodo, 'Generada'
        )
    ");
    
    $planillasIDs = [];
    
    for ($i = 0; $i < 3; $i++) {
        $desc = 'Planilla de prueba #' . ($i+1) . ' - ' . date('Y-m-d H:i:s');
        $fecha = date('Y-m-d H:i:s');
        $idPeriodo = $periodosIDs[array_rand($periodosIDs)];
        
        $insertPlanilla->bindParam(':descripcion', $desc);
        $insertPlanilla->bindParam(':fecha_generacion', $fecha);
        $insertPlanilla->bindParam(':id_periodo', $idPeriodo);
        $insertPlanilla->execute();
        
        $idPlanilla = $db->lastInsertId();
        $planillasIDs[] = $idPlanilla;
        $planillasCreadas++;
        
        echo "<p>Planilla #$idPlanilla creada</p>";
    }
    
    // 5. Crear detalles para cada planilla
    echo "<h2>Creando detalles para las planillas...</h2>";
    
    foreach ($planillasIDs as $idPlanilla) {
        echo "<h3>Generando detalles para planilla #$idPlanilla...</h3>";
        
        // Obtener empleados activos
        $empleados = $db->query("
            SELECT id_empleado, salario_base 
            FROM empleados 
            WHERE estado = 'Activo'
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($empleados)) {
            echo "<p style='color: orange;'>No se encontraron empleados activos para esta planilla.</p>";
            continue;
        }
        
        $detallesCreados = 0;
        
        foreach ($empleados as $empleado) {
            // Asignar valores a variables
            $diasTrabajados = rand(25, 30);
            $salarioBase = $empleado['salario_base'] ?? 5000; // Valor predeterminado si no existe
            $bonificacion = 250; // Bonificación incentivo fija
            
            $horasExtra = rand(0, 20);
            $valorHora = $salarioBase / 30 / 8 * 1.5; // Valor hora extra (tiempo y medio)
            $montoHorasExtra = round($horasExtra * $valorHora, 2);
            
            $comisiones = rand(0, 1000);
            $bonificacionesAdicionales = rand(0, 500);
            
            $salarioTotal = $salarioBase + $bonificacion + $montoHorasExtra + $comisiones + $bonificacionesAdicionales;
            
            // Deducciones
            $igss = round($salarioBase * 0.0483, 2); // 4.83% del salario base
            $isr = 0; // Simplificado, normalmente se calcula según tabla progresiva
            if ($salarioTotal > 8000) {
                $isr = round(($salarioTotal - 8000) * 0.05, 2);
            }
            
            $otrasDeduciones = rand(0, 200);
            $anticipos = rand(0, 300);
            $prestamos = rand(0, 500);
            $descuentosJudiciales = 0; // Normalmente es 0 o un valor fijo si aplica
            
            $totalDeducciones = $igss + $isr + $otrasDeduciones + $anticipos + $prestamos + $descuentosJudiciales;
            $liquidoRecibir = $salarioTotal - $totalDeducciones;
            
            // Insertar el detalle directamente - usamos una consulta completa para evitar problemas con columnas que podrían no existir
            $insertDetalle = $db->prepare("
                INSERT INTO Detalle_Planilla (
                    id_planilla, 
                    id_empleado, 
                    dias_trabajados, 
                    salario_base, 
                    bonificacion_incentivo, 
                    horas_extra, 
                    monto_horas_extra, 
                    comisiones,
                    bonificaciones_adicionales, 
                    salario_total, 
                    igss_laboral, 
                    isr_retenido,
                    otras_deducciones, 
                    anticipos, 
                    prestamos, 
                    descuentos_judiciales,
                    total_deducciones, 
                    liquido_recibir
                ) VALUES (
                    :id_planilla, 
                    :id_empleado, 
                    :dias_trabajados, 
                    :salario_base, 
                    :bonificacion, 
                    :horas_extra, 
                    :monto_horas_extra, 
                    :comisiones,
                    :bonificaciones_adicionales, 
                    :salario_total, 
                    :igss, 
                    :isr,
                    :otras_deducciones, 
                    :anticipos, 
                    :prestamos, 
                    :descuentos_judiciales,
                    :total_deducciones, 
                    :liquido_recibir
                )
            ");
            
            try {
                $insertDetalle->bindParam(':id_planilla', $idPlanilla);
                $insertDetalle->bindParam(':id_empleado', $empleado['id_empleado']);
                $insertDetalle->bindParam(':dias_trabajados', $diasTrabajados);
                $insertDetalle->bindParam(':salario_base', $salarioBase);
                $insertDetalle->bindParam(':bonificacion', $bonificacion);
                $insertDetalle->bindParam(':horas_extra', $horasExtra);
                $insertDetalle->bindParam(':monto_horas_extra', $montoHorasExtra);
                $insertDetalle->bindParam(':comisiones', $comisiones);
                $insertDetalle->bindParam(':bonificaciones_adicionales', $bonificacionesAdicionales);
                $insertDetalle->bindParam(':salario_total', $salarioTotal);
                $insertDetalle->bindParam(':igss', $igss);
                $insertDetalle->bindParam(':isr', $isr);
                $insertDetalle->bindParam(':otras_deducciones', $otrasDeduciones);
                $insertDetalle->bindParam(':anticipos', $anticipos);
                $insertDetalle->bindParam(':prestamos', $prestamos);
                $insertDetalle->bindParam(':descuentos_judiciales', $descuentosJudiciales);
                $insertDetalle->bindParam(':total_deducciones', $totalDeducciones);
                $insertDetalle->bindParam(':liquido_recibir', $liquidoRecibir);
                
                $insertDetalle->execute();
                $detallesCreados++;
            } catch (Exception $e) {
                echo "<p style='color: red;'>Error al insertar detalle para empleado ID " . $empleado['id_empleado'] . ": " . $e->getMessage() . "</p>";
                continue;
            }
        }
        
        echo "<p>Detalles creados para planilla #$idPlanilla: $detallesCreados</p>";
    }
    
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<h2 style='color: #155724;'>Resumen de la operación</h2>";
    echo "<ul>";
    echo "<li>Planillas creadas: $planillasCreadas</li>";
    echo "</ul>";
    
    echo "<p style='font-weight: bold;'>Se han creado planillas con IDs: " . implode(", ", $planillasIDs) . "</p>";
    
    // Mostrar todas las planillas
    $ultimasPlanillas = $db->query("
        SELECT id_planilla, descripcion, fecha_generacion, estado,
               (SELECT COUNT(*) FROM Detalle_Planilla WHERE id_planilla = p.id_planilla) as num_detalles
        FROM Planillas p
        ORDER BY id_planilla DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Últimas planillas creadas:</h3>";
    echo "<table border='1' cellpadding='5' style='margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>Descripción</th><th>Fecha</th><th>Estado</th><th>Detalles</th></tr>";
    
    foreach ($ultimasPlanillas as $planilla) {
        echo "<tr>";
        echo "<td>" . $planilla['id_planilla'] . "</td>";
        echo "<td>" . $planilla['descripcion'] . "</td>";
        echo "<td>" . $planilla['fecha_generacion'] . "</td>";
        echo "<td>" . $planilla['estado'] . "</td>";
        echo "<td>" . $planilla['num_detalles'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p><a href='index.php?page=planillas/listar' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Ver todas las planillas</a></p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 