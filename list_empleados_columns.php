<?php
require_once 'config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Empleados Table Full Column List</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Database connection successful</p>";

    // Get all column names
    $query = "SHOW COLUMNS FROM empleados";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>All Column Names:</h2>";
    echo "<ol>";
    foreach ($columns as $column) {
        echo "<li><strong>" . $column['Field'] . "</strong> - Type: " . $column['Type'] . "</li>";
    }
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 