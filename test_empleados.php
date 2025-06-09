<?php
require_once 'config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Empleados Sample Query</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Database connection successful</p>";

    // Get a sample row and output ALL column names
    $query = "SELECT * FROM empleados LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "<h2>Columns in empleados table:</h2>";
        echo "<pre>";
        print_r(array_keys($row));
        echo "</pre>";
        
        echo "<h2>Sample data:</h2>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    } else {
        echo "<p>No records found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Try testing a direct query
try {
    echo "<h2>Testing direct query for employee code field:</h2>";
    $db = getDB();
    
    // Try both potential field names
    $queries = [
        "codigo_empleado" => "SELECT id_empleado, codigo_empleado FROM empleados LIMIT 1",
        "codigo" => "SELECT id_empleado, codigo FROM empleados LIMIT 1"
    ];
    
    foreach ($queries as $field => $query) {
        echo "<p>Testing query with field: <strong>" . $field . "</strong></p>";
        try {
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p style='color: green;'>✓ Field exists: " . $field . "</p>";
            echo "<pre>";
            print_r($result);
            echo "</pre>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Field does not exist: " . $field . "</p>";
            echo "<p>Error: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 