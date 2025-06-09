<?php
require_once 'config/database.php';

try {
    $db = getDB();
    
    // Check if tipo_planilla table exists
    $result = $db->query("SHOW TABLES LIKE 'tipo_planilla'");
    echo "tipo_planilla table " . ($result->rowCount() > 0 ? "exists" : "does not exist") . "\n";
    
    // List all tables
    echo "\nAll tables in database:\n";
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "\n";
    }
    
    // If tipo_planilla exists, show its structure
    if ($result->rowCount() > 0) {
        echo "\nStructure of tipo_planilla table:\n";
        $stmt = $db->query("DESCRIBE tipo_planilla");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
        
        // Count records in tipo_planilla
        $stmt = $db->query("SELECT COUNT(*) FROM tipo_planilla");
        $count = $stmt->fetchColumn();
        echo "\nNumber of records in tipo_planilla: " . $count . "\n";
    }
    
    // Check if planillas table has id_tipo_planilla column
    $columnExists = false;
    $columnsResult = $db->query("SHOW COLUMNS FROM planillas");
    while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
        if ($column['Field'] == 'id_tipo_planilla') {
            $columnExists = true;
            break;
        }
    }
    echo "\nid_tipo_planilla column in planillas table " . ($columnExists ? "exists" : "does not exist") . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 