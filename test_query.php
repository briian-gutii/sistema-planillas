<?php
// Incluir configuración y conexión a la base de datos
require_once 'includes/config.php';

// ID de la planilla a verificar
$id_planilla = 15;

echo "<h1>Prueba de consulta SQL para la Planilla #$id_planilla</h1>";

try {
    $db = getDB();
    
    // Ejecutar la consulta original
    echo "<h2>Consulta 1: Consulta Original</h2>";
    $queryOriginal = "SELECT pd.*, e.*
                      FROM Detalle_Planilla pd
                      LEFT JOIN empleados e ON pd.id_empleado = e.id_empleado
                      WHERE pd.id_planilla = :id_planilla";
    
    $stmtOriginal = $db->prepare($queryOriginal);
    $stmtOriginal->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtOriginal->execute();
    
    $resultadosOriginal = $stmtOriginal->fetchAll(PDO::FETCH_ASSOC);
    $numResultadosOriginal = count($resultadosOriginal);
    
    echo "<p>La consulta original devuelve <strong>$numResultadosOriginal</strong> resultados.</p>";
    
    if ($numResultadosOriginal > 0) {
        // Mostrar el primer resultado para inspeccionar los datos
        echo "<h3>Primer resultado:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        foreach ($resultadosOriginal[0] as $campo => $valor) {
            echo "<tr><th>$campo</th><td>" . (is_null($valor) ? "<em>NULL</em>" : $valor) . "</td></tr>";
        }
        echo "</table>";
    }
    
    // Consulta modificada con joins a departamentos y puestos
    echo "<h2>Consulta 2: Consulta con JOIN a departamentos y puestos</h2>";
    $queryModificada = "SELECT pd.*, 
                        e.*,
                        d.nombre as departamento,
                        p.nombre as puesto
                        FROM Detalle_Planilla pd
                        LEFT JOIN empleados e ON pd.id_empleado = e.id_empleado
                        LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                        LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
                        WHERE pd.id_planilla = :id_planilla";
    
    $stmtModificada = $db->prepare($queryModificada);
    $stmtModificada->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtModificada->execute();
    
    $resultadosModificada = $stmtModificada->fetchAll(PDO::FETCH_ASSOC);
    $numResultadosModificada = count($resultadosModificada);
    
    echo "<p>La consulta modificada devuelve <strong>$numResultadosModificada</strong> resultados.</p>";
    
    if ($numResultadosModificada > 0) {
        // Mostrar el primer resultado para inspeccionar los datos
        echo "<h3>Primer resultado:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        foreach ($resultadosModificada[0] as $campo => $valor) {
            echo "<tr><th>$campo</th><td>" . (is_null($valor) ? "<em>NULL</em>" : $valor) . "</td></tr>";
        }
        echo "</table>";
    }
    
    // Consulta simplificada para depuración
    echo "<h2>Consulta 3: Consulta básica de detalle_planilla</h2>";
    $querySimple = "SELECT * FROM Detalle_Planilla WHERE id_planilla = :id_planilla";
    
    $stmtSimple = $db->prepare($querySimple);
    $stmtSimple->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtSimple->execute();
    
    $resultadosSimple = $stmtSimple->fetchAll(PDO::FETCH_ASSOC);
    $numResultadosSimple = count($resultadosSimple);
    
    echo "<p>La consulta simple devuelve <strong>$numResultadosSimple</strong> resultados.</p>";
    
    if ($numResultadosSimple > 0) {
        echo "<h3>Datos básicos de los empleados en esta planilla:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>#</th><th>ID Empleado</th><th>ID Planilla</th><th>Salario Base</th><th>Líquido a Recibir</th></tr>";
        
        foreach ($resultadosSimple as $index => $resultado) {
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td>" . $resultado['id_empleado'] . "</td>";
            echo "<td>" . $resultado['id_planilla'] . "</td>";
            echo "<td>" . ($resultado['salario_base'] ?? 'N/A') . "</td>";
            echo "<td>" . ($resultado['liquido_recibir'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar si las tablas existen
    echo "<h2>Verificación de Tablas</h2>";
    $tablesToCheck = ['empleados', 'departamentos', 'puestos', 'planillas', 'detalle_planilla'];
    
    foreach ($tablesToCheck as $table) {
        try {
            $result = $db->query("SELECT 1 FROM $table LIMIT 1");
            echo "<p>Tabla <strong>$table</strong>: <span style='color:green'>Existe</span></p>";
        } catch (PDOException $e) {
            echo "<p>Tabla <strong>$table</strong>: <span style='color:red'>No existe o error: " . $e->getMessage() . "</span></p>";
        }
    }
    
    // Verificar los IDs de departamento y puesto de los empleados
    echo "<h2>Verificación de IDs de Departamento y Puesto</h2>";
    $queryEmpleados = "SELECT id_empleado, primer_nombre, primer_apellido, id_departamento, id_puesto FROM empleados";
    $resultEmpleados = $db->query($queryEmpleados)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>ID Departamento</th><th>ID Puesto</th></tr>";
    
    foreach ($resultEmpleados as $empleado) {
        $colorDept = is_null($empleado['id_departamento']) ? "style='background-color: #ffcccc;'" : "";
        $colorPuesto = is_null($empleado['id_puesto']) ? "style='background-color: #ffcccc;'" : "";
        
        echo "<tr>";
        echo "<td>" . $empleado['id_empleado'] . "</td>";
        echo "<td>" . $empleado['primer_nombre'] . " " . $empleado['primer_apellido'] . "</td>";
        echo "<td $colorDept>" . (is_null($empleado['id_departamento']) ? "<em>NULL</em>" : $empleado['id_departamento']) . "</td>";
        echo "<td $colorPuesto>" . (is_null($empleado['id_puesto']) ? "<em>NULL</em>" : $empleado['id_puesto']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error de Base de Datos</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 