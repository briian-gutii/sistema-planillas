<?php
header('Content-Type: text/plain');
require_once 'config/database.php';

try {
    $db = getDB();
    
    // Get all column names
    $query = "SHOW COLUMNS FROM empleados";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "ALL COLUMNS IN 'empleados' TABLE:\n";
    echo "==============================\n";
    foreach ($columns as $index => $column) {
        echo ($index + 1) . ". " . $column . "\n";
    }
    
    // Get sample data
    $query = "SELECT * FROM empleados WHERE id_empleado = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        echo "\n\nSAMPLE DATA FOR id_empleado = 1:\n";
        echo "===============================\n";
        foreach ($sample as $key => $value) {
            echo $key . ": " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }
    
    // Test two versions of the JOIN query
    $id_planilla = 14;
    
    echo "\n\nTEST QUERY #1 (USING e.*):\n";
    echo "=========================\n";
    $query1 = "SELECT pd.id_detalle, pd.id_empleado, e.* 
               FROM Detalle_Planilla pd
               JOIN empleados e ON pd.id_empleado = e.id_empleado
               WHERE pd.id_planilla = :id_planilla
               LIMIT 1";
    
    $stmt = $db->prepare($query1);
    $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmt->execute();
    $result1 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result1) {
        echo "Query successful! Columns returned:\n";
        echo implode(", ", array_keys($result1)) . "\n";
    } else {
        echo "No results found or query failed\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?> 