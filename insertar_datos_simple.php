<?php
require_once 'config/database.php';

echo "<h2>Inserción de Datos Básicos</h2>";

try {
    // 1. Verificar e insertar empleados
    echo "<h3>Insertando Empleados</h3>";
    
    // Verificar la estructura de la tabla empleados
    $estructura = fetchAll("DESCRIBE empleados");
    echo "Estructura de la tabla empleados:<br>";
    echo "<pre>";
    print_r($estructura);
    echo "</pre>";
    
    // Preparar empleados con datos mínimos para funcionar
    $empleados = [
        [
            'primer_nombre' => 'Juan', 
            'primer_apellido' => 'Pérez',
            'DPI' => '1234567890101',
            'estado' => 'Activo',
            'id_departamento' => 1
        ],
        [
            'primer_nombre' => 'María', 
            'primer_apellido' => 'González',
            'DPI' => '9876543210101',
            'estado' => 'Activo',
            'id_departamento' => 2
        ],
        [
            'primer_nombre' => 'Pedro', 
            'primer_apellido' => 'Ramírez',
            'DPI' => '5678901234101',
            'estado' => 'Activo',
            'id_departamento' => 3
        ]
    ];
    
    // Insertar empleados
    foreach ($empleados as $empleado) {
        $existeEmpleado = fetchRow("SELECT COUNT(*) as total FROM empleados WHERE DPI = :DPI", [
            ':DPI' => $empleado['DPI']
        ]);
        
        if ($existeEmpleado && $existeEmpleado['total'] > 0) {
            echo "El empleado con DPI {$empleado['DPI']} ya existe.<br>";
            continue;
        }
        
        $sql = "INSERT INTO empleados (primer_nombre, primer_apellido, DPI, estado, id_departamento) 
                VALUES (:primer_nombre, :primer_apellido, :DPI, :estado, :id_departamento)";
        
        try {
            query($sql, [
                ':primer_nombre' => $empleado['primer_nombre'],
                ':primer_apellido' => $empleado['primer_apellido'],
                ':DPI' => $empleado['DPI'],
                ':estado' => $empleado['estado'],
                ':id_departamento' => $empleado['id_departamento']
            ]);
            echo "Empleado {$empleado['primer_nombre']} {$empleado['primer_apellido']} insertado correctamente.<br>";
        } catch (Exception $e) {
            echo "Error al insertar empleado {$empleado['primer_nombre']} {$empleado['primer_apellido']}: " . $e->getMessage() . "<br>";
        }
    }
    
    // 2. Insertar horas extra para los empleados
    echo "<h3>Insertando Horas Extra</h3>";
    
    // Verificar la existencia de la tabla
    $tablasExistentes = fetchAll("SHOW TABLES LIKE 'horas_extra'");
    if (count($tablasExistentes) == 0) {
        echo "La tabla horas_extra no existe. Intentando crearla...<br>";
        $sqlCrearTabla = "CREATE TABLE IF NOT EXISTS horas_extra (
            id_hora_extra INT AUTO_INCREMENT PRIMARY KEY,
            id_empleado INT NOT NULL,
            id_periodo INT NOT NULL,
            fecha DATE NOT NULL,
            cantidad DECIMAL(10,2) NOT NULL,
            valor_hora DECIMAL(10,2) NOT NULL,
            descripcion TEXT NULL
        )";
        query($sqlCrearTabla);
        echo "Tabla horas_extra creada.<br>";
    } else {
        echo "La tabla horas_extra ya existe.<br>";
    }
    
    // Obtener empleados
    $empleadosDB = fetchAll("SELECT id_empleado FROM empleados WHERE estado = 'Activo' LIMIT 3");
    
    // Obtener periodos
    $periodos = fetchAll("SELECT id_periodo FROM periodos_nomina WHERE estado = 'Activo' LIMIT 2");
    
    if (count($empleadosDB) > 0 && count($periodos) > 0) {
        foreach ($empleadosDB as $emp) {
            foreach ($periodos as $periodo) {
                $sql = "INSERT INTO horas_extra (id_empleado, id_periodo, fecha, cantidad, valor_hora, descripcion) 
                       VALUES (:id_empleado, :id_periodo, :fecha, :cantidad, :valor_hora, :descripcion)";
                       
                try {
                    query($sql, [
                        ':id_empleado' => $emp['id_empleado'],
                        ':id_periodo' => $periodo['id_periodo'],
                        ':fecha' => date('Y-m-d'),
                        ':cantidad' => 5,
                        ':valor_hora' => 75,
                        ':descripcion' => 'Trabajo extra'
                    ]);
                    echo "Hora extra registrada para empleado ID: {$emp['id_empleado']}, periodo ID: {$periodo['id_periodo']}.<br>";
                } catch (Exception $e) {
                    echo "Error al insertar hora extra: " . $e->getMessage() . "<br>";
                }
            }
        }
    } else {
        echo "No hay empleados o periodos suficientes para crear horas extra.<br>";
    }
    
    echo "<h3>Datos insertados correctamente</h3>";
    
} catch (Exception $e) {
    echo "Error general: " . $e->getMessage();
} 