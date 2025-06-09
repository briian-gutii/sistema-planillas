<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Verificación de Estructura de Empleados</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // 1. Verificar estructura de la tabla empleados
    echo "<h2>Estructura de la tabla empleados:</h2>";
    
    $columnsQuery = "SHOW COLUMNS FROM empleados";
    $columnsResult = $db->query($columnsQuery);
    
    if ($columnsResult) {
        $columns = $columnsResult->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Clave</th><th>Default</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . ($column['Default'] !== null ? $column['Default'] : 'NULL') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // 2. Verificar campos críticos
        $camposEsperados = [
            'id_empleado',
            'primer_nombre', 
            'segundo_nombre',
            'primer_apellido',
            'segundo_apellido',
            'id_departamento',
            'id_puesto'
        ];
        
        $camposExistentes = array_column($columns, 'Field');
        
        echo "<h2>Verificación de campos críticos:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Campo Esperado</th><th>¿Existe?</th></tr>";
        
        foreach ($camposEsperados as $campo) {
            $existe = in_array($campo, $camposExistentes);
            
            echo "<tr>";
            echo "<td>$campo</td>";
            echo "<td>" . ($existe ? "<span style='color: green;'>Sí</span>" : "<span style='color: red;'>No</span>") . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // 3. Verificar datos de muestra
        echo "<h2>Muestra de datos de empleados:</h2>";
        
        $queryEmpleados = "SELECT * FROM empleados LIMIT 3";
        $stmtEmpleados = $db->prepare($queryEmpleados);
        $stmtEmpleados->execute();
        
        $empleados = $stmtEmpleados->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($empleados) > 0) {
            echo "<pre>";
            print_r($empleados);
            echo "</pre>";
            
            // 4. Verificar departamentos
            echo "<h2>Verificación de departamentos:</h2>";
            
            if (in_array('id_departamento', $camposExistentes)) {
                $departamentosIds = array_column($empleados, 'id_departamento');
                $departamentosIds = array_filter($departamentosIds); // Eliminar nulls
                
                if (!empty($departamentosIds)) {
                    $placeholders = implode(',', array_fill(0, count($departamentosIds), '?'));
                    $queryDepts = "SELECT id_departamento, nombre FROM departamentos WHERE id_departamento IN ($placeholders)";
                    
                    $stmtDepts = $db->prepare($queryDepts);
                    foreach ($departamentosIds as $i => $id) {
                        $stmtDepts->bindValue($i+1, $id, PDO::PARAM_INT);
                    }
                    $stmtDepts->execute();
                    
                    $departamentos = $stmtDepts->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<p>Departamentos encontrados:</p>";
                    echo "<pre>";
                    print_r($departamentos);
                    echo "</pre>";
                } else {
                    echo "<p>No se encontraron IDs de departamento en los empleados de muestra.</p>";
                }
            } else {
                echo "<p>La tabla de empleados no tiene el campo id_departamento.</p>";
            }
            
            // 5. Verificar puestos
            echo "<h2>Verificación de puestos:</h2>";
            
            if (in_array('id_puesto', $camposExistentes)) {
                $puestosIds = array_column($empleados, 'id_puesto');
                $puestosIds = array_filter($puestosIds); // Eliminar nulls
                
                if (!empty($puestosIds)) {
                    $placeholders = implode(',', array_fill(0, count($puestosIds), '?'));
                    $queryPuestos = "SELECT id_puesto, nombre FROM puestos WHERE id_puesto IN ($placeholders)";
                    
                    $stmtPuestos = $db->prepare($queryPuestos);
                    foreach ($puestosIds as $i => $id) {
                        $stmtPuestos->bindValue($i+1, $id, PDO::PARAM_INT);
                    }
                    $stmtPuestos->execute();
                    
                    $puestos = $stmtPuestos->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<p>Puestos encontrados:</p>";
                    echo "<pre>";
                    print_r($puestos);
                    echo "</pre>";
                } else {
                    echo "<p>No se encontraron IDs de puesto en los empleados de muestra.</p>";
                }
            } else {
                echo "<p>La tabla de empleados no tiene el campo id_puesto.</p>";
            }
        } else {
            echo "<p>No se encontraron empleados en la base de datos.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Error: No se pudo obtener la estructura de la tabla empleados.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 