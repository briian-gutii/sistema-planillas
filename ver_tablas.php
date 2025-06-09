<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=planilla', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h1>Diagnóstico de la Base de Datos</h1>";
    
    // Listar todas las tablas
    echo "<h2>Tablas en la base de datos 'planilla':</h2>";
    $tables = $db->query("SHOW TABLES");
    echo "<ul>";
    $tableNames = [];
    foreach ($tables as $table) {
        $tableName = $table[0];
        echo "<li>" . htmlspecialchars($tableName) . "</li>";
        $tableNames[] = $tableName;
    }
    echo "</ul>";
    
    // Buscar tablas relacionadas con planillas
    $planillaTablas = [];
    $detalleTablas = [];
    foreach ($tableNames as $table) {
        $lowerTable = strtolower($table);
        if (strpos($lowerTable, 'planilla') !== false) {
            $planillaTablas[] = $table;
        }
        if (strpos($lowerTable, 'detalle') !== false) {
            $detalleTablas[] = $table;
        }
    }
    
    echo "<h2>Tablas relacionadas con planillas:</h2>";
    echo "<ul>";
    foreach ($planillaTablas as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    echo "<h2>Tablas relacionadas con detalles:</h2>";
    echo "<ul>";
    foreach ($detalleTablas as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // Verificar registros en la planilla ID 14
    echo "<h2>Verificar registros para planilla ID 14:</h2>";
    
    foreach ($detalleTablas as $tabla) {
        echo "<h3>Tabla: " . htmlspecialchars($tabla) . "</h3>";
        $query = "SELECT COUNT(*) FROM `" . $tabla . "` WHERE id_planilla = 14";
        try {
            $count = $db->query($query)->fetchColumn();
            echo "<p>Registros encontrados: <strong>" . $count . "</strong></p>";
            
            if ($count > 0) {
                // Mostrar los primeros 5 registros de ejemplo
                $queryDetalles = "SELECT * FROM `" . $tabla . "` WHERE id_planilla = 14 LIMIT 5";
                $detalles = $db->query($queryDetalles)->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table border='1' cellpadding='3'>";
                // Encabezados de tabla
                if (count($detalles) > 0) {
                    echo "<tr>";
                    foreach (array_keys($detalles[0]) as $columna) {
                        echo "<th>" . htmlspecialchars($columna) . "</th>";
                    }
                    echo "</tr>";
                }
                
                // Datos
                foreach ($detalles as $detalle) {
                    echo "<tr>";
                    foreach ($detalle as $valor) {
                        echo "<td>" . htmlspecialchars($valor ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>Error al consultar la tabla: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar empleados
    echo "<h2>Tabla de empleados:</h2>";
    try {
        $empleadosTabla = "empleados";
        if (!in_array($empleadosTabla, $tableNames)) {
            $empleadosTabla = "Empleados"; // Intentar con mayúscula
        }
        
        $query = "SELECT COUNT(*) FROM `" . $empleadosTabla . "`";
        $count = $db->query($query)->fetchColumn();
        echo "<p>Total de empleados: <strong>" . $count . "</strong></p>";
        
        if ($count > 0) {
            // Mostrar los primeros 5 empleados
            $queryEmpleados = "SELECT * FROM `" . $empleadosTabla . "` LIMIT 5";
            $empleados = $db->query($queryEmpleados)->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='3'>";
            // Encabezados de tabla
            if (count($empleados) > 0) {
                echo "<tr>";
                foreach (array_keys($empleados[0]) as $columna) {
                    echo "<th>" . htmlspecialchars($columna) . "</th>";
                }
                echo "</tr>";
            }
            
            // Datos
            foreach ($empleados as $empleado) {
                echo "<tr>";
                foreach ($empleado as $valor) {
                    echo "<td>" . htmlspecialchars($valor ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error al consultar la tabla de empleados: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<h1 style='color:red'>Error de conexión a la base de datos</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 