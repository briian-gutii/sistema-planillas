<?php
header('Content-Type: application/json');
require_once 'config/database.php';

try {
    $db = getDB();
    $stmt = $db->query("DESCRIBE empleados");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $columnNames = [];
    foreach ($columns as $column) {
        $columnNames[] = $column['Field'];
    }
    
    // Also get a sample row
    $stmt = $db->query("SELECT * FROM empleados LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $result = [
        'columns' => $columnNames,
        'sample' => $row
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 