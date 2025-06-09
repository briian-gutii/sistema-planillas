<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Estructura de la Base de Datos: planillasguatemala</h1>";

try {
    $db = getDB();
    
    // Obtener todas las tablas
    $query = "SHOW TABLES FROM planillasguatemala";
    $stmt = $db->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tablas en la base de datos:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>{$table}</strong></li>";
    }
    echo "</ul>";
    
    // Mostrar la estructura de cada tabla
    echo "<h2>Estructura detallada de las tablas:</h2>";
    foreach ($tables as $table) {
        echo "<h3>Tabla: {$table}</h3>";
        
        // Obtener estructura
        $query = "DESCRIBE {$table}";
        $stmt = $db->query($query);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Si es la tabla contratos, mostrar algunos datos
        if ($table == 'contratos') {
            echo "<h4>Muestra de datos en tabla contratos:</h4>";
            $query = "SELECT * FROM contratos LIMIT 5";
            $stmt = $db->query($query);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($data) > 0) {
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr>";
                foreach (array_keys($data[0]) as $header) {
                    echo "<th>{$header}</th>";
                }
                echo "</tr>";
                
                foreach ($data as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . (is_null($value) ? 'NULL' : $value) . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No hay datos en la tabla contratos.</p>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 