<?php
// Incluir configuración y conexión a la base de datos
require_once 'includes/config.php';

// ID de la planilla a verificar
$id_planilla = 15;

echo "<h1>Diagnóstico de Planilla #$id_planilla</h1>";

try {
    $db = getDB();
    
    // 1. Verificar si existe la planilla
    $queryPlanilla = "SELECT * FROM planillas WHERE id_planilla = :id_planilla";
    $stmtPlanilla = $db->prepare($queryPlanilla);
    $stmtPlanilla->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtPlanilla->execute();
    
    if ($stmtPlanilla->rowCount() == 0) {
        echo "<p style='color:red'>La planilla #$id_planilla no existe en la base de datos.</p>";
        exit;
    }
    
    $planilla = $stmtPlanilla->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Datos de la Planilla</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    foreach ($planilla as $key => $value) {
        echo "<tr><th>$key</th><td>" . (is_null($value) ? "<em>NULL</em>" : $value) . "</td></tr>";
    }
    echo "</table>";
    
    // 2. Verificar detalles de la planilla
    $queryDetalles = "SELECT * FROM detalle_planilla WHERE id_planilla = :id_planilla";
    $stmtDetalles = $db->prepare($queryDetalles);
    $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtDetalles->execute();
    
    $numDetalles = $stmtDetalles->rowCount();
    echo "<h2>Detalles de la Planilla</h2>";
    
    if ($numDetalles == 0) {
        echo "<p style='color:red'>No hay detalles (empleados) asociados a esta planilla.</p>";
    } else {
        echo "<p>Esta planilla tiene <strong>$numDetalles</strong> empleados registrados.</p>";
        
        $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Lista de Empleados en esta Planilla:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr>";
        foreach (array_keys($detalles[0]) as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
        
        foreach ($detalles as $detalle) {
            echo "<tr>";
            foreach ($detalle as $valor) {
                echo "<td>" . (is_null($valor) ? "<em>NULL</em>" : $valor) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // 3. Verificar los datos de los empleados
        echo "<h2>Datos de Empleados</h2>";
        
        foreach ($detalles as $index => $detalle) {
            $id_empleado = $detalle['id_empleado'];
            
            $queryEmpleado = "SELECT e.*, 
                             d.nombre as nombre_departamento,
                             p.nombre as nombre_puesto
                             FROM empleados e
                             LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                             LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
                             WHERE e.id_empleado = :id_empleado";
            $stmtEmpleado = $db->prepare($queryEmpleado);
            $stmtEmpleado->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
            $stmtEmpleado->execute();
            
            if ($stmtEmpleado->rowCount() == 0) {
                echo "<p style='color:red'>No se encontró el empleado con ID $id_empleado.</p>";
                continue;
            }
            
            $empleado = $stmtEmpleado->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>Empleado #".($index+1)." (ID: $id_empleado)</h3>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            foreach ($empleado as $campo => $valor) {
                $color = '';
                // Destacar campos importantes si son NULL
                if (is_null($valor) && in_array($campo, ['id_departamento', 'id_puesto'])) {
                    $color = "style='background-color: #ffcccc;'";
                }
                echo "<tr $color><th>$campo</th><td>" . (is_null($valor) ? "<em>NULL</em>" : $valor) . "</td></tr>";
            }
            echo "</table>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error de Base de Datos</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 