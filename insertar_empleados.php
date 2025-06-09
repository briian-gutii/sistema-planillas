<?php
require_once 'config/database.php';

echo "<h2>Inserción de Empleados</h2>";

try {
    // Preparar datos de empleados según la estructura real
    $empleados = [
        [
            'DPI' => '1234567890101',
            'NIT' => '12345678',
            'primer_nombre' => 'Juan',
            'segundo_nombre' => 'Carlos',
            'primer_apellido' => 'Pérez',
            'segundo_apellido' => 'López',
            'fecha_nacimiento' => '1990-01-15',
            'genero' => 'Masculino',
            'estado_civil' => 'Soltero',
            'direccion' => 'Zona 10, Ciudad de Guatemala',
            'zona' => 10,
            'departamento' => 'Guatemala',
            'municipio' => 'Guatemala',
            'telefono' => '55123456',
            'email' => 'jperez@ejemplo.com',
            'fecha_ingreso' => '2023-01-10',
            'estado' => 'Activo'
        ],
        [
            'DPI' => '2345678901101',
            'NIT' => '23456789',
            'primer_nombre' => 'María',
            'segundo_nombre' => 'José',
            'primer_apellido' => 'González',
            'segundo_apellido' => 'Ruiz',
            'fecha_nacimiento' => '1992-06-20',
            'genero' => 'Femenino',
            'estado_civil' => 'Casado',
            'direccion' => 'Zona 15, Ciudad de Guatemala',
            'zona' => 15,
            'departamento' => 'Guatemala',
            'municipio' => 'Guatemala',
            'telefono' => '42789456',
            'email' => 'mgonzalez@ejemplo.com',
            'fecha_ingreso' => '2023-02-05',
            'estado' => 'Activo'
        ],
        [
            'DPI' => '3456789012101',
            'NIT' => '34567890',
            'primer_nombre' => 'Roberto',
            'segundo_nombre' => 'Antonio',
            'primer_apellido' => 'Rodríguez',
            'segundo_apellido' => 'García',
            'fecha_nacimiento' => '1985-10-25',
            'genero' => 'Masculino',
            'estado_civil' => 'Casado',
            'direccion' => 'Zona 7, Ciudad de Guatemala',
            'zona' => 7,
            'departamento' => 'Guatemala',
            'municipio' => 'Guatemala',
            'telefono' => '56987456',
            'email' => 'rrodriguez@ejemplo.com',
            'fecha_ingreso' => '2022-11-01',
            'estado' => 'Activo'
        ]
    ];
    
    // Insertar cada empleado
    $empleadosInsertados = 0;
    foreach ($empleados as $empleado) {
        // Verificar si el empleado ya existe
        $existeEmpleado = fetchRow("SELECT COUNT(*) as total FROM empleados WHERE DPI = :DPI", [
            ':DPI' => $empleado['DPI']
        ]);
        
        if ($existeEmpleado && $existeEmpleado['total'] > 0) {
            echo "El empleado con DPI {$empleado['DPI']} ya existe.<br>";
            continue;
        }
        
        $campos = implode(', ', array_keys($empleado));
        $placeholders = ':' . implode(', :', array_keys($empleado));
        
        $sqlInsert = "INSERT INTO empleados ($campos) VALUES ($placeholders)";
        
        $params = [];
        foreach ($empleado as $campo => $valor) {
            $params[":$campo"] = $valor;
        }
        
        try {
            query($sqlInsert, $params);
            $empleadosInsertados++;
            echo "Empleado {$empleado['primer_nombre']} {$empleado['primer_apellido']} insertado correctamente.<br>";
        } catch (Exception $e) {
            echo "Error al insertar empleado {$empleado['primer_nombre']} {$empleado['primer_apellido']}: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><strong>Total de empleados insertados: $empleadosInsertados</strong><br><br>";
    
    // Listar los empleados existentes
    echo "<h3>Empleados en la base de datos:</h3>";
    $sql = "SELECT id_empleado, DPI, CONCAT(primer_nombre, ' ', primer_apellido) as nombre, estado, fecha_ingreso FROM empleados";
    $empleadosDB = fetchAll($sql);
    
    if (count($empleadosDB) > 0) {
        echo "<table border='1' cellpadding='4'>";
        echo "<tr><th>ID</th><th>DPI</th><th>Nombre</th><th>Estado</th><th>Fecha Ingreso</th></tr>";
        
        foreach ($empleadosDB as $emp) {
            echo "<tr>";
            echo "<td>{$emp['id_empleado']}</td>";
            echo "<td>{$emp['DPI']}</td>";
            echo "<td>{$emp['nombre']}</td>";
            echo "<td>{$emp['estado']}</td>";
            echo "<td>{$emp['fecha_ingreso']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "No hay empleados registrados.";
    }
    
} catch (Exception $e) {
    echo "Error general: " . $e->getMessage();
} 