<?php
/**
 * Script para configurar la base de datos del sistema de planillas
 * Este script crea la base de datos y aplica el esquema de tablas
 */

// Parámetros de conexión
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "planillasguatemala";

echo "<h1>Configuración de la base de datos del sistema de planillas</h1>";

try {
    // Conectar sin especificar base de datos
    $conn = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>Conexión al servidor MySQL establecida correctamente</p>";
    
    // Verificar si la base de datos existe
    $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
    $dbExists = $stmt->fetchColumn();
    
    if (!$dbExists) {
        // Crear la base de datos
        $conn->exec("CREATE DATABASE `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p>✓ Base de datos '$dbName' creada correctamente</p>";
    } else {
        echo "<p>La base de datos '$dbName' ya existe</p>";
    }
    
    // Seleccionar la base de datos
    $conn->exec("USE `$dbName`");
    
    // Leer y ejecutar el archivo de esquema
    $schemaFile = 'db_schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        
        // Dividir el script SQL en instrucciones individuales
        $queries = explode(';', $sql);
        
        // Ejecutar cada instrucción por separado
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $conn->exec($query);
            }
        }
        
        echo "<p>✓ Esquema de base de datos aplicado correctamente</p>";
        
        // Insertar usuario administrador predeterminado para el sistema 
        $passwordHash = password_hash('123456', PASSWORD_DEFAULT);
        $conn->exec("
        INSERT INTO Usuarios_Sistema (id_usuario, nombre_usuario, contrasena, correo, rol, fecha_creacion, estado) VALUES
        (1, 'admin', '$passwordHash', 'admin@sistema.com', 'Administrador', NOW(), 'Activo')");
        echo "<p>✓ Usuario administrador creado</p>";
    } else {
        echo "<p style='color:red'>Error: No se encuentra el archivo de esquema de base de datos ($schemaFile)</p>";
    }
    
    echo "<h2 style='color:green'>✓ Configuración de base de datos completada</h2>";
    echo "<p>Ahora puede <a href='datos_prueba.php'>generar los datos de prueba</a> o <a href='index.php'>ir al sistema</a>.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error en la configuración de la base de datos:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 