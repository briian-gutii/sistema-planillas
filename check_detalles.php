<?php
require_once 'config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get the planilla ID from the URL
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : 14;

echo "<h1>Verificar Detalles de Planilla</h1>";
echo "<p>Planilla ID: {$id_planilla}</p>";

try {
    $db = getDB();
    
    // Check both possible table names
    $tables = ['Detalle_Planilla', 'detalle_planilla', 'planilla_detalle'];
    
    echo "<h2>Verificación de tablas y registros:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Tabla</th><th>¿Existe?</th><th>Registros para esta planilla</th><th>Ver SQL</th></tr>";
    
    foreach ($tables as $table) {
        // Check if table exists
        try {
            $checkTable = $db->query("SHOW TABLES LIKE '{$table}'");
            $tableExists = ($checkTable && $checkTable->rowCount() > 0);
            
            $recordCount = 0;
            $queryText = "";
            
            if ($tableExists) {
                // Count records for this planilla
                $query = "SELECT COUNT(*) FROM {$table} WHERE id_planilla = :id_planilla";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
                $stmt->execute();
                $recordCount = $stmt->fetchColumn();
                
                // Get sample record SQL to display
                $queryText = "SELECT * FROM {$table} WHERE id_planilla = {$id_planilla} LIMIT 1";
            }
            
            echo "<tr>";
            echo "<td>{$table}</td>";
            echo "<td>" . ($tableExists ? "<span style='color: green'>Sí</span>" : "<span style='color: red'>No</span>") . "</td>";
            echo "<td>" . ($tableExists ? $recordCount : "N/A") . "</td>";
            echo "<td>" . ($tableExists ? "<code>" . htmlspecialchars($queryText) . "</code>" : "N/A") . "</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr><td>{$table}</td><td colspan='3'><span style='color: red'>Error: " . $e->getMessage() . "</span></td></tr>";
        }
    }
    
    echo "</table>";
    
    // If there are no records, offer to generate test data
    $hasRecords = false;
    foreach ($tables as $table) {
        try {
            $query = "SELECT COUNT(*) FROM {$table} WHERE id_planilla = :id_planilla";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $hasRecords = true;
                break;
            }
        } catch (Exception $e) {
            // Ignore errors for non-existent tables
        }
    }
    
    if (!$hasRecords) {
        echo "<h2>No se encontraron registros para esta planilla</h2>";
        echo "<p>Puede generar datos de prueba usando esta herramienta: <a href='util_fix_detalle_planilla.php?id={$id_planilla}'>Generar Datos de Prueba</a></p>";
    }
    
    // Check planilla record to ensure it exists
    $planillaQuery = "SELECT * FROM Planillas WHERE id_planilla = :id_planilla";
    $stmt = $db->prepare($planillaQuery);
    $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmt->execute();
    $planilla = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($planilla) {
        echo "<h2>Datos de la Planilla</h2>";
        echo "<pre>";
        print_r($planilla);
        echo "</pre>";
    } else {
        echo "<h2>¡Advertencia!</h2>";
        echo "<p style='color: red'>No existe una planilla con ID {$id_planilla} en la tabla Planillas.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red'>Error: " . $e->getMessage() . "</p>";
}
?> 