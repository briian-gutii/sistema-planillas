<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Verificación de Base de Datos</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // 1. Listar todas las tablas
    $tablesQuery = "SHOW TABLES";
    $stmt = $db->query($tablesQuery);
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tablas en la base de datos:</h2>";
    echo "<ul>";
    foreach ($allTables as $table) {
        echo "<li>" . $table . "</li>";
    }
    echo "</ul>";
    
    // 2. Buscar tabla de detalles de planilla
    $planillaDetailTables = [];
    foreach ($allTables as $table) {
        $lowerTable = strtolower($table);
        if (strpos($lowerTable, 'detalle') !== false || 
            strpos($lowerTable, 'planilla_') !== false || 
            strpos($lowerTable, '_planilla') !== false) {
            $planillaDetailTables[] = $table;
        }
    }
    
    echo "<h2>Tablas relacionadas con detalles de planilla:</h2>";
    if (empty($planillaDetailTables)) {
        echo "<p>No se encontraron tablas que parezcan contener detalles de planilla.</p>";
    } else {
        echo "<ul>";
        foreach ($planillaDetailTables as $table) {
            echo "<li>" . $table . "</li>";
        }
        echo "</ul>";
    }
    
    // 3. Mostrar estructura de las tablas importantes
    echo "<h2>Estructura de tablas importantes:</h2>";
    
    $importantTables = array_merge(['Planillas', 'empleados'], $planillaDetailTables);
    
    foreach ($importantTables as $table) {
        try {
            $columnsQuery = "SHOW COLUMNS FROM `$table`";
            $columnsResult = $db->query($columnsQuery);
            
            if ($columnsResult && $columnsResult->rowCount() > 0) {
                $columns = $columnsResult->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h3>Tabla: $table</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Clave</th><th>Default</th></tr>";
                
                foreach ($columns as $column) {
                    echo "<tr>";
                    echo "<td>" . $column['Field'] . "</td>";
                    echo "<td>" . $column['Type'] . "</td>";
                    echo "<td>" . $column['Null'] . "</td>";
                    echo "<td>" . $column['Key'] . "</td>";
                    echo "<td>" . ($column['Default'] !== null ? $column['Default'] : 'NULL') . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No se pudo obtener la estructura de la tabla '$table'.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error al consultar la estructura de '$table': " . $e->getMessage() . "</p>";
        }
    }
    
    // 4. Verificar existencia de datos en las tablas de detalle
    echo "<h2>Verificación de datos en tablas de detalle:</h2>";
    
    foreach ($planillaDetailTables as $table) {
        try {
            $countQuery = "SELECT COUNT(*) as total FROM `$table`";
            $countResult = $db->query($countQuery);
            
            if ($countResult) {
                $count = $countResult->fetch(PDO::FETCH_ASSOC)['total'];
                
                echo "<h3>Tabla: $table</h3>";
                echo "<p>Total de registros: <strong>$count</strong></p>";
                
                if ($count > 0) {
                    // Mostrar datos de muestra
                    $sampleQuery = "SELECT * FROM `$table` LIMIT 1";
                    $sampleResult = $db->query($sampleQuery);
                    $sample = $sampleResult->fetch(PDO::FETCH_ASSOC);
                    
                    echo "<p>Muestra de datos:</p>";
                    echo "<pre>";
                    print_r($sample);
                    echo "</pre>";
                }
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error al consultar datos de '$table': " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error en la conexión a la base de datos: " . $e->getMessage() . "</p>";
}
?> 