<?php
require_once 'config/database.php';

try {
    echo "Iniciando inserción de datos de ejemplo...<br>";
    
    // Verificar estructura empleados
    $sql = "DESCRIBE empleados";
    $camposEmpleados = fetchAll($sql);
    $camposEmpleadosNombres = array_column($camposEmpleados, 'Field');
    
    // 1. Insertar empleados de ejemplo
    $empleados = [
        [
            'primer_nombre' => 'María', 
            'segundo_nombre' => 'José', 
            'primer_apellido' => 'González',
            'segundo_apellido' => 'Ruiz',
            'DPI' => '4578123450101',
            'NIT' => '45781234',
            'fecha_nacimiento' => '1990-05-15',
            'genero' => 'Femenino',
            'estado_civil' => 'Casado',
            'direccion' => 'Zona 10, Ciudad de Guatemala',
            'telefono' => '55124578',
            'email' => 'mgonzalez@ejemplo.com',
            'fecha_ingreso' => '2023-01-15',
            'id_departamento' => 1,
            'estado' => 'Activo'
        ],
        [
            'primer_nombre' => 'Juan', 
            'segundo_nombre' => 'Carlos', 
            'primer_apellido' => 'Pérez',
            'segundo_apellido' => 'López',
            'DPI' => '5689741230101',
            'NIT' => '56897412',
            'fecha_nacimiento' => '1988-09-22',
            'genero' => 'Masculino',
            'estado_civil' => 'Soltero',
            'direccion' => 'Zona 15, Ciudad de Guatemala',
            'telefono' => '42578963',
            'email' => 'jperez@ejemplo.com',
            'fecha_ingreso' => '2023-02-01',
            'id_departamento' => 2,
            'estado' => 'Activo'
        ],
        [
            'primer_nombre' => 'Ana', 
            'segundo_nombre' => 'Lucía', 
            'primer_apellido' => 'Rodríguez',
            'segundo_apellido' => 'García',
            'DPI' => '8974563210101',
            'NIT' => '89745632',
            'fecha_nacimiento' => '1992-11-10',
            'genero' => 'Femenino',
            'estado_civil' => 'Casado',
            'direccion' => 'Zona 4, Ciudad de Guatemala',
            'telefono' => '52369874',
            'email' => 'arodriguez@ejemplo.com',
            'fecha_ingreso' => '2023-03-15',
            'id_departamento' => 3,
            'estado' => 'Activo'
        ],
        [
            'primer_nombre' => 'Roberto', 
            'segundo_nombre' => 'Antonio', 
            'primer_apellido' => 'Castillo',
            'segundo_apellido' => 'Méndez',
            'DPI' => '3652147890101',
            'NIT' => '36521478',
            'fecha_nacimiento' => '1985-07-25',
            'genero' => 'Masculino',
            'estado_civil' => 'Casado',
            'direccion' => 'Zona 7, Ciudad de Guatemala',
            'telefono' => '47896523',
            'email' => 'rcastillo@ejemplo.com',
            'fecha_ingreso' => '2022-11-01',
            'id_departamento' => 4,
            'estado' => 'Activo'
        ],
        [
            'primer_nombre' => 'Lucía', 
            'segundo_nombre' => 'Isabel', 
            'primer_apellido' => 'Morales',
            'segundo_apellido' => 'Castro',
            'DPI' => '7412589630101',
            'NIT' => '74125896',
            'fecha_nacimiento' => '1995-02-18',
            'genero' => 'Femenino',
            'estado_civil' => 'Soltero',
            'direccion' => 'Zona 11, Ciudad de Guatemala',
            'telefono' => '53698741',
            'email' => 'lmorales@ejemplo.com',
            'fecha_ingreso' => '2023-04-03',
            'id_departamento' => 5,
            'estado' => 'Activo'
        ]
    ];
    
    $empleadosInsertados = 0;
    foreach ($empleados as $empleado) {
        // Verificar si el empleado ya existe
        $existeEmpleado = fetchRow("SELECT id_empleado FROM empleados WHERE DPI = :DPI", [
            ':DPI' => $empleado['DPI']
        ]);
        
        if ($existeEmpleado) {
            echo "El empleado con DPI {$empleado['DPI']} ya existe.<br>";
            continue;
        }
        
        // Filtrar campos que no existen en la tabla
        $camposValidos = [];
        $valores = [];
        foreach ($empleado as $campo => $valor) {
            if (in_array($campo, $camposEmpleadosNombres)) {
                $camposValidos[] = $campo;
                $valores[':' . $campo] = $valor;
            }
        }
        
        $campos = implode(', ', $camposValidos);
        $parametros = implode(', ', array_map(function($campo) { return ':' . $campo; }, $camposValidos));
        
        $sqlInsert = "INSERT INTO empleados ($campos) VALUES ($parametros)";
        query($sqlInsert, $valores);
        $empleadosInsertados++;
    }
    
    echo "Se han insertado $empleadosInsertados empleados.<br><br>";
    
    // 2. Crear planillas de ejemplo
    echo "Creando planillas de ejemplo...<br>";
    
    // Obtener los periodos de nómina activos
    $periodos = fetchAll("SELECT * FROM periodos_nomina WHERE estado = 'Activo' ORDER BY fecha_inicio DESC LIMIT 3");
    
    if (count($periodos) == 0) {
        echo "No hay periodos de nómina activos para crear planillas.<br>";
    } else {
        $planillasInsertadas = 0;
        
        foreach ($periodos as $periodo) {
            // Comprobar si ya existe una planilla para este periodo
            $existePlanilla = fetchRow("SELECT id_planilla FROM planillas WHERE id_periodo = :id_periodo", [
                ':id_periodo' => $periodo['id_periodo']
            ]);
            
            if ($existePlanilla) {
                echo "Ya existe una planilla para el periodo {$periodo['descripcion']}.<br>";
                continue;
            }
            
            // Crear planilla
            $descripcion = "Planilla - " . $periodo['descripcion'];
            $fecha_generacion = date('Y-m-d H:i:s');
            $estado = 'Borrador';
            $total_bruto = rand(30000, 50000);
            $total_deducciones = $total_bruto * 0.12; // 12% de deducciones
            $total_neto = $total_bruto - $total_deducciones;
            
            $sqlInsert = "INSERT INTO planillas (id_periodo, id_departamento, descripcion, fecha_generacion, estado, 
                          total_bruto, total_deducciones, total_neto, usuario_genero) 
                          VALUES (:id_periodo, :id_departamento, :descripcion, :fecha_generacion, :estado, 
                          :total_bruto, :total_deducciones, :total_neto, :usuario_genero)";
            
            query($sqlInsert, [
                ':id_periodo' => $periodo['id_periodo'],
                ':id_departamento' => null, // Planilla para todos los departamentos
                ':descripcion' => $descripcion,
                ':fecha_generacion' => $fecha_generacion,
                ':estado' => $estado,
                ':total_bruto' => $total_bruto,
                ':total_deducciones' => $total_deducciones,
                ':total_neto' => $total_neto,
                ':usuario_genero' => 'admin'
            ]);
            
            $planillasInsertadas++;
            
            // También crear una planilla específica para el departamento de Administración
            $sqlInsert = "INSERT INTO planillas (id_periodo, id_departamento, descripcion, fecha_generacion, estado, 
                          total_bruto, total_deducciones, total_neto, usuario_genero) 
                          VALUES (:id_periodo, :id_departamento, :descripcion, :fecha_generacion, :estado, 
                          :total_bruto, :total_deducciones, :total_neto, :usuario_genero)";
            
            query($sqlInsert, [
                ':id_periodo' => $periodo['id_periodo'],
                ':id_departamento' => 1, // Departamento de Administración
                ':descripcion' => $descripcion . " - Administración",
                ':fecha_generacion' => $fecha_generacion,
                ':estado' => $estado,
                ':total_bruto' => $total_bruto / 2,
                ':total_deducciones' => ($total_bruto / 2) * 0.12,
                ':total_neto' => ($total_bruto / 2) * 0.88,
                ':usuario_genero' => 'admin'
            ]);
            
            $planillasInsertadas++;
        }
        
        echo "Se han insertado $planillasInsertadas planillas.<br><br>";
    }
    
    // 3. Crear horas extra
    echo "Creando registros de horas extra...<br>";
    
    // Obtener empleados
    $empleadosExistentes = fetchAll("SELECT id_empleado FROM empleados WHERE estado = 'Activo' LIMIT 5");
    
    if (count($empleadosExistentes) == 0) {
        echo "No hay empleados para crear horas extra.<br>";
    } else if (count($periodos) == 0) {
        echo "No hay periodos para crear horas extra.<br>";
    } else {
        $horasExtraInsertadas = 0;
        
        // Verificar si existe la tabla horas_extra
        $tablasExistentes = fetchAll("SHOW TABLES LIKE 'horas_extra'");
        
        if (count($tablasExistentes) == 0) {
            echo "La tabla horas_extra no existe. Creándola...<br>";
            $sqlCrearTabla = "CREATE TABLE IF NOT EXISTS horas_extra (
                id_hora_extra INT AUTO_INCREMENT PRIMARY KEY,
                id_empleado INT NOT NULL,
                id_periodo INT NOT NULL,
                fecha DATE NOT NULL,
                cantidad DECIMAL(10,2) NOT NULL,
                valor_hora DECIMAL(10,2) NOT NULL,
                descripcion TEXT NULL,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_empleado (id_empleado),
                INDEX idx_periodo (id_periodo),
                INDEX idx_fecha (fecha)
            )";
            query($sqlCrearTabla);
        }
        
        // Crear horas extra para cada empleado en cada periodo
        foreach ($empleadosExistentes as $empleado) {
            foreach ($periodos as $periodo) {
                // Fechas aleatorias dentro del periodo
                $fechaInicio = strtotime($periodo['fecha_inicio']);
                $fechaFin = strtotime($periodo['fecha_fin']);
                $fechaAleatoria = date('Y-m-d', rand($fechaInicio, $fechaFin));
                
                // Horas y valor aleatorios
                $cantidad = rand(1, 8);
                $valor_hora = rand(50, 150);
                
                $sqlInsert = "INSERT INTO horas_extra (id_empleado, id_periodo, fecha, cantidad, valor_hora, descripcion) 
                              VALUES (:id_empleado, :id_periodo, :fecha, :cantidad, :valor_hora, :descripcion)";
                
                query($sqlInsert, [
                    ':id_empleado' => $empleado['id_empleado'],
                    ':id_periodo' => $periodo['id_periodo'],
                    ':fecha' => $fechaAleatoria,
                    ':cantidad' => $cantidad,
                    ':valor_hora' => $valor_hora,
                    ':descripcion' => "Horas extra por trabajo adicional"
                ]);
                
                $horasExtraInsertadas++;
            }
        }
        
        echo "Se han insertado $horasExtraInsertadas registros de horas extra.<br><br>";
    }
    
    echo "Datos de demostración insertados correctamente en el sistema.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "<br>Traza: <pre>" . $e->getTraceAsString() . "</pre>";
} 