<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

try {
    $db = getDB();
    
    // Get column names directly
    $stmt = $db->query("DESCRIBE empleados");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Empleados Table Columns</h1>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Get a sample row
    $stmt = $db->query("SELECT * FROM empleados LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "<h2>Sample Row Data</h2>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 