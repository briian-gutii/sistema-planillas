<?php
require_once 'config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Planilla Data Diagnostic Tool</h1>";

// Get database connection
try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Get the planilla ID from the URL parameter or use the most recent one
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no ID was provided, try to get the most recent planilla
if ($id_planilla <= 0) {
    try {
        $query = "SELECT id_planilla FROM Planillas ORDER BY fecha_generacion DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $id_planilla = $result['id_planilla'];
            echo "<p>Using most recent planilla ID: <strong>$id_planilla</strong></p>";
        } else {
            echo "<p style='color: red;'>No planillas found. Please enter a planilla ID in the URL (?id=X)</p>";
            exit;
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error finding a planilla: " . $e->getMessage() . "</p>";
        exit;
    }
}

// Test both uppercase and lowercase table names to check for MySQL case sensitivity issues
echo "<h2>Testing Table Names (Case Sensitivity)</h2>";

$tableVariants = [
    'planillas', 'Planillas', 'PLANILLAS',
    'detalle_planilla', 'Detalle_Planilla', 'DETALLE_PLANILLA'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Table Name</th><th>Count Query Result</th><th>Status</th></tr>";

foreach ($tableVariants as $tableName) {
    try {
        $query = "SELECT COUNT(*) as count FROM $tableName";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<tr><td>$tableName</td><td>$count</td><td style='color:green'>EXISTS</td></tr>";
    } catch (Exception $e) {
        echo "<tr><td>$tableName</td><td>ERROR</td><td style='color:red'>NOT FOUND</td></tr>";
    }
}

echo "</table>";

// Check if the system is running on Windows (case-insensitive) or Linux/Unix (case-sensitive)
$os = PHP_OS;
echo "<p>Operating System: <strong>$os</strong> (";
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "Windows - typically case-insensitive for filenames";
} else {
    echo "Unix/Linux - typically case-sensitive for filenames";
}
echo ")</p>";

// Check MySQL version and settings
try {
    $query = "SELECT VERSION() as mysql_version, @@lower_case_table_names as case_sensitivity";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $mysql_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>MySQL Version: <strong>" . $mysql_info['mysql_version'] . "</strong></p>";
    echo "<p>Case Sensitivity Setting (lower_case_table_names): <strong>" . $mysql_info['case_sensitivity'] . "</strong><br>";
    echo "<small>0 = Case-sensitive, 1 = Case-insensitive (store as lowercase), 2 = Case-insensitive (store as defined)</small></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting MySQL info: " . $e->getMessage() . "</p>";
}

