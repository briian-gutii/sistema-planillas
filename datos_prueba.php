<?php
/**
 * Script para generar datos de prueba para el sistema de planillas
 * Este script inserta datos de prueba en todas las tablas principales del sistema
 */

// Cargar las configuraciones necesarias
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Generando datos de prueba para el sistema de planillas</h1>";

// Conectar a la base de datos
try {
    $db = getDB();
    echo "<p>Conexión a la base de datos establecida correctamente</p>";
    
    // Iniciar transacción para poder revertir en caso de error
    $db->beginTransaction();

    // Eliminar datos existentes respetando las restricciones de clave foránea
    echo "<h2>Limpiando datos existentes...</h2>";
    
    // Primero eliminar tablas que dependen de otras
    $db->exec("DELETE FROM Horas_Extra");
    $db->exec("DELETE FROM Contratos");
    $db->exec("DELETE FROM Puestos");
    $db->exec("DELETE FROM Departamentos");
    $db->exec("DELETE FROM Empleados");
    $db->exec("DELETE FROM Periodos_Pago");
    $db->exec("DELETE FROM Usuarios_Sistema WHERE id_usuario > 1");
    
    echo "<p>✓ Datos anteriores eliminados correctamente</p>";

    // 1. INSERTAR DATOS DE USUARIOS
    echo "<h2>Insertando usuarios...</h2>";
    
    $usuarios = [
        // id_usuario, nombre_usuario, contrasena, correo, rol, fecha_creacion, estado
        [2, 'contabilidad', password_hash('123456', PASSWORD_DEFAULT), 'contabilidad@empresa.com', 'Contabilidad', date('Y-m-d H:i:s'), 'Activo'],
        [3, 'rrhh', password_hash('123456', PASSWORD_DEFAULT), 'rrhh@empresa.com', 'RRHH', date('Y-m-d H:i:s'), 'Activo'],
        [4, 'gerente', password_hash('123456', PASSWORD_DEFAULT), 'gerente@empresa.com', 'Gerencia', date('Y-m-d H:i:s'), 'Activo'],
        [5, 'supervisor', password_hash('123456', PASSWORD_DEFAULT), 'supervisor@empresa.com', 'Consulta', date('Y-m-d H:i:s'), 'Activo']
    ];
    
    $stmt = $db->prepare("INSERT INTO Usuarios_Sistema (id_usuario, nombre_usuario, contrasena, correo, rol, fecha_creacion, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($usuarios as $usuario) {
        $stmt->execute($usuario);
    }
    echo "<p>✓ Insertados " . count($usuarios) . " usuarios</p>";
    
    // 2. INSERTAR EMPLEADOS
    echo "<h2>Insertando empleados...</h2>";
    
    // Crear array de empleados con datos realistas
    $empleados = [
        // id, DPI, NIT, numero_IGSS, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, fecha_nacimiento, genero, estado_civil, direccion, zona, departamento, municipio, telefono, email, fecha_ingreso, estado
        [1, '1234567890101', '12345678', '12345678', 'Juan', 'Antonio', 'Pérez', 'García', '1985-05-12', 'Masculino', 'Casado', 'Calle Principal 123', 10, 'Guatemala', 'Guatemala', '55123456', 'juan.perez@mail.com', '2020-01-15', 'Activo'],
        [2, '2345678901201', '23456789', '23456789', 'Ana', 'María', 'López', 'Rodríguez', '1990-08-15', 'Femenino', 'Soltera', 'Avenida Central 456', 14, 'Guatemala', 'Guatemala', '55234567', 'ana.lopez@mail.com', '2019-03-20', 'Activo'],
        [3, '3456789012301', '34567890', '34567890', 'Mario', 'José', 'González', 'Juárez', '1988-03-20', 'Masculino', 'Casado', 'Boulevard Los Próceres 789', 9, 'Guatemala', 'Guatemala', '55345678', 'mario.gonzalez@mail.com', '2018-05-10', 'Activo'],
        [4, '4567890123401', '45678901', '45678901', 'Laura', 'Isabel', 'Martínez', 'Solórzano', '1992-11-05', 'Femenino', 'Soltera', 'Calzada Roosevelt 101', 15, 'Guatemala', 'Guatemala', '55456789', 'laura.martinez@mail.com', '2021-02-01', 'Activo'],
        [5, '5678901234501', '56789012', '56789012', 'Carlos', 'Eduardo', 'Hernández', 'López', '1983-07-18', 'Masculino', 'Casado', 'Zona 7 Colonia Tikal 1', 7, 'Guatemala', 'Guatemala', '55567890', 'carlos.hernandez@mail.com', '2017-11-15', 'Activo']
    ];
    
    $stmt = $db->prepare("INSERT INTO Empleados (id_empleado, DPI, NIT, numero_IGSS, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, fecha_nacimiento, genero, estado_civil, direccion, zona, departamento, municipio, telefono, email, fecha_ingreso, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($empleados as $empleado) {
        $stmt->execute($empleado);
    }
    echo "<p>✓ Insertados " . count($empleados) . " empleados</p>";
    
    // 3. INSERTAR DEPARTAMENTOS
    echo "<h2>Insertando departamentos...</h2>";
    
    $departamentos = [
        [1, 'Administración', 'Gestión administrativa de la empresa', null],
        [2, 'Recursos Humanos', 'Gestión del personal y talento humano', null],
        [3, 'Contabilidad y Finanzas', 'Control financiero y contable', null],
        [4, 'Tecnología', 'Gestión tecnológica y desarrollo', null],
        [5, 'Ventas', 'Gestión comercial y ventas', null],
        [6, 'Marketing', 'Publicidad y mercadeo', null],
        [7, 'Producción', 'Manufactura y producción', null],
        [8, 'Logística', 'Gestión de almacenes y distribución', null]
    ];
    
    $stmt = $db->prepare("INSERT INTO Departamentos (id_departamento, nombre, descripcion, id_responsable) VALUES (?, ?, ?, ?)");
    foreach ($departamentos as $depto) {
        $stmt->execute($depto);
    }
    echo "<p>✓ Insertados " . count($departamentos) . " departamentos</p>";
    
    // 4. INSERTAR PUESTOS
    echo "<h2>Insertando puestos...</h2>";
    
    $puestos = [
        [1, 'Gerente General', 'Dirección general de la empresa', 15000.00, 1],
        [2, 'Gerente de Departamento', 'Dirección de departamento', 12000.00, null],
        [3, 'Supervisor', 'Supervisión de equipos de trabajo', 8000.00, null],
        [4, 'Analista', 'Análisis de procesos y datos', 6000.00, null],
        [5, 'Asistente', 'Apoyo administrativo', 4000.00, null],
        [6, 'Contador', 'Gestión contable', 6000.00, 3],
        [7, 'Desarrollador', 'Desarrollo de software', 6000.00, 4],
        [8, 'Diseñador', 'Diseño gráfico y web', 6000.00, 6],
        [9, 'Vendedor', 'Gestión de ventas y clientes', 4000.00, 5],
        [10, 'Recepcionista', 'Atención al público', 4000.00, 1],
        [11, 'Técnico', 'Soporte técnico', 4000.00, 4],
        [12, 'Operario', 'Operación de maquinaria', 3500.00, 7]
    ];
    
    $stmt = $db->prepare("INSERT INTO Puestos (id_puesto, nombre, descripcion, salario_base, id_departamento) VALUES (?, ?, ?, ?, ?)");
    foreach ($puestos as $puesto) {
        $stmt->execute($puesto);
    }
    echo "<p>✓ Insertados " . count($puestos) . " puestos</p>";
    
    // 5. INSERTAR CONTRATOS
    echo "<h2>Insertando contratos...</h2>";
    
    $fecha_actual = date('Y-m-d');
    
    $contratos = [
        // id_empleado, id_puesto, tipo_contrato, fecha_inicio, fecha_fin, salario, jornada, horas_semanales, bonificacion_incentivo, observaciones
        [1, 1, 'Indefinido', '2020-01-15', NULL, 15000.00, 'Diurna', 40, 250.00, 'Contrato de gerencia'],
        [2, 2, 'Indefinido', '2019-03-20', NULL, 12000.00, 'Diurna', 40, 250.00, 'Contrato de departamento'],
        [3, 6, 'Indefinido', '2018-05-10', NULL, 6000.00, 'Diurna', 40, 250.00, 'Contrato área contable'],
        [4, 7, 'Indefinido', '2021-02-01', NULL, 6000.00, 'Diurna', 40, 250.00, 'Contrato área tecnología'],
        [5, 9, 'Indefinido', '2017-11-15', NULL, 4000.00, 'Diurna', 40, 250.00, 'Contrato área ventas']
    ];
    
    $stmt = $db->prepare("INSERT INTO Contratos (id_empleado, id_puesto, tipo_contrato, fecha_inicio, fecha_fin, salario, jornada, horas_semanales, bonificacion_incentivo, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($contratos as $contrato) {
        $stmt->execute($contrato);
    }
    echo "<p>✓ Insertados " . count($contratos) . " contratos</p>";
    
    // 6. INSERTAR PERIODOS DE PAGO
    echo "<h2>Insertando periodos de pago...</h2>";
    
    $año_actual = date('Y');
    $mes_actual = date('m');
    
    $periodos = [];
    // Generar periodos mensuales para el año actual y el anterior
    for ($año = $año_actual - 1; $año <= $año_actual; $año++) {
        $mes_limite = ($año == $año_actual) ? intval($mes_actual) : 12;
        
        for ($mes = 1; $mes <= $mes_limite; $mes++) {
            $nombre = sprintf("Periodo %s %04d", nombreMes($mes), $año);
            $fecha_inicio = sprintf("%04d-%02d-01", $año, $mes);
            $ultimo_dia = date('t', strtotime($fecha_inicio));
            $fecha_fin = sprintf("%04d-%02d-%02d", $año, $mes, $ultimo_dia);
            $fecha_pago = date('Y-m-d', strtotime($fecha_fin . ' +5 days'));
            
            $periodos[] = [
                NULL, // id_periodo (auto-increment)
                $fecha_inicio,
                $fecha_fin,
                $fecha_pago,
                'Mensual',
                ($año == $año_actual && $mes == $mes_actual) ? 'Abierto' : 'Cerrado',
                $año,
                $mes
            ];
        }
    }
    
    $stmt = $db->prepare("INSERT INTO Periodos_Pago (id_periodo, fecha_inicio, fecha_fin, fecha_pago, tipo, estado, anio, mes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($periodos as $periodo) {
        $stmt->execute($periodo);
    }
    echo "<p>✓ Insertados " . count($periodos) . " periodos de pago</p>";
    
    // 7. INSERTAR HORAS EXTRA
    echo "<h2>Insertando horas extra...</h2>";
    
    $horas_extra = [
        // id_empleado, fecha, horas, descripcion, aprobado_por, estado, id_planilla
        [3, date('Y-m-d', strtotime('-20 days')), 2.5, 'Cierre contable mensual', 1, 'Aprobado', NULL],
        [4, date('Y-m-d', strtotime('-15 days')), 3.0, 'Implementación de sistema', 1, 'Aprobado', NULL],
        [5, date('Y-m-d', strtotime('-10 days')), 2.0, 'Atención a cliente importante', 2, 'Aprobado', NULL],
        [3, date('Y-m-d', strtotime('-5 days')), 1.5, 'Preparación de informes', NULL, 'Pendiente', NULL],
        [4, date('Y-m-d', strtotime('-3 days')), 2.0, 'Resolución de incidencias', NULL, 'Pendiente', NULL]
    ];
    
    $stmt = $db->prepare("INSERT INTO Horas_Extra (id_empleado, fecha, horas, descripcion, aprobado_por, estado, id_planilla) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($horas_extra as $extra) {
        $stmt->execute($extra);
    }
    echo "<p>✓ Insertados " . count($horas_extra) . " registros de horas extra</p>";
    
    // Confirmar la transacción
    $db->commit();
    
    echo "<h2 style='color:green'>✓ Datos de prueba generados correctamente</h2>";
    echo "<p>Se han insertado datos de prueba en las tablas principales del sistema.</p>";
    echo "<p><a href='index.php' class='btn btn-primary'>Ir al sistema</a></p>";
    
} catch (PDOException $e) {
    // Revertir la transacción en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "<h2 style='color:red'>Error al generar datos de prueba:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><a href='setup.php'>Volver a configuración</a></p>";
}

// Función para obtener el nombre del mes
function nombreMes($numero_mes) {
    $meses = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];
    
    return $meses[$numero_mes] ?? 'Desconocido';
}
?> 