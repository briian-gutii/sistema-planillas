<?php
// Solución Simple para Planillas - Se enfoca solo en corregir los detalles
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Solución Simple para Planillas</h1>";

try {
    // Conectar a la base de datos
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Conexión exitosa</p>";
    
    // ID de planilla a corregir
    $idPlanilla = isset($_GET['id_planilla']) ? intval($_GET['id_planilla']) : 21;
    
    // 1. Verificar que la planilla exista
    $checkPlanilla = $db->query("SELECT id_planilla, id_periodo FROM Planillas WHERE id_planilla = {$idPlanilla}");
    $planilla = $checkPlanilla->fetch(PDO::FETCH_ASSOC);
    
    if (!$planilla) {
        throw new Exception("No se encontró la planilla con ID {$idPlanilla}");
    }
    
    echo "<p>Trabajando con planilla ID: <strong>{$idPlanilla}</strong></p>";
    
    // 2. Verificar y corregir los detalles de la planilla
    $detallesQuery = $db->query("SELECT * FROM Detalle_Planilla WHERE id_planilla = {$idPlanilla}");
    $detalles = $detallesQuery->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($detalles)) {
        echo "<p style='color: orange;'>⚠️ No hay detalles para esta planilla. Creando detalles nuevos...</p>";
        
        // Obtener empleados
        $empleadosQuery = $db->query("SELECT id_empleado FROM empleados LIMIT 10");
        $empleados = $empleadosQuery->fetchAll(PDO::FETCH_ASSOC);
        
        $detallesInsertados = 0;
        
        foreach ($empleados as $empleado) {
            $idEmpleado = $empleado['id_empleado'];
            
            // Valores predeterminados
            $salarioBase = 5000;
            $bonificacion = 250;
            $diasTrabajados = 30;
            $igss = round($salarioBase * 0.0483, 2);
            $salarioTotal = $salarioBase + $bonificacion;
            $liquidoRecibir = $salarioTotal - $igss;
            
            // Insertar detalle
            try {
                $stmt = $db->prepare("
                    INSERT INTO Detalle_Planilla (
                        id_planilla, id_empleado, dias_trabajados, salario_base, 
                        bonificacion_incentivo, horas_extra, monto_horas_extra, comisiones,
                        bonificaciones_adicionales, salario_total, igss_laboral, isr_retenido,
                        otras_deducciones, anticipos, prestamos, descuentos_judiciales,
                        total_deducciones, liquido_recibir
                    ) VALUES (
                        :id_planilla, :id_empleado, :dias_trabajados, :salario_base,
                        :bonificacion, 0, 0, 0,
                        0, :salario_total, :igss, 0,
                        0, 0, 0, 0,
                        :total_deducciones, :liquido_recibir
                    )
                ");
                
                $stmt->execute([
                    ':id_planilla' => $idPlanilla,
                    ':id_empleado' => $idEmpleado,
                    ':dias_trabajados' => $diasTrabajados,
                    ':salario_base' => $salarioBase,
                    ':bonificacion' => $bonificacion,
                    ':salario_total' => $salarioTotal,
                    ':igss' => $igss,
                    ':total_deducciones' => $igss,
                    ':liquido_recibir' => $liquidoRecibir
                ]);
                
                $detallesInsertados++;
                echo "<p style='color: green;'>✓ Detalle creado para empleado ID: {$idEmpleado}</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>Error al crear detalle para empleado ID {$idEmpleado}: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p style='color: green;'>Total de detalles creados: <strong>{$detallesInsertados}</strong></p>";
    } else {
        echo "<p>La planilla ya tiene <strong>" . count($detalles) . "</strong> detalles. Verificando valores NULL...</p>";
        
        $detallesActualizados = 0;
        
        foreach ($detalles as $detalle) {
            $idDetalle = $detalle['id_detalle'];
            $idEmpleado = $detalle['id_empleado'];
            
            // Verificar campos necesarios
            $tieneNull = false;
            
            // Lista de campos a verificar
            $camposImportantes = [
                'salario_base', 'bonificacion_incentivo', 'dias_trabajados',
                'salario_total', 'igss_laboral', 'total_deducciones', 'liquido_recibir'
            ];
            
            foreach ($camposImportantes as $campo) {
                if (!isset($detalle[$campo]) || is_null($detalle[$campo])) {
                    $tieneNull = true;
                    break;
                }
            }
            
            if ($tieneNull) {
                // Valores predeterminados
                $salarioBase = isset($detalle['salario_base']) && !is_null($detalle['salario_base']) ? 
                    $detalle['salario_base'] : 5000;
                    
                $bonificacion = isset($detalle['bonificacion_incentivo']) && !is_null($detalle['bonificacion_incentivo']) ? 
                    $detalle['bonificacion_incentivo'] : 250;
                    
                $diasTrabajados = isset($detalle['dias_trabajados']) && !is_null($detalle['dias_trabajados']) ? 
                    $detalle['dias_trabajados'] : 30;
                    
                $igss = round($salarioBase * 0.0483, 2);
                $salarioTotal = $salarioBase + $bonificacion;
                $liquidoRecibir = $salarioTotal - $igss;
                
                // Actualizar registro
                try {
                    $stmt = $db->prepare("
                        UPDATE Detalle_Planilla SET
                            salario_base = :salario_base,
                            bonificacion_incentivo = :bonificacion,
                            dias_trabajados = :dias_trabajados,
                            salario_total = :salario_total,
                            igss_laboral = :igss,
                            total_deducciones = :total_deducciones,
                            liquido_recibir = :liquido_recibir,
                            horas_extra = COALESCE(horas_extra, 0),
                            monto_horas_extra = COALESCE(monto_horas_extra, 0),
                            comisiones = COALESCE(comisiones, 0),
                            bonificaciones_adicionales = COALESCE(bonificaciones_adicionales, 0),
                            isr_retenido = COALESCE(isr_retenido, 0),
                            otras_deducciones = COALESCE(otras_deducciones, 0),
                            anticipos = COALESCE(anticipos, 0),
                            prestamos = COALESCE(prestamos, 0),
                            descuentos_judiciales = COALESCE(descuentos_judiciales, 0)
                        WHERE id_detalle = :id_detalle
                    ");
                    
                    $stmt->execute([
                        ':salario_base' => $salarioBase,
                        ':bonificacion' => $bonificacion,
                        ':dias_trabajados' => $diasTrabajados,
                        ':salario_total' => $salarioTotal,
                        ':igss' => $igss,
                        ':total_deducciones' => $igss,
                        ':liquido_recibir' => $liquidoRecibir,
                        ':id_detalle' => $idDetalle
                    ]);
                    
                    $detallesActualizados++;
                    echo "<p style='color: green;'>✓ Detalle actualizado para empleado ID: {$idEmpleado}</p>";
                } catch (PDOException $e) {
                    echo "<p style='color: red;'>Error al actualizar detalle para empleado ID {$idEmpleado}: " . $e->getMessage() . "</p>";
                }
            }
        }
        
        if ($detallesActualizados > 0) {
            echo "<p style='color: green;'>Total de detalles actualizados: <strong>{$detallesActualizados}</strong></p>";
        } else {
            echo "<p style='color: green;'>✓ Todos los detalles ya tienen valores completos</p>";
        }
    }
    
    // 3. Resultados finales
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<h2>¡Corrección Finalizada!</h2>";
    echo "<p>La planilla ID <strong>{$idPlanilla}</strong> ha sido corregida.</p>";
    echo "<p>Puede verificar los resultados en los siguientes enlaces:</p>";
    echo "<a href='index.php?page=planillas/ver&id={$idPlanilla}' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Ver Planilla</a>";
    echo "<a href='index.php?page=planillas/listar' style='display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Ver Todas las Planillas</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 15px; border: 1px solid red; border-radius: 4px;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 