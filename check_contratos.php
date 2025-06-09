<?php
require_once 'config/database.php';

try {
    echo "Estructura de la tabla 'contratos':<br>";
    $sql = "DESCRIBE contratos";
    $estructura = fetchAll($sql);
    
    foreach ($estructura as $campo) {
        echo "- " . $campo['Field'] . " (" . $campo['Type'] . ")<br>";
    }
    
    echo "<br>Datos de la tabla contratos:<br>";
    $sql = "SELECT * FROM contratos LIMIT 1";
    $contratos = fetchAll($sql);
    if (count($contratos) > 0) {
        foreach ($contratos as $contrato) {
            foreach ($contrato as $campo => $valor) {
                echo "- $campo: $valor<br>";
            }
        }
    } else {
        echo "No hay datos en la tabla contratos.<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 