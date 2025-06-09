<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "DESCRIBE empleados\n";
    echo str_repeat('=', 40) . "\n";
    $desc = $db->query('DESCRIBE empleados')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($desc as $col) {
        echo $col['Field'] . ' | ' . $col['Type'] . ' | ' . $col['Null'] . ' | ' . $col['Key'] . ' | ' . $col['Default'] . ' | ' . $col['Extra'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} 