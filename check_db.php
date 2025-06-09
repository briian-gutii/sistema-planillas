<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Script de verificación de base de datos iniciado\n";

try {
    echo "Intentando conectar a la base de datos...\n";
    $db = new PDO('mysql:host=localhost;dbname=planilla', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa a la base de datos planilla\n\n";
    
    // Listar todas las tablas
    echo "TABLAS EN LA BASE DE DATOS:\n";
    echo "=========================\n";
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
        echo "NO SE ENCONTRARON TABLAS EN LA BASE DE DATOS\n";
    }
    echo "\n";
    
    // Verificar si hay una tabla de planillas
    echo "BUSCANDO TABLA PRINCIPAL DE PLANILLAS...\n";
    $planillasTable = null;
    foreach ($tableNames as $table) {
        if (strtolower($table) == 'planillas') {
            $planillasTable = $table;
            break;
        }
    }
    
    if ($planillasTable) {
        echo "Tabla de planillas encontrada: {$planillasTable}\n";
        // Verificar si la planilla ID 14 existe
        $query = "SELECT COUNT(*) FROM `{$planillasTable}` WHERE id_planilla = 14";
        try {
            $count = $db->query($query)->fetchColumn();
            echo "Planilla ID 14: " . ($count > 0 ? "EXISTE" : "NO EXISTE") . "\n";
        } catch (Exception $e) {
            echo "Error al verificar planilla ID 14: " . $e->getMessage() . "\n";
        }
    } else {
        echo "No se encontró una tabla principal de planillas\n";
    }
    echo "\n";
    
    // Verificar planilla_detalle vs Detalle_Planilla
    echo "VERIFICANDO TABLAS DE DETALLE PARA PLANILLA ID 14:\n";
    echo "=============================================\n";
    
    $tablasAVerificar = ['planilla_detalle', 'Detalle_Planilla'];
    
    foreach ($tablasAVerificar as $tabla) {
        echo "Verificando tabla '{$tabla}':\n";
        
        if (in_array($tabla, $tableNames)) {
            echo "- Tabla '{$tabla}' EXISTE.\n";
            
            try {
                $query = "SELECT COUNT(*) FROM `{$tabla}` WHERE id_planilla = 14";
                $count = $db->query($query)->fetchColumn();
                echo "  - Registros encontrados para planilla ID 14: {$count}\n";
                
                if ($count > 0) {
                    $queryDetalle = "SELECT * FROM `{$tabla}` WHERE id_planilla = 14 LIMIT 1";
                    $detalle = $db->query($queryDetalle)->fetch(PDO::FETCH_ASSOC);
                    echo "  - Ejemplo de un registro:\n";
                    foreach ($detalle as $campo => $valor) {
                        echo "    {$campo}: " . (is_null($valor) ? "NULL" : $valor) . "\n";
                    }
                }
            } catch (Exception $e) {
                echo "  - Error al consultar tabla: " . $e->getMessage() . "\n";
            }
        } else {
            echo "- Tabla '{$tabla}' NO EXISTE.\n";
        }
        echo "\n";
    }
    
    // Verificar tabla empleados
    echo "VERIFICANDO TABLA DE EMPLEADOS:\n";
    echo "============================\n";
    
    $empleadosTabla = "empleados";
    if (in_array($empleadosTabla, $tableNames)) {
        echo "Tabla '{$empleadosTabla}' existe.\n";
        
        try {
            $query = "SELECT COUNT(*) FROM `{$empleadosTabla}`";
            $count = $db->query($query)->fetchColumn();
            echo "  - Total de empleados: {$count}\n";
            
            if ($count > 0) {
                $queryEmpleado = "SELECT * FROM `{$empleadosTabla}` LIMIT 1";
                $empleado = $db->query($queryEmpleado)->fetch(PDO::FETCH_ASSOC);
                echo "  - Ejemplo de un empleado:\n";
                foreach ($empleado as $campo => $valor) {
                    echo "    {$campo}: " . (is_null($valor) ? "NULL" : $valor) . "\n";
                }
            }
        } catch (Exception $e) {
            echo "  - Error al consultar empleados: " . $e->getMessage() . "\n";
        }
    } else {
        $empleadosTabla = "Empleados"; // Intentar con mayúscula
        if (in_array($empleadosTabla, $tableNames)) {
            echo "Tabla '{$empleadosTabla}' existe (con mayúscula).\n";
            
            try {
                $query = "SELECT COUNT(*) FROM `{$empleadosTabla}`";
                $count = $db->query($query)->fetchColumn();
                echo "  - Total de empleados: {$count}\n";
            } catch (Exception $e) {
                echo "  - Error al consultar empleados: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Ninguna tabla de empleados encontrada.\n";
        }
    }
    
    echo "\nVERIFICACIÓN COMPLETA.\n";
    
} catch (PDOException $e) {
    echo "ERROR DE CONEXIÓN: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "ERROR GENERAL: " . $e->getMessage() . "\n";
}

echo "Script finalizado.\n"; 