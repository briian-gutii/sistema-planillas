<?php
require_once 'config/database.php';

echo "<h1>Depuración de conexión a la base de datos</h1>";

// Verificar la configuración
echo "<h2>Configuración actual:</h2>";
echo "<pre>";
echo "Host: " . $dbHost . "\n";
echo "Usuario: " . $dbUser . "\n";
echo "Contraseña: " . ($dbPass ? "[Establecida]" : "[Vacía]") . "\n";
echo "Base de datos: " . $dbName . "\n";
echo "</pre>";

// Verificar la conexión usando getDB()
echo "<h2>Verificación de conexión usando getDB():</h2>";
try {
    $db = getDB();
    echo "<p style='color:green;'>✓ Conexión exitosa con getDB()</p>";
    
    // Verificar a qué base de datos estamos conectados realmente
    $query = "SELECT database() as db_name";
    $result = $db->query($query)->fetch();
    echo "<p>Base de datos actual: <strong>" . $result['db_name'] . "</strong></p>";
    
    // Verificar tablas
    echo "<h3>Tablas en la base de datos actual:</h3>";
    $tablesResult = $db->query("SHOW TABLES");
    echo "<ul>";
    $tableCount = 0;
    foreach ($tablesResult as $table) {
        $tableCount++;
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
    echo "<p>Total de tablas: " . $tableCount . "</p>";
    
    // Verificar planillas
    try {
        $planillasQuery = "SELECT COUNT(*) FROM planillas";
        $planillasCount = $db->query($planillasQuery)->fetchColumn();
        echo "<p>Total de planillas: " . $planillasCount . "</p>";
        
        if ($planillasCount > 0) {
            $idsQuery = "SELECT id_planilla FROM planillas ORDER BY id_planilla";
            $ids = $db->query($idsQuery)->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>IDs de planillas disponibles: " . implode(", ", $ids) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error al verificar planillas: " . $e->getMessage() . "</p>";
    }
    
    // Verificar planilla 14 específica
    try {
        $planilla14Query = "SELECT COUNT(*) FROM planillas WHERE id_planilla = 14";
        $planilla14Count = $db->query($planilla14Query)->fetchColumn();
        echo "<p>Planilla ID 14: " . ($planilla14Count > 0 ? "EXISTE" : "NO EXISTE") . "</p>";
        
        // Verificar detalles para planilla 14
        $detallesQuery = "SELECT COUNT(*) FROM detalle_planilla WHERE id_planilla = 14";
        $detallesCount = $db->query($detallesQuery)->fetchColumn();
        echo "<p>Detalles para planilla ID 14: " . $detallesCount . "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error al verificar planilla 14: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error de conexión con getDB(): " . $e->getMessage() . "</p>";
}

// Verificar la misma consulta que usa el script ver.php
echo "<h2>Prueba de consulta de planilla específica:</h2>";
$id_planilla = 14; // El ID que estás intentando ver

try {
    // Obtener los detalles de la planilla
    $queryDetalles = "SELECT pd.*, 
                   e.*
                   FROM detalle_planilla pd
                   LEFT JOIN empleados e ON pd.id_empleado = e.id_empleado
                   WHERE pd.id_planilla = :id_planilla";
    
    $stmtDetalles = $db->prepare($queryDetalles);
    $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtDetalles->execute();
    $detalles = $stmtDetalles->fetchAll();
    
    echo "<p>Registros encontrados: " . count($detalles) . "</p>";
    
    if (count($detalles) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($detalles[0]) as $columna) {
            echo "<th>" . htmlspecialchars($columna) . "</th>";
        }
        echo "</tr>";
        
        foreach ($detalles as $detalle) {
            echo "<tr>";
            foreach ($detalle as $valor) {
                echo "<td>" . htmlspecialchars($valor ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No se encontraron detalles para la planilla ID 14.</p>";
        
        // Probar con planilla ID 15 que sabemos que existe
        $id_planilla = 15;
        $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
        $stmtDetalles->execute();
        $detalles = $stmtDetalles->fetchAll();
        
        echo "<p>Probando con planilla ID 15...</p>";
        echo "<p>Registros encontrados: " . count($detalles) . "</p>";
        
        if (count($detalles) > 0) {
            echo "<p style='color:green;'>✓ Se encontraron registros para planilla ID 15</p>";
            echo "<p>El problema es que estás intentando ver la planilla ID 14, que no existe.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error al ejecutar consulta: " . $e->getMessage() . "</p>";
}
?> 