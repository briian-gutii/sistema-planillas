<?php
// Script para actualizar los detalles de planilla con valores faltantes
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Corrección de Datos en Planillas</h1>";

// Verificar ID de planilla
$idPlanilla = isset($_GET['id_planilla']) ? intval($_GET['id_planilla']) : 21;

try {
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Conexión establecida</p>";
    echo "<p>Actualizando detalles para la planilla ID: <strong>{$idPlanilla}</strong></p>";
    
    // 1. Verificar si existe la planilla
    $planillaCheck = $db->query("SELECT * FROM Planillas WHERE id_planilla = {$idPlanilla}");
    $planilla = $planillaCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$planilla) {
        throw new Exception("La planilla ID {$idPlanilla} no existe");
    }
    
    // 2. Obtener todos los detalles
    $detalles = $db->query("SELECT * FROM Detalle_Planilla WHERE id_planilla = {$idPlanilla}")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($detalles) === 0) {
        echo "<p>No se encontraron detalles para esta planilla</p>";
    } else {
        $actualizados = 0;
        
        foreach ($detalles as $detalle) {
            $idDetalle = $detalle['id_detalle'];
            $idEmpleado = $detalle['id_empleado'];
            
            // Obtener empleado
            $empleado = $db->query("SELECT * FROM empleados WHERE id_empleado = {$idEmpleado}")->fetch(PDO::FETCH_ASSOC);
            $nombreEmpleado = $empleado ? $empleado['primer_nombre'] . ' ' . $empleado['primer_apellido'] : "Empleado ID {$idEmpleado}";
            
            // Valores predeterminados
            $salarioBase = 5000; // Valor estándar si no está definido
            $bonificacion = isset($detalle['bonificacion_incentivo']) && $detalle['bonificacion_incentivo'] > 0 ? 
                $detalle['bonificacion_incentivo'] : 250;
            
            $diasTrabajados = isset($detalle['dias_trabajados']) && $detalle['dias_trabajados'] > 0 ? 
                $detalle['dias_trabajados'] : 30;
                
            // Calcular valores derivados
            $igss = round($salarioBase * 0.0483, 2);
            $salarioTotal = $salarioBase + $bonificacion;
            $liquidoRecibir = $salarioTotal - $igss;
            
            // Preparar la actualización para este detalle
            $camposActualizar = [];
            $params = [];
            
            // Verificar cada campo y actualizarlo si es NULL
            if (!isset($detalle['salario_base']) || $detalle['salario_base'] === null) {
                $camposActualizar[] = "salario_base = :salario_base";
                $params[':salario_base'] = $salarioBase;
            }
            
            if (!isset($detalle['bonificacion_incentivo']) || $detalle['bonificacion_incentivo'] === null) {
                $camposActualizar[] = "bonificacion_incentivo = :bonificacion";
                $params[':bonificacion'] = $bonificacion;
            }
            
            if (!isset($detalle['dias_trabajados']) || $detalle['dias_trabajados'] === null) {
                $camposActualizar[] = "dias_trabajados = :dias_trabajados";
                $params[':dias_trabajados'] = $diasTrabajados;
            }
            
            if (!isset($detalle['igss_laboral']) || $detalle['igss_laboral'] === null) {
                $camposActualizar[] = "igss_laboral = :igss";
                $params[':igss'] = $igss;
            }
            
            if (!isset($detalle['salario_total']) || $detalle['salario_total'] === null) {
                $camposActualizar[] = "salario_total = :salario_total";
                $params[':salario_total'] = $salarioTotal;
            }
            
            if (!isset($detalle['total_deducciones']) || $detalle['total_deducciones'] === null) {
                $camposActualizar[] = "total_deducciones = :total_deducciones";
                $params[':total_deducciones'] = $igss;
            }
            
            if (!isset($detalle['liquido_recibir']) || $detalle['liquido_recibir'] === null) {
                $camposActualizar[] = "liquido_recibir = :liquido_recibir";
                $params[':liquido_recibir'] = $liquidoRecibir;
            }
            
            // Completar todos los otros campos posiblemente nulos con 0
            $camposPosibles = [
                'horas_extra', 'monto_horas_extra', 'comisiones', 
                'bonificaciones_adicionales', 'isr_retenido', 'otras_deducciones',
                'anticipos', 'prestamos', 'descuentos_judiciales'
            ];
            
            foreach ($camposPosibles as $campo) {
                if (!isset($detalle[$campo]) || $detalle[$campo] === null) {
                    $camposActualizar[] = "{$campo} = :$campo";
                    $params[":{$campo}"] = 0;
                }
            }
            
            // Ejecutar la actualización si hay campos para actualizar
            if (count($camposActualizar) > 0) {
                $sql = "UPDATE Detalle_Planilla SET " . implode(", ", $camposActualizar) . 
                       " WHERE id_detalle = :id_detalle";
                $params[':id_detalle'] = $idDetalle;
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                $actualizados++;
                echo "<p style='color: green;'>✓ Actualizado: {$nombreEmpleado} (ID Detalle: {$idDetalle})</p>";
            } else {
                echo "<p>No fue necesario actualizar: {$nombreEmpleado} (ID Detalle: {$idDetalle})</p>";
            }
        }
        
        echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;'>";
        echo "<h2>Resultado</h2>";
        
        if ($actualizados > 0) {
            echo "<p>Se actualizaron <strong>{$actualizados}</strong> detalles de planilla</p>";
        } else {
            echo "<p>No fue necesario actualizar ningún detalle</p>";
        }
        
        echo "<p>Enlaces:</p>";
        echo "<a href='index.php?page=planillas/ver&id={$idPlanilla}' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Ver Planilla</a>";
        echo "<a href='revisar_sistema.php' style='display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Volver al Análisis</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 4px;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
    
    echo "<p><a href='javascript:history.back()' style='display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Volver</a></p>";
}
?> 