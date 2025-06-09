<?php
// Incluir configuración y conexión a la base de datos
require_once 'includes/config.php';

// Función para mostrar las tablas en la base de datos
function showTables($db, $dbName) {
    echo "<h3>Tablas en la base de datos '$dbName':</h3>";
    
    try {
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No se encontraron tablas.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error al obtener tablas: " . $e->getMessage() . "</p>";
    }
}

// Función para mostrar los campos de una tabla
function showTableColumns($db, $tableName) {
    echo "<h3>Campos de la tabla '$tableName':</h3>";
    
    try {
        $columns = $db->query("SHOW COLUMNS FROM $tableName")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($columns) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "<td>" . ($column['Extra'] ?? '') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No se encontraron campos.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error al obtener campos: " . $e->getMessage() . "</p>";
    }
}

// Obtener conexión a la base de datos
try {
    $db = getDB();
    
    // Obtener nombre de la base de datos actual
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    
    echo "<h1>Estructura de la Base de Datos</h1>";
    echo "<p>Base de datos actual: <strong>$dbName</strong></p>";
    
    // Mostrar todas las tablas
    showTables($db, $dbName);
    
    // Mostrar campos de tablas específicas
    $tablesToShow = ['empleados', 'departamentos', 'puestos', 'planillas', 'detalle_planilla'];
    
    foreach ($tablesToShow as $table) {
        showTableColumns($db, $table);
    }
    
} catch (PDOException $e) {
    echo "<h1>Error de Conexión</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 