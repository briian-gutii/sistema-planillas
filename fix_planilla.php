<?php
// Script para verificar la estructura y crear registros con los campos correctos
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Verificación y Solución de Planillas</h1>";

try {
    // Conectar a la base de datos
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Conexión exitosa</p>";
    
    // Verificar la estructura de las tablas
    echo "<h2>Estructura de la tabla 'empleados':</h2>";
    $empleadosStruct = $db->query("DESCRIBE empleados");
    echo "<pre>";
    while ($columna = $empleadosStruct->fetch(PDO::FETCH_ASSOC)) {
        print_r($columna);
    }
    echo "</pre>";
    
    echo "<h2>Estructura de la tabla 'Detalle_Planilla':</h2>";
    $detalleStruct = $db->query("DESCRIBE Detalle_Planilla");
    echo "<pre>";
    while ($columna = $detalleStruct->fetch(PDO::FETCH_ASSOC)) {
        print_r($columna);
    }
    echo "</pre>";
    
    // Obtener la planilla que ya se creó (ID 19)
    $idPlanilla = 19;
    $checkPlanilla = $db->query("SELECT * FROM Planillas WHERE id_planilla = $idPlanilla");
    $planilla = $checkPlanilla->fetch(PDO::FETCH_ASSOC);
    
    if (!$planilla) {
        echo "<p style='color: orange;'>La planilla ID 19 no existe, creando una nueva...</p>";
        // Obtener un periodo válido
        $periodo = $db->query("SELECT id_periodo FROM periodos_pago LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $idPeriodo = $periodo['id_periodo'];
        
        // Crear nueva planilla
        $db->exec("INSERT INTO Planillas (descripcion, fecha_generacion, id_periodo, estado) 
                  VALUES ('Planilla de Corrección', NOW(), $idPeriodo, 'Generada')");
        $idPlanilla = $db->lastInsertId();
        echo "<p style='color: green;'>Nueva planilla creada con ID: $idPlanilla</p>";
    } else {
        echo "<p>Usando planilla existente ID: $idPlanilla</p>";
    }
    
    // Obtener empleados
    $empleados = $db->query("SELECT id_empleado FROM empleados LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($empleados) > 0) {
        foreach ($empleados as $empleado) {
            $idEmpleado = $empleado['id_empleado'];
            
            // Verificar si ya existe un detalle para este empleado en esta planilla
            $checkDetalle = $db->query("SELECT COUNT(*) as total FROM Detalle_Planilla 
                                      WHERE id_planilla = $idPlanilla AND id_empleado = $idEmpleado")->fetch(PDO::FETCH_ASSOC);
            
            if ($checkDetalle['total'] > 0) {
                echo "<p>El empleado ID $idEmpleado ya tiene un detalle en esta planilla</p>";
                continue;
            }
            
            // Insertar detalle usando solo los campos obligatorios
            $salario = 5000; // Valor predeterminado
            $bonificacion = 250;
            $igss = round($salario * 0.0483, 2);
            
            try {
                $stmt = $db->prepare("
                    INSERT INTO Detalle_Planilla (
                        id_planilla, id_empleado, dias_trabajados, 
                        bonificacion_incentivo, igss_laboral, 
                        total_deducciones, liquido_recibir
                    ) VALUES (
                        :id_planilla, :id_empleado, 30,
                        :bonificacion, :igss, 
                        :total_deducciones, :liquido_recibir
                    )
                ");
                
                $stmt->execute([
                    ':id_planilla' => $idPlanilla,
                    ':id_empleado' => $idEmpleado,
                    ':bonificacion' => $bonificacion,
                    ':igss' => $igss,
                    ':total_deducciones' => $igss,
                    ':liquido_recibir' => ($salario + $bonificacion - $igss)
                ]);
                
                echo "<p style='color: green;'>✓ Detalle creado para empleado ID: $idEmpleado</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>Error al insertar detalle: " . $e->getMessage() . "</p>";
                // Intentar con menos columnas si falla
                try {
                    $stmt = $db->prepare("
                        INSERT INTO Detalle_Planilla (
                            id_planilla, id_empleado, dias_trabajados
                        ) VALUES (
                            :id_planilla, :id_empleado, 30
                        )
                    ");
                    
                    $stmt->execute([
                        ':id_planilla' => $idPlanilla,
                        ':id_empleado' => $idEmpleado
                    ]);
                    
                    echo "<p style='color: green;'>✓ Detalle básico creado para empleado ID: $idEmpleado</p>";
                } catch (PDOException $e2) {
                    echo "<p style='color: red;'>Error al insertar detalle básico: " . $e2->getMessage() . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color: red;'>No se encontraron empleados</p>";
    }
    
    // Mostrar resultados finales
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb;'>";
    echo "<h2>Resultados:</h2>";
    $conteo = $db->query("SELECT COUNT(*) as total FROM Detalle_Planilla WHERE id_planilla = $idPlanilla")->fetch(PDO::FETCH_ASSOC);
    echo "<p>La planilla ID <strong>$idPlanilla</strong> ahora tiene <strong>" . $conteo['total'] . "</strong> detalles.</p>";
    echo "<p>Enlaces:</p>";
    echo "<a href='index.php?page=planillas/ver&id=$idPlanilla' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none;'>Ver Planilla #$idPlanilla</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 