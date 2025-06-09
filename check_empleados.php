<?php
require_once 'config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Empleados Table Structure</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Database connection successful</p>";

    // Get table structure
    $query = "SHOW COLUMNS FROM empleados";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Columns in Empleados Table</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . ($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check if specific columns exist
    $checkColumns = ['codigo', 'codigo_empleado', 'DPI', 'dpi', 'nombres', 'apellidos', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido'];
    
    echo "<h2>Key Column Check</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column Name</th><th>Exists?</th></tr>";
    
    foreach ($checkColumns as $columnName) {
        $found = false;
        foreach ($columns as $column) {
            if (strtolower($column['Field']) === strtolower($columnName)) {
                $found = true;
                break;
            }
        }
        
        echo "<tr>";
        echo "<td>" . $columnName . "</td>";
        echo "<td style='color:" . ($found ? "green" : "red") . "'>" . ($found ? "Yes" : "No") . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Get a sample record
    $query = "SELECT * FROM empleados LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        echo "<h2>Sample Record</h2>";
        echo "<pre>";
        print_r($sample);
        echo "</pre>";
    } else {
        echo "<p>No records found in empleados table.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 