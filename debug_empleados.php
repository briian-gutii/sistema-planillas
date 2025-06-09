<?php
require_once 'config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get employee ID from URL or use default
$id_empleado = isset($_GET['id']) ? intval($_GET['id']) : 1;

echo "<h1>Empleado Details</h1>";
echo "<p>Showing details for employee ID: {$id_empleado}</p>";

try {
    $db = getDB();
    
    // Get column names
    $columnsQuery = "SHOW COLUMNS FROM empleados";
    $stmt = $db->prepare($columnsQuery);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Get employee data
    $empleadoQuery = "SELECT * FROM empleados WHERE id_empleado = :id_empleado";
    $stmt = $db->prepare($empleadoQuery);
    $stmt->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
    $stmt->execute();
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empleado) {
        echo "<p style='color: red;'>Employee not found with ID: {$id_empleado}</p>";
        
        // Show available IDs
        $idsQuery = "SELECT id_empleado FROM empleados ORDER BY id_empleado LIMIT 10";
        $stmt = $db->prepare($idsQuery);
        $stmt->execute();
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        echo "<p>Available employee IDs: " . implode(', ', $ids) . "</p>";
        echo "<p>Try <a href='?id={$ids[0]}'>this link</a> to view an available employee.</p>";
    } else {
        echo "<h2>Column Names and Values</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Column</th><th>Value</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>{$column}</strong></td>";
            echo "<td>" . (isset($empleado[$column]) ? htmlspecialchars($empleado[$column]) : 'NULL') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Highlight important fields for the query
        echo "<h2>Important Fields for Queries</h2>";
        
        // Name fields
        echo "<h3>Name Fields:</h3>";
        $nameFields = ['nombre', 'nombres', 'primer_nombre', 'segundo_nombre', 'apellido', 'apellidos', 'primer_apellido', 'segundo_apellido'];
        echo "<ul>";
        foreach ($columns as $column) {
            foreach ($nameFields as $field) {
                if (stripos($column, $field) !== false) {
                    echo "<li><strong>{$column}</strong>: " . (isset($empleado[$column]) ? htmlspecialchars($empleado[$column]) : 'NULL') . "</li>";
                    break;
                }
            }
        }
        echo "</ul>";
        
        // ID fields
        echo "<h3>Code/ID Fields:</h3>";
        $idFields = ['codigo', 'code', 'nit', 'dpi', 'num'];
        echo "<ul>";
        foreach ($columns as $column) {
            foreach ($idFields as $field) {
                if (stripos($column, $field) !== false) {
                    echo "<li><strong>{$column}</strong>: " . (isset($empleado[$column]) ? htmlspecialchars($empleado[$column]) : 'NULL') . "</li>";
                    break;
                }
            }
        }
        echo "</ul>";
    }
    
    // Test query that should work 
    echo "<h2>Test Query</h2>";
    $testQuery = "
    SELECT pd.*, e.*
    FROM Detalle_Planilla pd
    JOIN empleados e ON pd.id_empleado = e.id_empleado
    WHERE pd.id_planilla = 14
    LIMIT 1";
    
    echo "<pre>" . htmlspecialchars($testQuery) . "</pre>";
    
    try {
        $stmt = $db->prepare($testQuery);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<p style='color: green;'>Query succeeded!</p>";
            echo "<h3>Columns returned:</h3>";
            echo "<pre>";
            print_r(array_keys($result));
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'>Query executed but no results found.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Query error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 