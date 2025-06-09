<?php
require_once 'config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get the table name from the URL
$table = isset($_GET['table']) ? $_GET['table'] : '';

echo "<h1>Actualizar Consulta de Planilla</h1>";

if (empty($table)) {
    echo "<p style='color: red'>Error: No se especificó una tabla.</p>";
    echo "<p><a href='fix_query.php'>Volver a verificar tablas</a></p>";
    exit;
}

// Function to update the query in a file
function updateQueryInFile($filePath, $tableName) {
    $fileContent = file_get_contents($filePath);
    
    if ($fileContent === false) {
        return "Error: No se pudo leer el archivo $filePath";
    }
    
    // Create the pattern to look for
    $pattern = '/FROM\s+Detalle_Planilla\s+pd/i';
    $replacement = "FROM $tableName pd";
    
    // Replace the table name in the query
    $updatedContent = preg_replace($pattern, $replacement, $fileContent);
    
    if ($updatedContent === null) {
        return "Error: Falló al reemplazar texto en el archivo.";
    }
    
    // Write the updated content back to the file
    if (file_put_contents($filePath, $updatedContent) === false) {
        return "Error: No se pudo escribir en el archivo $filePath";
    }
    
    return "✅ Actualización exitosa: Se cambió 'Detalle_Planilla' a '$tableName' en el archivo.";
}

// Update the query in ver.php
$verPhpPath = 'pages/planillas/ver.php';
if (file_exists($verPhpPath)) {
    $result = updateQueryInFile($verPhpPath, $table);
    echo "<p>$result</p>";
    
    // Also update debug files
    $debugFiles = [
        'debug_planilla.php',
        'debug_planilla_data.php',
        'debug_empleados.php'
    ];
    
    foreach ($debugFiles as $debugFile) {
        if (file_exists($debugFile)) {
            $debugResult = updateQueryInFile($debugFile, $table);
            echo "<p>$debugFile: $debugResult</p>";
        }
    }
    
    echo "<p>La actualización se ha completado. Ahora debería poder ver los detalles de planilla correctamente.</p>";
    
    // Provide links to test
    echo "<h2>Enlaces para probar:</h2>";
    echo "<ul>";
    echo "<li><a href='index.php?page=planillas/ver&id=14'>Ver planilla con ID 14</a></li>";
    echo "<li><a href='debug_planilla.php?id=14'>Diagnosticar planilla con ID 14</a></li>";
    echo "<li><a href='util_fix_detalle_planilla.php'>Herramienta para generar datos de prueba</a></li>";
    echo "</ul>";
} else {
    echo "<p style='color: red'>Error: No se pudo encontrar el archivo $verPhpPath</p>";
}
?> 