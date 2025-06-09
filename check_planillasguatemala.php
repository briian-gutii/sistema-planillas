<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Verificando la base de datos 'planillasguatemala'\n";
echo "==============================================\n\n";

try {
    // Conectar a la base de datos planillasguatemala
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa a la base de datos 'planillasguatemala'\n\n";
    
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
        try {
            // Verificar cuántas planillas hay en total
            $query = "SELECT COUNT(*) FROM `{$planillasTable}`";
            $count = $db->query($query)->fetchColumn();
            echo "Total de planillas: {$count}\n";
            
            // Verificar si la planilla ID 14 existe
            $query = "SELECT COUNT(*) FROM `{$planillasTable}` WHERE id_planilla = 14";
            $count = $db->query($query)->fetchColumn();
            echo "Planilla ID 14: " . ($count > 0 ? "EXISTE" : "NO EXISTE") . "\n";
            
            if ($count > 0) {
                // Mostrar detalles de la planilla
                $queryInfo = "SELECT * FROM `{$planillasTable}` WHERE id_planilla = 14";
                $planilla = $db->query($queryInfo)->fetch(PDO::FETCH_ASSOC);
                echo "Información de la planilla ID 14:\n";
                foreach ($planilla as $campo => $valor) {
                    echo "  {$campo}: " . (is_null($valor) ? "NULL" : $valor) . "\n";
                }
            } else {
                // Mostrar IDs de planillas disponibles
                $query = "SELECT id_planilla FROM `{$planillasTable}` ORDER BY id_planilla";
                $ids = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);
                echo "IDs de planillas disponibles: " . implode(", ", $ids) . "\n";
                
                // Mostrar detalles de la primera planilla como ejemplo
                if (!empty($ids)) {
                    $firstId = $ids[0];
                    $queryInfo = "SELECT * FROM `{$planillasTable}` WHERE id_planilla = {$firstId}";
                    $planilla = $db->query($queryInfo)->fetch(PDO::FETCH_ASSOC);
                    echo "Información de la planilla ID {$firstId} (ejemplo):\n";
                    foreach ($planilla as $campo => $valor) {
                        echo "  {$campo}: " . (is_null($valor) ? "NULL" : $valor) . "\n";
                    }
                }
            }
        } catch (Exception $e) {
            echo "Error al verificar planillas: " . $e->getMessage() . "\n";
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
        echo "Tablas de detalles encontradas: " . implode(", ", $detallesTables) . "\n\n";
        
        foreach ($detallesTables as $tabla) {
            echo "Verificando tabla '{$tabla}':\n";
            
            try {
                // Verificar cuántos registros hay en total
                $query = "SELECT COUNT(*) FROM `{$tabla}`";
                $count = $db->query($query)->fetchColumn();
                echo "- Total de registros: {$count}\n";
                
                if ($count > 0) {
                    // Verificar si hay registros para la planilla ID 14 (o la primera disponible)
                    $planillaId = 14;
                    $query = "SELECT COUNT(*) FROM `{$tabla}` WHERE id_planilla = {$planillaId}";
                    $count = $db->query($query)->fetchColumn();
                    echo "- Registros para planilla ID {$planillaId}: {$count}\n";
                    
                    if ($count > 0) {
                        // Mostrar un ejemplo
                        $queryDetalle = "SELECT * FROM `{$tabla}` WHERE id_planilla = {$planillaId} LIMIT 1";
                        $detalle = $db->query($queryDetalle)->fetch(PDO::FETCH_ASSOC);
                        echo "- Ejemplo de un registro:\n";
                        foreach ($detalle as $campo => $valor) {
                            echo "  {$campo}: " . (is_null($valor) ? "NULL" : $valor) . "\n";
                        }
                    } else if (!empty($ids)) {
                        // Intentar con el primer ID de planilla encontrado
                        $planillaId = $ids[0];
                        $query = "SELECT COUNT(*) FROM `{$tabla}` WHERE id_planilla = {$planillaId}";
                        $count = $db->query($query)->fetchColumn();
                        echo "- Registros para planilla ID {$planillaId} (alternativa): {$count}\n";
                        
                        if ($count > 0) {
                            // Mostrar un ejemplo
                            $queryDetalle = "SELECT * FROM `{$tabla}` WHERE id_planilla = {$planillaId} LIMIT 1";
                            $detalle = $db->query($queryDetalle)->fetch(PDO::FETCH_ASSOC);
                            echo "- Ejemplo de un registro:\n";
                            foreach ($detalle as $campo => $valor) {
                                echo "  {$campo}: " . (is_null($valor) ? "NULL" : $valor) . "\n";
                            }
                        }
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
    
    $empleadosTabla = null;
    foreach ($tableNames as $table) {
        if (strtolower($table) == 'empleados') {
            $empleadosTabla = $table;
            break;
        }
    }
    
    if ($empleadosTabla) {
        echo "Tabla de empleados encontrada: {$empleadosTabla}\n";
        
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
        echo "✗ No se encontró tabla de empleados\n";
    }
    
    echo "\nVERIFICACIÓN COMPLETA.\n";
    
} catch (PDOException $e) {
    echo "ERROR DE CONEXIÓN: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "ERROR GENERAL: " . $e->getMessage() . "\n";
} 