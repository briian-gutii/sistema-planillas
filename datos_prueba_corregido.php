<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Generación de Datos de Prueba Completos (Corregido)</h1>";

try {
    // Conectar a la base de datos
    $db = getDB();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // 1. Crear departamentos de prueba si no existen
    $departamentos = [
        ['nombre' => 'Administración', 'descripcion' => 'Departamento de administración'],
        ['nombre' => 'Ventas', 'descripcion' => 'Departamento de ventas'],
        ['nombre' => 'Producción', 'descripcion' => 'Departamento de producción'],
        ['nombre' => 'Recursos Humanos', 'descripcion' => 'Departamento de recursos humanos'],
        ['nombre' => 'Tecnología', 'descripcion' => 'Departamento de tecnología']
    ];
    
    echo "<h2>Creando departamentos de prueba...</h2>";
    $departamentosCreados = 0;
    
    foreach ($departamentos as $depto) {
        // Verificar si ya existe
        $checkDepto = $db->prepare("SELECT COUNT(*) FROM departamentos WHERE nombre = :nombre");
        $checkDepto->bindParam(':nombre', $depto['nombre']);
        $checkDepto->execute();
        
        if ($checkDepto->fetchColumn() == 0) {
            $insertDepto = $db->prepare("INSERT INTO departamentos (nombre, descripcion) VALUES (:nombre, :descripcion)");
            $insertDepto->bindParam(':nombre', $depto['nombre']);
            $insertDepto->bindParam(':descripcion', $depto['descripcion']);
            $insertDepto->execute();
            $departamentosCreados++;
        }
    }
    
    echo "<p>Departamentos creados: $departamentosCreados</p>";
    
    // 2. Crear puestos de trabajo si no existen
    $puestos = [
        ['nombre' => 'Gerente', 'descripcion' => 'Gerente de departamento'],
        ['nombre' => 'Supervisor', 'descripcion' => 'Supervisor de área'],
        ['nombre' => 'Analista', 'descripcion' => 'Analista de procesos'],
        ['nombre' => 'Asistente', 'descripcion' => 'Asistente administrativo'],
        ['nombre' => 'Técnico', 'descripcion' => 'Técnico especializado']
    ];
    
    echo "<h2>Creando puestos de trabajo...</h2>";
    $puestosCreados = 0;
    
    foreach ($puestos as $puesto) {
        // Verificar si ya existe
        $checkPuesto = $db->prepare("SELECT COUNT(*) FROM puestos WHERE nombre = :nombre");
        $checkPuesto->bindParam(':nombre', $puesto['nombre']);
        $checkPuesto->execute();
        
        if ($checkPuesto->fetchColumn() == 0) {
            $insertPuesto = $db->prepare("INSERT INTO puestos (nombre, descripcion) VALUES (:nombre, :descripcion)");
            $insertPuesto->bindParam(':nombre', $puesto['nombre']);
            $insertPuesto->bindParam(':descripcion', $puesto['descripcion']);
            $insertPuesto->execute();
            $puestosCreados++;
        }
    }
    
    echo "<p>Puestos creados: $puestosCreados</p>";
    
    // 3. Verificar la estructura de la tabla empleados
    echo "<h2>Verificando estructura de la tabla empleados...</h2>";
    
    // Verificar si la tabla empleados tiene la columna fecha_contratacion
    $checkColumn = $db->query("SHOW COLUMNS FROM empleados LIKE 'fecha_contratacion'");
    $hasFechaContratacion = ($checkColumn && $checkColumn->rowCount() > 0);
    
    if (!$hasFechaContratacion) {
        echo "<p style='color: orange;'>⚠ La columna 'fecha_contratacion' no existe en la tabla empleados. Se ajustará la consulta.</p>";
    }
    
    // Obtener todas las columnas disponibles
    $columns = $db->query("SHOW COLUMNS FROM empleados")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Columnas disponibles en la tabla empleados: " . implode(", ", $columns) . "</p>";
    
    // 3. Crear empleados de prueba si no existen suficientes
    $countEmpleados = $db->query("SELECT COUNT(*) FROM empleados")->fetchColumn();
    echo "<p>Empleados existentes: $countEmpleados</p>";
    
    if ($countEmpleados < 10) {
        echo "<h3>Creando empleados de prueba...</h3>";
        
        // Obtener IDs de departamentos
        $departamentosIDs = $db->query("SELECT id_departamento FROM departamentos")->fetchAll(PDO::FETCH_COLUMN);
        
        // Obtener IDs de puestos
        $puestosIDs = $db->query("SELECT id_puesto FROM puestos")->fetchAll(PDO::FETCH_COLUMN);
        
        // Nombres y apellidos para generar datos aleatorios
        $nombres = ['Juan', 'María', 'Pedro', 'Ana', 'Luis', 'Carmen', 'José', 'Lucía', 'Miguel', 'Sofía'];
        $apellidos = ['García', 'López', 'Martínez', 'Rodríguez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Díaz'];
        
        // Crear 10 empleados
        $empleadosCreados = 0;
        
        // Preparar SQL según las columnas disponibles
        $sqlFields = [];
        $sqlValues = [];
        
        // Campos obligatorios
        $sqlFields[] = "primer_nombre";
        $sqlValues[] = ":primer_nombre";
        
        $sqlFields[] = "segundo_nombre";
        $sqlValues[] = ":segundo_nombre";
        
        $sqlFields[] = "primer_apellido";
        $sqlValues[] = ":primer_apellido";
        
        $sqlFields[] = "segundo_apellido";
        $sqlValues[] = ":segundo_apellido";
        
        // Verificar y agregar otros campos si existen
        if (in_array('fecha_nacimiento', $columns)) {
            $sqlFields[] = "fecha_nacimiento";
            $sqlValues[] = ":fecha_nacimiento";
        }
        
        if (in_array('email', $columns)) {
            $sqlFields[] = "email";
            $sqlValues[] = ":email";
        }
        
        if (in_array('telefono', $columns)) {
            $sqlFields[] = "telefono";
            $sqlValues[] = ":telefono";
        }
        
        if (in_array('direccion', $columns)) {
            $sqlFields[] = "direccion";
            $sqlValues[] = ":direccion";
        }
        
        if (in_array('dpi', $columns)) {
            $sqlFields[] = "dpi";
            $sqlValues[] = ":dpi";
        }
        
        if (in_array('nit', $columns)) {
            $sqlFields[] = "nit";
            $sqlValues[] = ":nit";
        }
        
        if (in_array('id_departamento', $columns)) {
            $sqlFields[] = "id_departamento";
            $sqlValues[] = ":id_departamento";
        }
        
        if (in_array('id_puesto', $columns)) {
            $sqlFields[] = "id_puesto";
            $sqlValues[] = ":id_puesto";
        }
        
        if (in_array('fecha_contratacion', $columns)) {
            $sqlFields[] = "fecha_contratacion";
            $sqlValues[] = ":fecha_contratacion";
        }
        
        if (in_array('salario_base', $columns)) {
            $sqlFields[] = "salario_base";
            $sqlValues[] = ":salario_base";
        }
        
        if (in_array('estado', $columns)) {
            $sqlFields[] = "estado";
            $sqlValues[] = ":estado";
        }
        
        $sql = "INSERT INTO empleados (" . implode(", ", $sqlFields) . ") VALUES (" . implode(", ", $sqlValues) . ")";
        echo "<p>SQL generado: $sql</p>";
        
        $insertEmpleado = $db->prepare($sql);
        
        for ($i = 0; $i < 10; $i++) {
            $primerNombre = $nombres[array_rand($nombres)];
            $segundoNombre = $nombres[array_rand($nombres)];
            $primerApellido = $apellidos[array_rand($apellidos)];
            $segundoApellido = $apellidos[array_rand($apellidos)];
            
            $email = strtolower($primerNombre) . '.' . strtolower($primerApellido) . '@ejemplo.com';
            $telefono = '555' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $direccion = 'Calle ' . rand(1, 50) . ', Ciudad';
            
            $dpi = '1' . str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
            $nit = rand(10000000, 99999999) . rand(0, 9);
            
            $deptoID = $departamentosIDs[array_rand($departamentosIDs)];
            $puestoID = $puestosIDs[array_rand($puestosIDs)];
            
            // Fechas (entre 25 y 50 años atrás)
            $yearNac = date('Y') - rand(25, 50);
            $fechaNacimiento = $yearNac . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
            
            // Fecha contratación (entre 1 y 10 años atrás)
            $yearContrato = date('Y') - rand(1, 10);
            $fechaContratacion = $yearContrato . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
            
            $salarioBase = rand(3000, 10000);
            $estado = 'Activo';
            
            // Binding de parámetros que siempre están presentes
            $insertEmpleado->bindParam(':primer_nombre', $primerNombre);
            $insertEmpleado->bindParam(':segundo_nombre', $segundoNombre);
            $insertEmpleado->bindParam(':primer_apellido', $primerApellido);
            $insertEmpleado->bindParam(':segundo_apellido', $segundoApellido);
            
            // Binding de parámetros opcionales
            if (in_array('fecha_nacimiento', $columns)) {
                $insertEmpleado->bindParam(':fecha_nacimiento', $fechaNacimiento);
            }
            
            if (in_array('email', $columns)) {
                $insertEmpleado->bindParam(':email', $email);
            }
            
            if (in_array('telefono', $columns)) {
                $insertEmpleado->bindParam(':telefono', $telefono);
            }
            
            if (in_array('direccion', $columns)) {
                $insertEmpleado->bindParam(':direccion', $direccion);
            }
            
            if (in_array('dpi', $columns)) {
                $insertEmpleado->bindParam(':dpi', $dpi);
            }
            
            if (in_array('nit', $columns)) {
                $insertEmpleado->bindParam(':nit', $nit);
            }
            
            if (in_array('id_departamento', $columns)) {
                $insertEmpleado->bindParam(':id_departamento', $deptoID);
            }
            
            if (in_array('id_puesto', $columns)) {
                $insertEmpleado->bindParam(':id_puesto', $puestoID);
            }
            
            if (in_array('fecha_contratacion', $columns)) {
                $insertEmpleado->bindParam(':fecha_contratacion', $fechaContratacion);
            }
            
            if (in_array('salario_base', $columns)) {
                $insertEmpleado->bindParam(':salario_base', $salarioBase);
            }
            
            if (in_array('estado', $columns)) {
                $insertEmpleado->bindParam(':estado', $estado);
            }
            
            $insertEmpleado->execute();
            $empleadosCreados++;
        }
        
        echo "<p>Empleados creados: $empleadosCreados</p>";
    }
    
    // 4. Crear periodos de pago si no existen
    echo "<h2>Verificando periodos de pago...</h2>";
    
    $countPeriodos = $db->query("SELECT COUNT(*) FROM periodos_nomina")->fetchColumn();
    echo "<p>Periodos existentes: $countPeriodos</p>";
    
    if ($countPeriodos < 3) {
        echo "<h3>Creando periodos de pago...</h3>";
        
        $periodos = [
            ['nombre' => 'Enero 2023', 'fecha_inicio' => '2023-01-01', 'fecha_fin' => '2023-01-31', 'estado' => 'Cerrado'],
            ['nombre' => 'Febrero 2023', 'fecha_inicio' => '2023-02-01', 'fecha_fin' => '2023-02-28', 'estado' => 'Cerrado'],
            ['nombre' => 'Marzo 2023', 'fecha_inicio' => '2023-03-01', 'fecha_fin' => '2023-03-31', 'estado' => 'Cerrado'],
            ['nombre' => 'Abril 2023', 'fecha_inicio' => '2023-04-01', 'fecha_fin' => '2023-04-30', 'estado' => 'Cerrado'],
            ['nombre' => 'Mayo 2023', 'fecha_inicio' => '2023-05-01', 'fecha_fin' => '2023-05-31', 'estado' => 'Activo']
        ];
        
        $periodosCreados = 0;
        $insertPeriodo = $db->prepare("
            INSERT INTO periodos_nomina (nombre, fecha_inicio, fecha_fin, estado)
            VALUES (:nombre, :fecha_inicio, :fecha_fin, :estado)
        ");
        
        foreach ($periodos as $periodo) {
            $insertPeriodo->bindParam(':nombre', $periodo['nombre']);
            $insertPeriodo->bindParam(':fecha_inicio', $periodo['fecha_inicio']);
            $insertPeriodo->bindParam(':fecha_fin', $periodo['fecha_fin']);
            $insertPeriodo->bindParam(':estado', $periodo['estado']);
            $insertPeriodo->execute();
            $periodosCreados++;
        }
        
        echo "<p>Periodos creados: $periodosCreados</p>";
    }
    
    // 5. Crear planillas de prueba
    echo "<h2>Creando planillas de prueba...</h2>";
    
    // Obtener IDs de periodos
    $periodosIDs = $db->query("SELECT id_periodo FROM periodos_nomina")->fetchAll(PDO::FETCH_COLUMN);
    
    // Crear 3 planillas nuevas
    $planillasCreadas = 0;
    $insertPlanilla = $db->prepare("
        INSERT INTO Planillas (
            descripcion, fecha_generacion, id_periodo, estado
        ) VALUES (
            :descripcion, :fecha_generacion, :id_periodo, 'Generada'
        )
    ");
    
    for ($i = 0; $i < 3; $i++) {
        $desc = 'Planilla de prueba #' . ($i+1) . ' - ' . date('Y-m-d H:i:s');
        $fecha = date('Y-m-d H:i:s');
        $idPeriodo = $periodosIDs[array_rand($periodosIDs)];
        
        $insertPlanilla->bindParam(':descripcion', $desc);
        $insertPlanilla->bindParam(':fecha_generacion', $fecha);
        $insertPlanilla->bindParam(':id_periodo', $idPeriodo);
        $insertPlanilla->execute();
        
        $idPlanilla = $db->lastInsertId();
        $planillasCreadas++;
        
        // 6. Crear detalles para cada planilla
        echo "<h3>Creando detalles para planilla #$idPlanilla...</h3>";
        
        // Obtener empleados activos
        $empleados = $db->query("
            SELECT id_empleado, salario_base 
            FROM empleados 
            WHERE estado = 'Activo'
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $detallesCreados = 0;
        
        // Verificar la estructura de la tabla Detalle_Planilla
        $detalleColumns = $db->query("SHOW COLUMNS FROM Detalle_Planilla")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas disponibles en Detalle_Planilla: " . implode(", ", $detalleColumns) . "</p>";
        
        // Crear consulta SQL dinámica basada en columnas disponibles
        $detalleSqlFields = [];
        $detalleSqlValues = [];
        
        // Asegurar que siempre incluimos estos campos obligatorios
        $detalleSqlFields[] = "id_planilla";
        $detalleSqlValues[] = ":id_planilla";
        
        $detalleSqlFields[] = "id_empleado";
        $detalleSqlValues[] = ":id_empleado";
        
        // Otros campos comunes
        $camposDetalle = [
            'dias_trabajados', 'salario_base', 'bonificacion_incentivo',
            'horas_extra', 'monto_horas_extra', 'comisiones',
            'bonificaciones_adicionales', 'salario_total', 'igss_laboral', 
            'isr_retenido', 'otras_deducciones', 'anticipos', 
            'prestamos', 'descuentos_judiciales', 'total_deducciones', 
            'liquido_recibir'
        ];
        
        foreach ($camposDetalle as $campo) {
            if (in_array($campo, $detalleColumns)) {
                $detalleSqlFields[] = $campo;
                $detalleSqlValues[] = ":" . $campo;
            }
        }
        
        $detalleSql = "INSERT INTO Detalle_Planilla (" . implode(", ", $detalleSqlFields) . ") 
                      VALUES (" . implode(", ", $detalleSqlValues) . ")";
        
        echo "<p>SQL para insertar detalles: $detalleSql</p>";
        
        $insertDetalle = $db->prepare($detalleSql);
        
        foreach ($empleados as $empleado) {
            // Asignar valores a variables
            $diasTrabajados = rand(25, 30);
            $salarioBase = $empleado['salario_base'] ?? 5000; // Valor predeterminado si no existe
            $bonificacion = 250; // Bonificación incentivo fija
            
            $horasExtra = rand(0, 20);
            $valorHora = $salarioBase / 30 / 8 * 1.5; // Valor hora extra (tiempo y medio)
            $montoHorasExtra = round($horasExtra * $valorHora, 2);
            
            $comisiones = rand(0, 1000);
            $bonificacionesAdicionales = rand(0, 500);
            
            $salarioTotal = $salarioBase + $bonificacion + $montoHorasExtra + $comisiones + $bonificacionesAdicionales;
            
            // Deducciones
            $igss = round($salarioBase * 0.0483, 2); // 4.83% del salario base
            $isr = 0; // Simplificado, normalmente se calcula según tabla progresiva
            if ($salarioTotal > 8000) {
                $isr = round(($salarioTotal - 8000) * 0.05, 2);
            }
            
            $otrasDeduciones = rand(0, 200);
            $anticipos = rand(0, 300);
            $prestamos = rand(0, 500);
            $descuentosJudiciales = 0; // Normalmente es 0 o un valor fijo si aplica
            
            $totalDeducciones = $igss + $isr + $otrasDeduciones + $anticipos + $prestamos + $descuentosJudiciales;
            $liquidoRecibir = $salarioTotal - $totalDeducciones;
            
            // Binding de parámetros obligatorios
            $insertDetalle->bindParam(':id_planilla', $idPlanilla);
            $insertDetalle->bindParam(':id_empleado', $empleado['id_empleado']);
            
            // Binding de parámetros opcionales según la estructura de la tabla
            if (in_array('dias_trabajados', $detalleColumns)) {
                $insertDetalle->bindParam(':dias_trabajados', $diasTrabajados);
            }
            
            if (in_array('salario_base', $detalleColumns)) {
                $insertDetalle->bindParam(':salario_base', $salarioBase);
            }
            
            if (in_array('bonificacion_incentivo', $detalleColumns)) {
                $insertDetalle->bindParam(':bonificacion_incentivo', $bonificacion);
            }
            
            if (in_array('horas_extra', $detalleColumns)) {
                $insertDetalle->bindParam(':horas_extra', $horasExtra);
            }
            
            if (in_array('monto_horas_extra', $detalleColumns)) {
                $insertDetalle->bindParam(':monto_horas_extra', $montoHorasExtra);
            }
            
            if (in_array('comisiones', $detalleColumns)) {
                $insertDetalle->bindParam(':comisiones', $comisiones);
            }
            
            if (in_array('bonificaciones_adicionales', $detalleColumns)) {
                $insertDetalle->bindParam(':bonificaciones_adicionales', $bonificacionesAdicionales);
            }
            
            if (in_array('salario_total', $detalleColumns)) {
                $insertDetalle->bindParam(':salario_total', $salarioTotal);
            }
            
            if (in_array('igss_laboral', $detalleColumns)) {
                $insertDetalle->bindParam(':igss_laboral', $igss);
            }
            
            if (in_array('isr_retenido', $detalleColumns)) {
                $insertDetalle->bindParam(':isr_retenido', $isr);
            }
            
            if (in_array('otras_deducciones', $detalleColumns)) {
                $insertDetalle->bindParam(':otras_deducciones', $otrasDeduciones);
            }
            
            if (in_array('anticipos', $detalleColumns)) {
                $insertDetalle->bindParam(':anticipos', $anticipos);
            }
            
            if (in_array('prestamos', $detalleColumns)) {
                $insertDetalle->bindParam(':prestamos', $prestamos);
            }
            
            if (in_array('descuentos_judiciales', $detalleColumns)) {
                $insertDetalle->bindParam(':descuentos_judiciales', $descuentosJudiciales);
            }
            
            if (in_array('total_deducciones', $detalleColumns)) {
                $insertDetalle->bindParam(':total_deducciones', $totalDeducciones);
            }
            
            if (in_array('liquido_recibir', $detalleColumns)) {
                $insertDetalle->bindParam(':liquido_recibir', $liquidoRecibir);
            }
            
            $insertDetalle->execute();
            $detallesCreados++;
        }
        
        echo "<p>Detalles creados para planilla #$idPlanilla: $detallesCreados</p>";
    }
    
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<h2 style='color: #155724;'>Resumen de la operación</h2>";
    echo "<ul>";
    echo "<li>Departamentos creados: $departamentosCreados</li>";
    echo "<li>Puestos creados: $puestosCreados</li>";
    if (isset($empleadosCreados)) echo "<li>Empleados creados: $empleadosCreados</li>";
    if (isset($periodosCreados)) echo "<li>Periodos creados: $periodosCreados</li>";
    echo "<li>Planillas creadas: $planillasCreadas</li>";
    echo "</ul>";
    
    echo "<p style='font-weight: bold;'>Se han creado planillas con IDs: ";
    
    // Mostrar últimas planillas creadas
    $ultimasPlanillas = $db->query("
        SELECT id_planilla, descripcion, fecha_generacion
        FROM Planillas
        ORDER BY id_planilla DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>Descripción</th><th>Fecha</th></tr>";
    
    foreach ($ultimasPlanillas as $planilla) {
        echo "<tr>";
        echo "<td>" . $planilla['id_planilla'] . "</td>";
        echo "<td>" . $planilla['descripcion'] . "</td>";
        echo "<td>" . $planilla['fecha_generacion'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p><a href='index.php?page=planillas/listar' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Ver todas las planillas</a></p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 