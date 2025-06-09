<?php
/**
 * Script para verificar y diagnosticar problemas de datos en las tablas
 * Este script verifica si existen datos en todas las tablas necesarias para generar planillas
 */

// Cargar las configuraciones necesarias
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Iniciar salida HTML
echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Datos - Sistema de Planillas</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css'>
    <style>
        body { padding: 20px; }
        .table-count { width: 100%; margin-bottom: 20px; }
        .status-ok { color: green; }
        .status-warning { color: orange; }
        .status-error { color: red; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'>Diagnóstico de Datos - Sistema de Planillas</h1>";

try {
    $db = getDB();
    echo "<div class='alert alert-success'>Conexión a la base de datos establecida correctamente.</div>";
    
    // Array de tablas a verificar
    $tablas = [
        'Empleados' => ['Debe tener datos para generar planillas', 1],
        'Departamentos' => ['Requerido para categorizar empleados', 1],
        'Puestos' => ['Requerido para asignar salarios', 1],
        'Contratos' => ['Requerido para obtener información salarial', 1],
        'Periodos_Pago' => ['Requerido para definir períodos de pago', 1],
        'Planillas' => ['Tabla donde se guardan las planillas generadas', 0],
        'Detalle_Planilla' => ['Contiene detalles de cada planilla', 0],
        'Horas_Extra' => ['Opcional, para calcular horas extras', 0],
        'Usuarios_Sistema' => ['Requerido para autenticación', 1]
    ];
    
    echo "<h2 class='mt-4 mb-3'>Conteo de registros por tabla</h2>";
    echo "<table class='table table-striped table-count'>";
    echo "<thead><tr><th>Tabla</th><th>Descripción</th><th>Registros</th><th>Estado</th><th>Acción</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($tablas as $tabla => $info) {
        $descripcion = $info[0];
        $requerido = $info[1];
        
        try {
            $query = "SELECT COUNT(*) as total FROM $tabla";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['total'];
            
            // Determinar estado
            if ($requerido && $count == 0) {
                $estado = "<span class='status-error'>ERROR: Tabla requerida sin datos</span>";
                $accion = "<a href='completar_datos.php?tabla=$tabla' class='btn btn-sm btn-danger'>Generar Datos</a>";
            } elseif ($count == 0) {
                $estado = "<span class='status-warning'>OK, pero sin datos</span>";
                $accion = "<a href='completar_datos.php?tabla=$tabla' class='btn btn-sm btn-warning'>Generar Datos</a>";
            } else {
                $estado = "<span class='status-ok'>OK</span>";
                $accion = "";
            }
            
            echo "<tr>
                <td>$tabla</td>
                <td>$descripcion</td>
                <td>$count</td>
                <td>$estado</td>
                <td>$accion</td>
            </tr>";
            
        } catch (PDOException $e) {
            echo "<tr>
                <td>$tabla</td>
                <td>$descripcion</td>
                <td colspan='2'><span class='status-error'>ERROR: " . $e->getMessage() . "</span></td>
                <td></td>
            </tr>";
        }
    }
    
    echo "</tbody></table>";
    
    // Verificar configuración de periodo de pago activo
    echo "<h2 class='mt-4 mb-3'>Periodos de Pago Disponibles</h2>";
    try {
        $query = "SELECT id_periodo, fecha_inicio, fecha_fin, estado, tipo FROM Periodos_Pago ORDER BY fecha_inicio DESC LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($periodos) > 0) {
            echo "<table class='table table-striped'>";
            echo "<thead><tr><th>ID</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Tipo</th><th>Estado</th></tr></thead>";
            echo "<tbody>";
            
            foreach ($periodos as $periodo) {
                echo "<tr>
                    <td>{$periodo['id_periodo']}</td>
                    <td>{$periodo['fecha_inicio']}</td>
                    <td>{$periodo['fecha_fin']}</td>
                    <td>{$periodo['tipo']}</td>
                    <td>{$periodo['estado']}</td>
                </tr>";
            }
            
            echo "</tbody></table>";
        } else {
            echo "<div class='alert alert-warning'>No hay periodos de pago disponibles. Necesita crear al menos un periodo para generar planillas.</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error al consultar periodos: " . $e->getMessage() . "</div>";
    }
    
    // Verificar contratos activos asociados a empleados
    echo "<h2 class='mt-4 mb-3'>Contratos Activos</h2>";
    try {
        $query = "SELECT c.*, e.primer_nombre, e.primer_apellido, p.nombre as puesto 
                 FROM Contratos c 
                 JOIN Empleados e ON c.id_empleado = e.id_empleado 
                 JOIN Puestos p ON c.id_puesto = p.id_puesto 
                 WHERE c.fecha_fin IS NULL
                 ORDER BY e.primer_apellido, e.primer_nombre";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($contratos) > 0) {
            echo "<table class='table table-striped'>";
            echo "<thead><tr><th>ID</th><th>Empleado</th><th>Puesto</th><th>Salario</th><th>Bonificación</th></tr></thead>";
            echo "<tbody>";
            
            foreach ($contratos as $contrato) {
                echo "<tr>
                    <td>{$contrato['id_contrato']}</td>
                    <td>{$contrato['primer_nombre']} {$contrato['primer_apellido']}</td>
                    <td>{$contrato['puesto']}</td>
                    <td>Q " . number_format($contrato['salario'], 2) . "</td>
                    <td>Q " . number_format($contrato['bonificacion_incentivo'], 2) . "</td>
                </tr>";
            }
            
            echo "</tbody></table>";
        } else {
            echo "<div class='alert alert-danger'>No hay contratos activos. Se requieren contratos activos para generar planillas.</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error al consultar contratos: " . $e->getMessage() . "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error de conexión a la base de datos: " . $e->getMessage() . "</div>";
}

echo "    <div class='mt-4'>
        <a href='index.php' class='btn btn-primary'>Volver al sistema</a>
        <a href='completar_datos.php' class='btn btn-success ms-2'>Generar Todos los Datos Faltantes</a>
        <a href='datos_prueba.php' class='btn btn-warning ms-2'>Generar Todos los Datos de Prueba</a>
    </div>
</div>
</body>
</html>";
?> 