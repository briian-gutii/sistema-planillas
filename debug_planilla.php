<?php
require_once 'config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get the planilla ID from the URL
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : 0;

echo "<h1>Debugging Planilla Details</h1>";

if ($id_planilla <= 0) {
    echo "<p style='color: red;'>No planilla ID specified. Use ?id=X in URL.</p>";
    exit;
}

echo "<p>Debugging planilla ID: <strong>$id_planilla</strong></p>";

// Get database connection
try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// 1. Check if planilla exists
try {
    $query = "SELECT * FROM Planillas WHERE id_planilla = :id_planilla";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmt->execute();
    $planilla = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($planilla) {
        echo "<h2>Planilla Information</h2>";
        echo "<pre>";
        print_r($planilla);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>No planilla found with ID $id_planilla</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error querying planilla: " . $e->getMessage() . "</p>";
}

// 2. Check table structure of Detalle_Planilla
try {
    $query = "SHOW TABLES LIKE 'Detalle_Planilla'";
    $result = $db->query($query);
    $tableExists = ($result && $result->rowCount() > 0);
    
    if ($tableExists) {
        echo "<p style='color: green;'>✓ Table 'Detalle_Planilla' exists</p>";
        
        // Show columns
        echo "<h2>Detalle_Planilla Table Structure</h2>";
        $columnsQuery = "SHOW COLUMNS FROM Detalle_Planilla";
        $columnsResult = $db->query($columnsQuery);
        $columns = $columnsResult->fetchAll(PDO::FETCH_ASSOC);
        
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
    } else {
        echo "<p style='color: red;'>Table 'Detalle_Planilla' does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking table structure: " . $e->getMessage() . "</p>";
}

// 3. Check for records in Detalle_Planilla for this planilla
try {
    $queryCount = "SELECT COUNT(*) as total FROM Detalle_Planilla WHERE id_planilla = :id_planilla";
    $stmtCount = $db->prepare($queryCount);
    $stmtCount->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtCount->execute();
    $count = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<h2>Detalle_Planilla Records</h2>";
    echo "<p>Records found for planilla ID $id_planilla: <strong>$count</strong></p>";
    
    if ($count > 0) {
        // Get all details records
        $queryDetails = "SELECT * FROM Detalle_Planilla WHERE id_planilla = :id_planilla";
        $stmtDetails = $db->prepare($queryDetails);
        $stmtDetails->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
        $stmtDetails->execute();
        $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<pre>";
        print_r($details);
        echo "</pre>";
    } else {
        // Try to list all records to see if the table has any data
        $queryAny = "SELECT COUNT(*) as total FROM Detalle_Planilla";
        $stmtAny = $db->prepare($queryAny);
        $stmtAny->execute();
        $totalCount = $stmtAny->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "<p>Total records in Detalle_Planilla (all planillas): <strong>$totalCount</strong></p>";
        
        if ($totalCount > 0) {
            // If there are records but none for this planilla ID
            echo "<p>There are records in the table, but none for this planilla ID.</p>";
            
            // Show a sample of records 
            $querySample = "SELECT id_planilla, COUNT(*) as record_count FROM Detalle_Planilla GROUP BY id_planilla LIMIT 10";
            $stmtSample = $db->prepare($querySample);
            $stmtSample->execute();
            $samples = $stmtSample->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample of planilla IDs with records:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID Planilla</th><th>Record Count</th></tr>";
            
            foreach ($samples as $sample) {
                echo "<tr>";
                echo "<td>" . $sample['id_planilla'] . "</td>";
                echo "<td>" . $sample['record_count'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error querying Detalle_Planilla: " . $e->getMessage() . "</p>";
}

// 4. Debug the exact query used in the ver.php
try {
    echo "<h2>Testing the Exact Query from ver.php</h2>";
    
    $queryDetalles = "SELECT pd.*, 
                    e.*
                    FROM Detalle_Planilla pd
                    JOIN empleados e ON pd.id_empleado = e.id_empleado
                    WHERE pd.id_planilla = :id_planilla";
    
    echo "<p>Query:<br><code>" . htmlspecialchars($queryDetalles) . "</code></p>";
    
    $stmtDetalles = $db->prepare($queryDetalles);
    $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtDetalles->execute();
    $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Records returned: <strong>" . count($detalles) . "</strong></p>";
    
    if (count($detalles) > 0) {
        echo "<pre>";
        print_r($detalles);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error executing the exact query: " . $e->getMessage() . "</p>";
}

// 5. Check empleados table to ensure employees exist
try {
    $queryEmpleados = "SELECT COUNT(*) as total FROM empleados";
    $stmtEmpleados = $db->prepare($queryEmpleados);
    $stmtEmpleados->execute();
    $empleadosCount = $stmtEmpleados->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<h2>Empleados Table</h2>";
    echo "<p>Total employees in system: <strong>$empleadosCount</strong></p>";
    
    if ($empleadosCount > 0) {
        // Check if we can find the employees referenced in Detalle_Planilla
        if ($count > 0) {
            $queryEmployeeCheck = "SELECT dp.id_empleado, 
                                  CASE WHEN e.id_empleado IS NOT NULL THEN 'Found' ELSE 'Missing' END as status
                                  FROM Detalle_Planilla dp
                                  LEFT JOIN empleados e ON dp.id_empleado = e.id_empleado
                                  WHERE dp.id_planilla = :id_planilla";
            $stmtEmployeeCheck = $db->prepare($queryEmployeeCheck);
            $stmtEmployeeCheck->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
            $stmtEmployeeCheck->execute();
            $employeeStatus = $stmtEmployeeCheck->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Employee Status Check:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Employee ID</th><th>Status</th></tr>";
            
            foreach ($employeeStatus as $status) {
                echo "<tr>";
                echo "<td>" . $status['id_empleado'] . "</td>";
                echo "<td>" . $status['status'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking empleados: " . $e->getMessage() . "</p>";
}

// 6. Check case sensitivity in table names
try {
    echo "<h2>Case Sensitivity Check</h2>";
    
    $variants = [
        'Detalle_Planilla', 
        'detalle_planilla', 
        'DETALLE_PLANILLA'
    ];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table Name Variant</th><th>Exists</th></tr>";
    
    foreach ($variants as $variant) {
        $query = "SHOW TABLES LIKE '" . $variant . "'";
        $result = $db->query($query);
        $exists = ($result && $result->rowCount() > 0) ? 'Yes' : 'No';
        
        echo "<tr>";
        echo "<td>" . $variant . "</td>";
        echo "<td>" . $exists . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking case sensitivity: " . $e->getMessage() . "</p>";
}

// Finally - Provide a link to go back
echo "<p><a href='index.php?page=planillas/ver&id=$id_planilla'>Return to Planilla Details</a></p>";
?> 