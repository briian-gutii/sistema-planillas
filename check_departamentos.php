<?php
// Include database configuration
include_once 'config/database.php';

// Get database connection
$db = getDB();

try {
    // Get all columns from departamentos table
    $query = "SHOW COLUMNS FROM departamentos";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Columns in departamentos table:</h2>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // Get sample data
    $query = "SELECT * FROM departamentos LIMIT 1";
    $stmt = $db->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "<h2>Sample data from departamentos table:</h2>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    } else {
        echo "<p>No data found in departamentos table.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 