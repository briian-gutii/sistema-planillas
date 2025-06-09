<?php
// Script para probar la conexión a la base de datos
echo "<h2>Probando conexión a la base de datos</h2>";

$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "planillasguatemala";

echo "<p>Intentando conectar a MySQL en $dbHost con usuario $dbUser...</p>";

try {
    // Intento de conexión sin especificar base de datos
    $conn = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✓ Conexión a MySQL exitosa.</p>";
    
    // Comprobar si la base de datos existe
    $stmt = $conn->query("SHOW DATABASES LIKE '$dbName'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ Base de datos '$dbName' existe.</p>";
        
        // Intentar conectar a la base de datos específica
        try {
            $dbConn = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
            $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<p style='color:green'>✓ Conexión a la base de datos '$dbName' exitosa.</p>";
            
            // Verificar tablas principales
            $tables = ['usuarios', 'empleados', 'contratos', 'planillas'];
            foreach ($tables as $table) {
                try {
                    $tableCheck = $dbConn->query("SELECT 1 FROM $table LIMIT 1");
                    echo "<p style='color:green'>✓ Tabla '$table' accesible.</p>";
                } catch (PDOException $e) {
                    echo "<p style='color:orange'>⚠ Tabla '$table': " . $e->getMessage() . "</p>";
                }
            }
        } catch (PDOException $e) {
            echo "<p style='color:red'>✗ Error al conectar a la base de datos '$dbName': " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ La base de datos '$dbName' no existe.</p>";
    }
} catch(PDOException $e) {
    echo "<p style='color:red'>✗ Error de conexión a MySQL: " . $e->getMessage() . "</p>";
}

echo "<h3>Información del servidor:</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "</pre>";
?> 