<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "DETALLES DE LA PLANILLA 15\n";
    echo str_repeat('=', 40) . "\n";
    
    $detalles = $db->query('SELECT * FROM detalle_planilla WHERE id_planilla=15')->fetchAll(PDO::FETCH_ASSOC);
    if (empty($detalles)) {
        echo "No hay detalles para la planilla 15\n";
        exit;
    }
    foreach ($detalles as $row) {
        var_export($row);
        echo "\n" . str_repeat('-', 40) . "\n";
        flush();
        // Buscar datos del empleado relacionado
        $id_empleado = $row['id_empleado'];
        $empleado = $db->query("SELECT * FROM empleados WHERE id_empleado = $id_empleado")->fetch(PDO::FETCH_ASSOC);
        if ($empleado) {
            echo "Empleado relacionado:\n";
            var_export($empleado);
        } else {
            echo "Empleado relacionado: NO ENCONTRADO\n";
        }
        echo "\n" . str_repeat('=', 40) . "\n";
        flush();
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} 