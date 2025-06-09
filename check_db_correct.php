<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Script de verificación de bases de datos iniciado\n";
echo "================================================\n\n";

// Posibles nombres de la base de datos
$posibles_nombres = [
    'planillas guatemala',
    'planillas_guatemala',
    'planillasguatemala',
    'planilla',
    'planillas'
];

$conexion_exitosa = false;
$db = null;

foreach ($posibles_nombres as $nombre_db) {
    echo "Intentando conectar a la base de datos '{$nombre_db}'...\n";
    
    try {
        $db = new PDO("mysql:host=localhost;dbname={$nombre_db}", 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✓ CONEXIÓN EXITOSA a '{$nombre_db}'\n\n";
        $conexion_exitosa = true;
        break;
    } catch (PDOException $e) {
        echo "✗ No se pudo conectar a '{$nombre_db}': " . $e->getMessage() . "\n\n";
    }
}

if (!$conexion_exitosa) {
    echo "ERROR: No se pudo conectar a ninguna de las bases de datos probadas.\n";
    echo "Por favor verifica el nombre exacto de la base de datos.\n";
    exit;
}

// Continuamos con el análisis de la base de datos correcta
echo "TABLAS EN LA BASE DE DATOS:\n";
echo "==========================\n";
$tablesResult = $db->query("SHOW TABLES");
$tableNames = [];
$tablesFound = false;

foreach ($tablesResult as $table) {
    $tablesFound = true;
    $tableName = $table[0];
    echo "- " . $tableName . "\n";
    $tableNames[] = $tableName;
}

if (!$tablesFound) {
    echo "✗ NO SE ENCONTRARON TABLAS EN LA BASE DE DATOS\n";
    exit;
}
echo "\n";

// Buscar tablas de planillas y detalles
$planillasTable = null;
$detallesTables = [];

foreach ($tableNames as $table) {
    $lowerTable = strtolower($table);
    if ($lowerTable == 'planillas') {
        $planillasTable = $table;
    }
    if (strpos($lowerTable, 'detalle') !== false || strpos($lowerTable, 'planilla_') !== false) {
        $detallesTables[] = $table;
    }
}

// Verificar tabla principal de planillas
echo "TABLA PRINCIPAL DE PLANILLAS:\n";
echo "===========================\n";
if ($planillasTable) {
    echo "Tabla de planillas encontrada: {$planillasTable}\n";
    // Verificar si la planilla ID 14 existe
    $query = "SELECT COUNT(*) FROM `{$planillasTable}` WHERE id_planilla = 14";
    try {
        $count = $db->query($query)->fetchColumn();
        echo "Planilla ID 14: " . ($count > 0 ? "EXISTE" : "NO EXISTE") . "\n";
        
        if ($count > 0) {
            // Mostrar detalles de la planilla
            $queryInfo = "SELECT * FROM `{$planillasTable}` WHERE id_planilla = 14";
            $planilla = $db->query($queryInfo)->fetch(PDO::FETCH_ASSOC);
            echo "Información de la planilla:\n";
            foreach ($planilla as $campo => $valor) {
                echo "  {$campo}: " . (is_null($valor) ? "NULL" : $valor) . "\n";
            }
        }
    } catch (Exception $e) {
        echo "Error al verificar planilla ID 14: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ No se encontró una tabla principal de planillas\n";
}
echo "\n";

// Verificar tablas de detalles
echo "TABLAS DE DETALLES DE PLANILLA:\n";
echo "============================\n";

if (empty($detallesTables)) {
    echo "✗ No se encontraron tablas relacionadas con detalles de planilla\n";
} else {
    foreach ($detallesTables as $tabla) {
        echo "Verificando tabla '{$tabla}':\n";
        
        try {
            $query = "SELECT COUNT(*) FROM `{$tabla}` WHERE id_planilla = 14";
            $count = $db->query($query)->fetchColumn();
            echo "- Registros encontrados para planilla ID 14: {$count}\n";
            
            if ($count > 0) {
                $queryDetalle = "SELECT * FROM `{$tabla}` WHERE id_planilla = 14 LIMIT 1";
                $detalle = $db->query($queryDetalle)->fetch(PDO::FETCH_ASSOC);
                echo "- Ejemplo de un registro:\n";
                foreach ($detalle as $campo => $valor) {
                    echo "  {$campo}: " . (is_null($valor) ? "NULL" : $valor) . "\n";
                }
            }
        } catch (Exception $e) {
            echo "- Error al consultar tabla: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
}

// Verificar tabla empleados
echo "TABLA DE EMPLEADOS:\n";
echo "=================\n";

$empleadosTabla = "empleados";
if (in_array($empleadosTabla, $tableNames)) {
    echo "Tabla '{$empleadosTabla}' existe.\n";
    
    try {
        $query = "SELECT COUNT(*) FROM `{$empleadosTabla}`";
        $count = $db->query($query)->fetchColumn();
        echo "- Total de empleados: {$count}\n";
        
        if ($count > 0) {
            $queryEmpleado = "SELECT * FROM `{$empleadosTabla}` LIMIT 1";
            $empleado = $db->query($queryEmpleado)->fetch(PDO::FETCH_ASSOC);
            echo "- Ejemplo de un empleado:\n";
            foreach ($empleado as $campo => $valor) {
                echo "  {$campo}: " . (is_null($valor) ? "NULL" : $valor) . "\n";
            }
        }
    } catch (Exception $e) {
        echo "- Error al consultar empleados: " . $e->getMessage() . "\n";
    }
} else {
    $empleadosTabla = "Empleados"; // Intentar con mayúscula
    if (in_array($empleadosTabla, $tableNames)) {
        echo "Tabla '{$empleadosTabla}' existe (con mayúscula).\n";
        
        try {
            $query = "SELECT COUNT(*) FROM `{$empleadosTabla}`";
            $count = $db->query($query)->fetchColumn();
            echo "- Total de empleados: {$count}\n";
        } catch (Exception $e) {
            echo "- Error al consultar empleados: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ Ninguna tabla de empleados encontrada.\n";
    }
}

echo "\nVERIFICACIÓN COMPLETA.\n"; 