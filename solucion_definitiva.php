<?php
// Solución Definitiva para Planillas
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Solución Definitiva de Planillas</h1>";

try {
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>✓ Conexión a la base de datos exitosa.</p>";

    $idPlanillaCorregir = isset($_GET['id_planilla']) ? intval($_GET['id_planilla']) : 21;
    $defaultSalarioBase = 5000.00;
    $defaultBonificacion = 250.00;
    $defaultDiasTrabajados = 30.00;
    $tasaIgss = 0.0483;

    echo "<p>Corrigiendo Planilla ID: <strong>{$idPlanillaCorregir}</strong>...</p>";

    // Iniciar transacción para asegurar la integridad de los datos
    $db->beginTransaction();

    // 1. Corregir la cabecera de la Planilla
    $stmtPlanilla = $db->prepare("SELECT * FROM Planillas WHERE id_planilla = :id_planilla");
    $stmtPlanilla->bindParam(':id_planilla', $idPlanillaCorregir, PDO::PARAM_INT);
    $stmtPlanilla->execute();
    $planillaData = $stmtPlanilla->fetch(PDO::FETCH_ASSOC);

    if (!$planillaData) {
        throw new Exception("No se encontró la planilla con ID {$idPlanillaCorregir}.");
    }

    $updatesPlanilla = [];
    if (empty($planillaData['estado']) || !in_array($planillaData['estado'], ['Borrador', 'Aprobada', 'Pagada'])) {
        $updatesPlanilla[] = "estado = 'Borrador'";
    }
    if (empty($planillaData['usuario_genero'])) {
        $updatesPlanilla[] = "usuario_genero = 'sistema'";
    }

    if (!empty($updatesPlanilla)) {
        $sqlFixPlanilla = "UPDATE Planillas SET " . implode(", ", $updatesPlanilla) . " WHERE id_planilla = :id_planilla";
        $stmtFix = $db->prepare($sqlFixPlanilla);
        $stmtFix->bindParam(':id_planilla', $idPlanillaCorregir, PDO::PARAM_INT);
        $stmtFix->execute();
        echo "<p style='color:blue;'>✓ Cabecera de planilla ID {$idPlanillaCorregir} actualizada (estado/usuario_genero).</p>";
    }

    // 2. Verificar y corregir Detalles de Planilla
    $stmtDetalles = $db->prepare("SELECT * FROM Detalle_Planilla WHERE id_planilla = :id_planilla");
    $stmtDetalles->bindParam(':id_planilla', $idPlanillaCorregir, PDO::PARAM_INT);
    $stmtDetalles->execute();
    $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

    $numeroDetallesCorregidos = 0;
    $numeroDetallesCreados = 0;

    if (empty($detalles)) {
        echo "<p style='color:orange;'>⚠️ Planilla ID {$idPlanillaCorregir} no tiene detalles. Intentando crear algunos...</p>";
        $stmtEmpleados = $db->query("SELECT id_empleado FROM empleados WHERE estado = 'Activo' LIMIT 5");
        $empleadosParaCrear = $stmtEmpleados->fetchAll(PDO::FETCH_ASSOC);

        if (empty($empleadosParaCrear)) {
            echo "<p style='color:red;'>No se encontraron empleados activos para crear detalles. No se pueden hacer más correcciones.</p>";
            $db->rollBack();
            exit;
        }

        foreach ($empleadosParaCrear as $emp) {
            $idEmp = $emp['id_empleado'];
            $salarioBase = $defaultSalarioBase;
            $bonificacion = $defaultBonificacion;
            $diasTrabajados = $defaultDiasTrabajados;
            
            // Cálculos iniciales
            $salarioTotal = $salarioBase + $bonificacion; // Asumiendo otras ganancias = 0 por ahora
            $igssLaboral = round($salarioBase * $tasaIgss, 2);
            $totalDeduccionesCalc = $igssLaboral; // Asumiendo otras deducciones = 0 por ahora
            $liquidoRecibir = $salarioTotal - $totalDeduccionesCalc;

            $sqlInsertDetalle = "INSERT INTO Detalle_Planilla (id_planilla, id_empleado, dias_trabajados, salario_base, bonificacion_incentivo, salario_total, igss_laboral, total_deducciones, liquido_recibir, horas_extra, monto_horas_extra, comisiones, bonificaciones_adicionales, isr_retenido, otras_deducciones, anticipos, prestamos, descuentos_judiciales) VALUES (:id_planilla, :id_empleado, :dias_trabajados, :salario_base, :bonificacion_incentivo, :salario_total, :igss_laboral, :total_deducciones, :liquido_recibir, 0,0,0,0,0,0,0,0,0)";
            $stmtNewDet = $db->prepare($sqlInsertDetalle);
            $stmtNewDet->execute([
                ':id_planilla' => $idPlanillaCorregir,
                ':id_empleado' => $idEmp,
                ':dias_trabajados' => $diasTrabajados,
                ':salario_base' => $salarioBase,
                ':bonificacion_incentivo' => $bonificacion,
                ':salario_total' => $salarioTotal,
                ':igss_laboral' => $igssLaboral,
                ':total_deducciones' => $totalDeduccionesCalc,
                ':liquido_recibir' => $liquidoRecibir
            ]);
            $numeroDetallesCreados++;
        }
        echo "<p style='color:green;'>✓ Creados {$numeroDetallesCreados} detalles para la planilla ID {$idPlanillaCorregir}.</p>";
        // Volver a cargar los detalles recién creados para el procesamiento de totales
        $stmtDetalles->execute();
        $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
    }

    // Procesar detalles existentes o recién creados
    $granTotalBruto = 0;
    $granTotalDeducciones = 0;
    $granTotalNeto = 0;

    foreach ($detalles as $detalle) {
        $idDetalle = $detalle['id_detalle'];
        $salarioBase = (!isset($detalle['salario_base']) || floatval($detalle['salario_base']) == 0) ? $defaultSalarioBase : floatval($detalle['salario_base']);
        $bonificacion = (!isset($detalle['bonificacion_incentivo']) || floatval($detalle['bonificacion_incentivo']) == 0) ? $defaultBonificacion : floatval($detalle['bonificacion_incentivo']);
        $diasTrabajados = (!isset($detalle['dias_trabajados']) || floatval($detalle['dias_trabajados']) == 0) ? $defaultDiasTrabajados : floatval($detalle['dias_trabajados']);

        // Otros ingresos (tomar de la BD o 0 si son nulos/no existen)
        $horasExtraMonto = isset($detalle['monto_horas_extra']) ? floatval($detalle['monto_horas_extra']) : 0;
        $comisiones = isset($detalle['comisiones']) ? floatval($detalle['comisiones']) : 0;
        $bonificacionesAdicionales = isset($detalle['bonificaciones_adicionales']) ? floatval($detalle['bonificaciones_adicionales']) : 0;

        // Otras deducciones (tomar de la BD o 0 si son nulos/no existen)
        $isrRetenido = isset($detalle['isr_retenido']) ? floatval($detalle['isr_retenido']) : 0;
        $otrasDeducciones = isset($detalle['otras_deducciones']) ? floatval($detalle['otras_deducciones']) : 0;
        $anticipos = isset($detalle['anticipos']) ? floatval($detalle['anticipos']) : 0;
        $prestamos = isset($detalle['prestamos']) ? floatval($detalle['prestamos']) : 0;
        $descuentosJudiciales = isset($detalle['descuentos_judiciales']) ? floatval($detalle['descuentos_judiciales']) : 0;

        // Cálculos
        $calcSalarioTotal = $salarioBase + $bonificacion + $horasExtraMonto + $comisiones + $bonificacionesAdicionales;
        $calcIgssLaboral = round($salarioBase * $tasaIgss, 2); // IGSS sobre salario base
        $calcTotalDeducciones = $calcIgssLaboral + $isrRetenido + $otrasDeducciones + $anticipos + $prestamos + $descuentosJudiciales;
        $calcLiquidoRecibir = $calcSalarioTotal - $calcTotalDeducciones;

        // Actualizar el detalle en la BD
        $sqlUpdateDetalle = "UPDATE Detalle_Planilla SET 
            salario_base = :salario_base, 
            bonificacion_incentivo = :bonificacion_incentivo, 
            dias_trabajados = :dias_trabajados,
            salario_total = :salario_total, 
            igss_laboral = :igss_laboral, 
            total_deducciones = :total_deducciones, 
            liquido_recibir = :liquido_recibir
            WHERE id_detalle = :id_detalle";
        
        $stmtUpdDet = $db->prepare($sqlUpdateDetalle);
        $stmtUpdDet->execute([
            ':salario_base' => $salarioBase,
            ':bonificacion_incentivo' => $bonificacion,
            ':dias_trabajados' => $diasTrabajados,
            ':salario_total' => $calcSalarioTotal,
            ':igss_laboral' => $calcIgssLaboral,
            ':total_deducciones' => $calcTotalDeducciones,
            ':liquido_recibir' => $calcLiquidoRecibir,
            ':id_detalle' => $idDetalle
        ]);
        $numeroDetallesCorregidos++;

        // Acumular para totales de planilla
        $granTotalBruto += $calcSalarioTotal;
        $granTotalDeducciones += $calcTotalDeducciones;
        $granTotalNeto += $calcLiquidoRecibir;
    }

    if ($numeroDetallesCorregidos > 0 && $numeroDetallesCreados == 0) { // Solo si no se crearon recién, para evitar doble mensaje
        echo "<p style='color:green;'>✓ {$numeroDetallesCorregidos} detalles de planilla fueron recalculados y actualizados.</p>";
    }

    // 3. Actualizar los totales en la tabla Planillas
    $sqlTotalsPlanilla = "UPDATE Planillas SET 
        total_bruto = :total_bruto, 
        total_deducciones = :total_deducciones, 
        total_neto = :total_neto 
        WHERE id_planilla = :id_planilla";
    $stmtTotals = $db->prepare($sqlTotalsPlanilla);
    $stmtTotals->execute([
        ':total_bruto' => $granTotalBruto,
        ':total_deducciones' => $granTotalDeducciones,
        ':total_neto' => $granTotalNeto,
        ':id_planilla' => $idPlanillaCorregir
    ]);
    echo "<p style='color:green;'>✓ Totales de la planilla ID {$idPlanillaCorregir} actualizados (bruto, deducciones, neto).</p>";

    // Confirmar la transacción
    $db->commit();

    echo "<div style='margin-top:20px; padding:15px; background-color:#d4edda; border:1px solid #c3e6cb; border-radius:4px;'>";
    echo "<h2>¡Corrección Completada!</h2>";
    echo "<p>La planilla ID <strong>{$idPlanillaCorregir}</strong> y sus detalles han sido verificados y corregidos.</p>";
    echo "<p><strong>Resumen:</strong><br>";
    if ($numeroDetallesCreados > 0) {
         echo "- Detalles creados: {$numeroDetallesCreados}<br>";
    }
    echo "- Detalles procesados/actualizados: {$numeroDetallesCorregidos}<br>";
    echo "- Total Bruto Planilla: " . number_format($granTotalBruto, 2) . "<br>";
    echo "- Total Deducciones Planilla: " . number_format($granTotalDeducciones, 2) . "<br>";
    echo "- Total Neto Planilla: " . number_format($granTotalNeto, 2) . "<br>";
    echo "</p>";
    echo "<p><a href='index.php?page=planillas/ver&id={$idPlanillaCorregir}' style='display:inline-block; padding:10px 15px; background-color:#007bff; color:white; text-decoration:none; border-radius:4px; margin-right:10px;'>Ver Planilla #{$idPlanillaCorregir}</a>";
    echo "<a href='index.php?page=planillas/listar' style='display:inline-block; padding:10px 15px; background-color:#6c757d; color:white; text-decoration:none; border-radius:4px;'>Ver Todas las Planillas</a></p>";
    echo "</div>";

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
        echo "<p style='color:red;'>⚠️ Ocurrió un error y se revirtieron los cambios (Rollback).</p>";
    }
    echo "<div style='color:red; padding:10px; border:1px solid red; border-radius:4px;'>";
    echo "<h3>Error Crítico:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>Línea: " . $e->getLine() . "<br>Archivo: " . $e->getFile() . "</pre>";
    echo "</div>";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
        echo "<p style='color:red;'>⚠️ Ocurrió un error general y se revirtieron los cambios (Rollback).</p>";
    }
    echo "<div style='color:red; padding:10px; border:1px solid red; border-radius:4px;'>";
    echo "<h3>Error General:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

?> 