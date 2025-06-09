<?php
require_once 'config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get the planilla ID from query string or default to 14
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : 14;

echo "<h1>Planilla Detail Query Update & Test</h1>";
echo "<p>Testing for planilla ID: $id_planilla</p>";

try {
    $db = getDB();
    
    // Get employee table structure
    $columnQuery = "DESCRIBE empleados";
    $stmt = $db->query($columnQuery);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Get all column names
    
    echo "<h2>Empleados Columns:</h2>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . htmlspecialchars($column) . "</li>";
    }
    echo "</ul>";
    
    // Now try to find the "code" column - often used for employee IDs
    $codeColumn = null;
    $nameColumns = [];
    $idColumn = null;
    
    foreach ($columns as $column) {
        $lowerColumn = strtolower($column);
        
        // Try to identify a code column
        if (strpos($lowerColumn, 'codigo') !== false || 
            strpos($lowerColumn, 'code') !== false ||
            strpos($lowerColumn, 'num') !== false) {
            $codeColumn = $column;
        }
        
        // Try to identify name columns
        if (strpos($lowerColumn, 'nombre') !== false || 
            strpos($lowerColumn, 'name') !== false || 
            strpos($lowerColumn, 'apellido') !== false) {
            $nameColumns[] = $column;
        }
        
        // Find the primary identifier column
        if (strpos($lowerColumn, 'dpi') !== false || 
            strpos($lowerColumn, 'nit') !== false || 
            strpos($lowerColumn, 'identificacion') !== false ||
            strpos($lowerColumn, 'id_') !== false && $lowerColumn != 'id_empleado') {
            $idColumn = $column;
        }
    }
    
    echo "<h2>Column Identification:</h2>";
    echo "<ul>";
    echo "<li>Code column: " . ($codeColumn ?: "Not found") . "</li>";
    echo "<li>Name columns: " . (!empty($nameColumns) ? implode(", ", $nameColumns) : "Not found") . "</li>";
    echo "<li>ID column: " . ($idColumn ?: "Not found") . "</li>";
    echo "</ul>";
    
    // Generate a new query with the e.* approach (safer)
    $safeQuery = "SELECT pd.*, 
                e.*,
                d.nombre as departamento, p.nombre as puesto
                FROM Detalle_Planilla pd
                JOIN empleados e ON pd.id_empleado = e.id_empleado
                LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
                WHERE pd.id_planilla = :id_planilla";
    
    // Test the query
    echo "<h2>Testing Safe Query:</h2>";
    echo "<pre>" . htmlspecialchars($safeQuery) . "</pre>";
    
    $stmt = $db->prepare($safeQuery);
    $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Records found: " . count($results) . "</p>";
    
    if (count($results) > 0) {
        // Create a sample row for reference
        echo "<h2>Sample Row Keys (Columns Returned):</h2>";
        echo "<pre>";
        print_r(array_keys($results[0]));
        echo "</pre>";
        
        // List empleados columns from the result
        echo "<h2>Empleados Columns in Result:</h2>";
        echo "<ul>";
        foreach (array_keys($results[0]) as $key) {
            if (!in_array($key, ['departamento', 'puesto']) && !preg_match('/^id_detalle|id_planilla|dias_trabajados|salario_base|bonificacion_incentivo|horas_extra|monto_horas_extra|comisiones|bonificaciones_adicionales|salario_total|igss_laboral|isr_retenido|otras_deducciones|anticipos|prestamos|descuentos_judiciales|total_deducciones|liquido_recibir$/', $key)) {
                echo "<li>" . htmlspecialchars($key) . " = " . htmlspecialchars($results[0][$key]) . "</li>";
            }
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 