// Check basic planilla record 
echo "<h2>Checking Planilla Record</h2>";
try {
    $query = "SELECT * FROM Planillas WHERE id_planilla = :id_planilla";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmt->execute();
    $planilla = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($planilla) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($planilla as $field => $value) {
            echo "<tr><td>$field</td><td>" . (is_null($value) ? 'NULL' : $value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No planilla found with ID $id_planilla</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error querying planilla: " . $e->getMessage() . "</p>";
}

// Check raw detalle records with direct SQL
echo "<h2>Raw Query Test for Detalle_Planilla</h2>";

try {
    $query = "SELECT * FROM Detalle_Planilla WHERE id_planilla = :id_planilla";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmt->execute();
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($detalles) > 0) {
        echo "<p style='color: green;'>✓ Found " . count($detalles) . " detail records</p>";
        
        // Show first record as sample
        echo "<h3>Sample Detail Record:</h3>";
        echo "<pre>";
        print_r($detalles[0]);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>No detail records found for planilla ID $id_planilla</p>";
        
        // Check if there are any records in the table
        $query = "SELECT COUNT(*) as count FROM Detalle_Planilla";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p>Total records in Detalle_Planilla: $count</p>";
        
        if ($count > 0) {
            // Get a sample of planilla IDs that do have details
            $query = "SELECT id_planilla, COUNT(*) as count FROM Detalle_Planilla GROUP BY id_planilla LIMIT 5";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Planilla IDs that have details:</p>";
            echo "<ul>";
            foreach ($samples as $sample) {
                echo "<li>Planilla ID: " . $sample['id_planilla'] . " (" . $sample['count'] . " details)</li>";
            }
            echo "</ul>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error querying Detalle_Planilla: " . $e->getMessage() . "</p>";
}

// Try to diagnose join issues
echo "<h2>Diagnosing JOIN Issues</h2>";

// 1. Check if empleados exists for the detail records
try {
    $query = "SELECT dp.id_detalle, dp.id_empleado, 
             (SELECT COUNT(*) FROM empleados e WHERE e.id_empleado = dp.id_empleado) as empleado_exists
             FROM Detalle_Planilla dp
             WHERE dp.id_planilla = :id_planilla";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($resultados) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Detail ID</th><th>Employee ID</th><th>Employee Exists</th></tr>";
        
        $allEmployeesExist = true;
        foreach ($resultados as $resultado) {
            $exists = $resultado['empleado_exists'] > 0;
            if (!$exists) $allEmployeesExist = false;
            
            echo "<tr>";
            echo "<td>" . $resultado['id_detalle'] . "</td>";
            echo "<td>" . $resultado['id_empleado'] . "</td>";
            echo "<td style='color:" . ($exists ? "green" : "red") . "'>" . ($exists ? "Yes" : "No") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($allEmployeesExist) {
            echo "<p style='color: green;'>✓ All employees referenced in detail records exist</p>";
        } else {
            echo "<p style='color: red;'>Some employees referenced in detail records are missing from the empleados table</p>";
        }
    } else {
        echo "<p>No detail records found to check</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking employee references: " . $e->getMessage() . "</p>";
}

// Function for testing a query that should work
function testQuery($db, $query, $id_planilla, $description) {
    echo "<h3>$description</h3>";
    echo "<p>Query: <code>" . htmlspecialchars($query) . "</code></p>";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color: green;'>✓ Query executed successfully. Returned " . count($results) . " records.</p>";
        
        if (count($results) > 0) {
            echo "<details>";
            echo "<summary>View Results (First record)</summary>";
            echo "<pre>";
            print_r($results[0]);
            echo "</pre>";
            echo "</details>";
        }
        
        return true;
    } catch (Exception $e) {
        echo "<p style='color: red;'>Query failed: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Test JOIN queries with different table combinations
$queries = [
    "Basic Detalle_Planilla Query" => 
        "SELECT * FROM Detalle_Planilla WHERE id_planilla = :id_planilla",
    
    "JOIN with empleados" => 
        "SELECT dp.*, e.* 
         FROM Detalle_Planilla dp
         JOIN empleados e ON dp.id_empleado = e.id_empleado
         WHERE dp.id_planilla = :id_planilla",
    
    "Complete JOIN (exactly as in ver.php)" => 
        "SELECT dp.*, e.*
         FROM Detalle_Planilla dp
         JOIN empleados e ON dp.id_empleado = e.id_empleado
         WHERE dp.id_planilla = :id_planilla"
];

echo "<h2>Testing Different JOIN Queries</h2>";

foreach ($queries as $description => $query) {
    testQuery($db, $query, $id_planilla, $description);
}

// Try an alternate ID test (try generating a placeholder record)
echo "<h2>Creating a Test Query</h2>";

// Find another planilla ID that has data
try {
    $query = "SELECT id_planilla FROM Detalle_Planilla GROUP BY id_planilla HAVING COUNT(*) > 0 LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $test_id = $result['id_planilla'];
        echo "<p>Found a planilla ID with data: <strong>$test_id</strong></p>";
        
        // Test querying this ID
        if ($test_id != $id_planilla) {
            echo "<h3>Testing with alternate planilla ID: $test_id</h3>";
            
            $query = "SELECT dp.*, 
                     e.nombres, e.apellidos, e.codigo_empleado, e.dpi,
                     d.nombre as departamento, p.nombre as puesto
                     FROM Detalle_Planilla dp
                     JOIN empleados e ON dp.id_empleado = e.id_empleado
                     LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                     LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
                     WHERE dp.id_planilla = :id_planilla
                     ORDER BY e.apellidos, e.nombres";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_planilla', $test_id, PDO::PARAM_INT);
            $stmt->execute();
            $test_detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($test_detalles) > 0) {
                echo "<p style='color: green;'>✓ Alternate ID query returned " . count($test_detalles) . " records. The query works!</p>";
                echo "<p style='color: orange;'><strong>CONCLUSION:</strong> The issue seems to be that planilla ID $id_planilla does not have associated detail records.</p>";
            } else {
                echo "<p style='color: red;'>The alternate ID query also returned no results.</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>Could not find any planilla with details.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error testing alternate ID: " . $e->getMessage() . "</p>";
}

// Show possible actions to take
echo "<h2>Possible Actions</h2>";
echo "<ul>";
echo "<li><a href='debug_planilla.php?id=$id_planilla'>Run detailed diagnostics for planilla ID $id_planilla</a></li>";
echo "<li><a href='index.php?page=planillas/ver&id=$id_planilla'>Return to planilla details page</a></li>";
echo "<li><a href='index.php?page=planillas/lista'>Go to planillas list</a></li>";
echo "</ul>";

// Create a sample query to regenerate details for this planilla
echo "<h2>Sample Query to Generate Missing Details</h2>";
echo "<pre>";
echo "-- Sample query to create dummy details for testing (run this in phpMyAdmin)\n";
echo "INSERT INTO Detalle_Planilla (id_planilla, id_empleado, dias_trabajados, salario_base, bonificacion_incentivo, \n";
echo "                            salario_total, igss_laboral, total_deducciones, liquido_recibir)\n";
echo "SELECT $id_planilla, id_empleado, 30, 5000, 250, 5250, 250, 250, 5000\n";
echo "FROM empleados\n";
echo "WHERE estado = 'Activo'\n";
echo "LIMIT 5;  -- This will create 5 test records\n";
echo "</pre>";
?> 