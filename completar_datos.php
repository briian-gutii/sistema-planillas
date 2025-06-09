<?php
/**
 * Script para generar datos faltantes en tablas específicas
 * Este script se puede llamar directamente o con el parámetro tabla=nombre_tabla
 */

// Cargar las configuraciones necesarias
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Iniciar salida HTML
echo "<!DOCTYPE html>
<html>
<head>
    <title>Completar Datos - Sistema de Planillas</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css'>
    <style>
        body { padding: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'>Completar Datos Faltantes</h1>";

// Obtener tabla específica si se proporciona
$tabla_especifica = isset($_GET['tabla']) ? $_GET['tabla'] : null;

try {
    $db = getDB();
    echo "<div class='alert alert-success'>Conexión a la base de datos establecida correctamente.</div>";
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Función para crear datos de ejemplo según la tabla
    function generarDatosTabla($db, $tabla) {
        $resultados = [];
        
        switch ($tabla) {
            case 'Usuarios_Sistema':
                if (!hayRegistros($db, $tabla)) {
                    $stmt = $db->prepare("INSERT INTO Usuarios_Sistema (id_usuario, nombre_usuario, contrasena, correo, rol, fecha_creacion, estado) 
                                        VALUES (1, 'admin', :pass, 'admin@sistema.com', 'Admin', NOW(), 'Activo')");
                    $hash = password_hash('123456', PASSWORD_DEFAULT);
                    $stmt->bindParam(':pass', $hash);
                    $stmt->execute();
                    $resultados[] = "Creado usuario administrador por defecto";
                }
                break;
                
            case 'Empleados':
                if (!hayRegistros($db, $tabla)) {
                    $empleados = [
                        [1, '1234567890101', '12345678', '12345678', 'Juan', 'Antonio', 'Pérez', 'García', '1985-05-12', 'Masculino', 'Casado', 'Calle Principal 123', 10, 'Guatemala', 'Guatemala', '55123456', 'juan.perez@mail.com', '2020-01-15', 'Activo'],
                        [2, '2345678901201', '23456789', '23456789', 'Ana', 'María', 'López', 'Rodríguez', '1990-08-15', 'Femenino', 'Soltera', 'Avenida Central 456', 14, 'Guatemala', 'Guatemala', '55234567', 'ana.lopez@mail.com', '2019-03-20', 'Activo']
                    ];
                    
                    $stmt = $db->prepare("INSERT INTO Empleados (id_empleado, DPI, NIT, numero_IGSS, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, fecha_nacimiento, genero, estado_civil, direccion, zona, departamento, municipio, telefono, email, fecha_ingreso, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($empleados as $empleado) {
                        $stmt->execute($empleado);
                    }
                    $resultados[] = "Insertados " . count($empleados) . " empleados de ejemplo";
                }
                break;
                
            case 'Departamentos':
                if (!hayRegistros($db, $tabla)) {
                    $departamentos = [
                        [1, 'Administración', 'Gestión administrativa', null],
                        [2, 'Recursos Humanos', 'Gestión del personal', null]
                    ];
                    
                    $stmt = $db->prepare("INSERT INTO Departamentos (id_departamento, nombre, descripcion, id_responsable) VALUES (?, ?, ?, ?)");
                    foreach ($departamentos as $depto) {
                        $stmt->execute($depto);
                    }
                    $resultados[] = "Insertados " . count($departamentos) . " departamentos de ejemplo";
                }
                break;
                
            case 'Puestos':
                if (!hayRegistros($db, $tabla)) {
                    $puestos = [
                        [1, 'Gerente', 'Puesto gerencial', 10000.00, 1],
                        [2, 'Analista RRHH', 'Analista de recursos humanos', 6000.00, 2]
                    ];
                    
                    $stmt = $db->prepare("INSERT INTO Puestos (id_puesto, nombre, descripcion, salario_base, id_departamento) VALUES (?, ?, ?, ?, ?)");
                    foreach ($puestos as $puesto) {
                        $stmt->execute($puesto);
                    }
                    $resultados[] = "Insertados " . count($puestos) . " puestos de ejemplo";
                }
                break;
                
            case 'Contratos':
                if (!hayRegistros($db, $tabla)) {
                    // Primero verificar si hay empleados y puestos
                    if (!hayRegistros($db, 'Empleados') || !hayRegistros($db, 'Puestos')) {
                        generarDatosTabla($db, 'Empleados');
                        generarDatosTabla($db, 'Puestos');
                    }
                    
                    $contratos = [
                        [1, 1, 'Indefinido', date('Y-m-d', strtotime('-1 year')), NULL, 10000.00, 'Diurna', 40, 250.00, 'Contrato estándar'],
                        [2, 2, 'Indefinido', date('Y-m-d', strtotime('-1 year')), NULL, 6000.00, 'Diurna', 40, 250.00, 'Contrato estándar']
                    ];
                    
                    $stmt = $db->prepare("INSERT INTO Contratos (id_empleado, id_puesto, tipo_contrato, fecha_inicio, fecha_fin, salario, jornada, horas_semanales, bonificacion_incentivo, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($contratos as $contrato) {
                        $stmt->execute($contrato);
                    }
                    $resultados[] = "Insertados " . count($contratos) . " contratos de ejemplo";
                }
                break;
                
            case 'Periodos_Pago':
                if (!hayRegistros($db, $tabla)) {
                    $periodos = [];
                    $año_actual = date('Y');
                    $mes_actual = date('m');
                    
                    // Crear periodo para el mes actual
                    $fecha_inicio = sprintf("%04d-%02d-01", $año_actual, $mes_actual);
                    $ultimo_dia = date('t', strtotime($fecha_inicio));
                    $fecha_fin = sprintf("%04d-%02d-%02d", $año_actual, $mes_actual, $ultimo_dia);
                    $fecha_pago = date('Y-m-d', strtotime($fecha_fin . ' +5 days'));
                    
                    $periodos[] = [NULL, $fecha_inicio, $fecha_fin, $fecha_pago, 'Mensual', 'Abierto', $año_actual, $mes_actual];
                    
                    $stmt = $db->prepare("INSERT INTO Periodos_Pago (id_periodo, fecha_inicio, fecha_fin, fecha_pago, tipo, estado, anio, mes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($periodos as $periodo) {
                        $stmt->execute($periodo);
                    }
                    $resultados[] = "Insertado periodo de pago para el mes actual";
                }
                break;
                
            case 'Horas_Extra':
                if (!hayRegistros($db, $tabla) && hayRegistros($db, 'Empleados')) {
                    $horas_extra = [
                        [1, date('Y-m-d', strtotime('-7 days')), 2, 'Trabajo extra para cierre', 1, 'Aprobado', NULL],
                        [2, date('Y-m-d', strtotime('-5 days')), 3, 'Proyecto urgente', 1, 'Aprobado', NULL]
                    ];
                    
                    $stmt = $db->prepare("INSERT INTO Horas_Extra (id_empleado, fecha, horas, descripcion, aprobado_por, estado, id_planilla) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    foreach ($horas_extra as $extra) {
                        $stmt->execute($extra);
                    }
                    $resultados[] = "Insertados " . count($horas_extra) . " registros de horas extra";
                }
                break;
                
            default:
                $resultados[] = "No hay generador de datos para la tabla: $tabla";
                break;
        }
        
        return $resultados;
    }
    
    // Función para verificar si una tabla tiene registros
    function hayRegistros($db, $tabla) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM $tabla");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['total'] > 0);
    }
    
    $resultados = [];
    
    // Si se especificó una tabla, generar datos solo para esa tabla
    if ($tabla_especifica) {
        $resultados = generarDatosTabla($db, $tabla_especifica);
        echo "<div class='alert alert-info'>Generando datos para tabla: $tabla_especifica</div>";
    } else {
        // Generar datos para todas las tablas que los necesiten
        $tablasNecesarias = [
            'Usuarios_Sistema', 'Empleados', 'Departamentos', 'Puestos', 
            'Contratos', 'Periodos_Pago', 'Horas_Extra'
        ];
        
        foreach ($tablasNecesarias as $tabla) {
            $resultados = array_merge($resultados, generarDatosTabla($db, $tabla));
        }
        echo "<div class='alert alert-info'>Generando datos para todas las tablas necesarias</div>";
    }
    
    // Confirmar transacción
    $db->commit();
    
    // Mostrar resultados
    echo "<h2 class='mt-4 mb-3'>Resultados</h2>";
    echo "<ul class='list-group'>";
    foreach ($resultados as $resultado) {
        echo "<li class='list-group-item'>$resultado</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}

echo "    <div class='mt-4'>
        <a href='verificar_datos.php' class='btn btn-primary'>Verificar Datos</a>
        <a href='index.php' class='btn btn-secondary ms-2'>Volver al sistema</a>
    </div>
</div>
</body>
</html>";
?> 