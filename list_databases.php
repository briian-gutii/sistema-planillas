<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Verificando las bases de datos disponibles en el servidor\n";
echo "=================================================\n\n";

try {
    // Conectar sin especificar base de datos
    $db = new PDO('mysql:host=localhost', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa al servidor MySQL\n\n";
    
    // Listar bases de datos
    echo "BASES DE DATOS DISPONIBLES:\n";
    echo "=========================\n";
    $dbs = $db->query("SHOW DATABASES");
    $found = false;
    
    foreach ($dbs as $database) {
        $found = true;
        $dbName = $database[0];
        echo "- " . $dbName . "\n";
    }
    
    if (!$found) {
        echo "No se encontraron bases de datos\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR DE CONEXIÓN: " . $e->getMessage() . "\n";
}

echo "\nVerificación completa.\n"; 