<?php
require_once 'config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get the planilla ID from the URL
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : 14;

echo "<h1>Verificar y Corregir Consulta de Planilla</h1>";
echo "<p>Planilla ID: {$id_planilla}</p>";

try {
    $db = getDB();
    
    // 1. Check all tables in the database
    echo "<h2>Tablas en la base de datos:</h2>";
    $tablesQuery = "SHOW TABLES";
    $stmt = $db->query($tablesQuery);
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($allTables as $table) {
        echo "<li>" . $table . "</li>";
    }
    echo "</ul>";
    
    // 2. Find all tables that might be "detail" tables
    $detailTables = [];
    $planillaDetailPatterns = [
        'detalle', 
        'planilla_detalle', 
        'detail'
    ];
    
    foreach ($allTables as $table) {
        $lowerTable = strtolower($table);
        foreach ($planillaDetailPatterns as $pattern) {
            if (strpos($lowerTable, $pattern) !== false) {
                $detailTables[] = $table;
                break;
            }
        }
    }
    
    echo "<h2>Posibles tablas de detalles de planilla:</h2>";
    if (empty($detailTables)) {
        echo "<p>No se encontraron tablas que podrían contener detalles de planilla.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Tabla</th><th>Verificar datos para planilla ID {$id_planilla}</th></tr>";
        
        foreach ($detailTables as $table) {
            echo "<tr>";
            echo "<td>{$table}</td>";
            echo "<td><a href='check_detalles.php?id={$id_planilla}&table={$table}'>Verificar datos</a></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // 3. Check the correct table structure 
    echo "<h2>Probar tablas para ver cuál contiene datos:</h2>";
    
    $tablaCorrecta = null;
    $hayDatos = false;
    
    // Try different table patterns
    $tablesToTry = ['Detalle_Planilla', 'detalle_planilla', 'planilla_detalle'];
    
    // Add any others we found
    foreach ($detailTables as $detailTable) {
        if (!in_array($detailTable, $tablesToTry)) {
            $tablesToTry[] = $detailTable;
        }
    }
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Tabla</th><th>Existe</th><th>Registros para planilla {$id_planilla}</th></tr>";
    
    foreach ($tablesToTry as $table) {
        try {
            // Check if table exists
            $tableQuery = "SHOW TABLES LIKE '{$table}'";
            $stmt = $db->query($tableQuery);
            $tableExists = ($stmt && $stmt->rowCount() > 0);
            
            $count = 0;
            
            if ($tableExists) {
                // Try to count records
                $countQuery = "SELECT COUNT(*) FROM {$table} WHERE id_planilla = :id_planilla";
                $stmt = $db->prepare($countQuery);
                $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
                $stmt->execute();
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $tablaCorrecta = $table;
                    $hayDatos = true;
                }
            }
            
            echo "<tr>";
            echo "<td>{$table}</td>";
            echo "<td>" . ($tableExists ? "✅ Sí" : "❌ No") . "</td>";
            echo "<td>" . ($tableExists ? $count : "N/A") . "</td>";
            echo "</tr>";
            
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td>{$table}</td>";
            echo "<td colspan='2'>Error: " . $e->getMessage() . "</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    // 4. Suggest the correct query
    echo "<h2>Resultados y Recomendaciones:</h2>";
    
    if ($hayDatos) {
        echo "<p style='color: green'>✅ Se encontraron datos para la planilla {$id_planilla} en la tabla <strong>{$tablaCorrecta}</strong>.</p>";
        
        // Show a sample query
        $correctQuery = "SELECT pd.*, e.*
                       FROM {$tablaCorrecta} pd
                       JOIN empleados e ON pd.id_empleado = e.id_empleado
                       WHERE pd.id_planilla = {$id_planilla}";
        
        echo "<h3>Consulta recomendada:</h3>";
        echo "<pre>" . htmlspecialchars($correctQuery) . "</pre>";
        
        // Test the query
        $stmt = $db->prepare($correctQuery);
        $stmt->execute();
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sample) {
            echo "<h3>Muestra de datos:</h3>";
            echo "<pre>";
            print_r($sample);
            echo "</pre>";
            
            // Provide an update link
            echo "<p><a href='update_planilla_query.php?table={$tablaCorrecta}' class='btn btn-primary'>Actualizar consulta en ver.php</a></p>";
        }
    } else {
        echo "<p style='color: red'>❌ No se encontraron datos para la planilla {$id_planilla} en ninguna tabla.</p>";
        echo "<p>Recomendaciones:</p>";
        echo "<ol>";
        echo "<li>Verifique que la planilla tenga detalles asociados en la base de datos.</li>";
        echo "<li>Utilice la herramienta <a href='util_fix_detalle_planilla.php?id={$id_planilla}'>Generar Datos de Prueba</a> para crear registros de prueba.</li>";
        echo "</ol>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red'>Error: " . $e->getMessage() . "</p>";
}
?> 