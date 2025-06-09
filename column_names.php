<?php
header('Content-Type: text/plain');
require_once 'config/database.php';

try {
    // Connect to DB
    $db = getDB();
    
    // Get columns
    $stmt = $db->query("SHOW COLUMNS FROM empleados");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "EMPLEADOS TABLE COLUMNS:\n";
    echo "=======================\n";
    foreach ($columns as $index => $column) {
        echo ($index + 1) . ". " . $column . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?> 