<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_RRHH])) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Reporte de Vacaciones';
$activeMenu = 'vacaciones';

// Obtener parámetros de filtro
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : 0;
$id_departamento = isset($_GET['id_departamento']) ? intval($_GET['id_departamento']) : 0;
$id_empleado = isset($_GET['id_empleado']) ? intval($_GET['id_empleado']) : 0;
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Preparar variables para la consulta
$db = getDB();
$vacaciones = [];
$departamentos = [];
$empleados = [];
$totales = [
    'pendientes' => 0,
    'aprobadas' => 0,
    'rechazadas' => 0,
    'finalizadas' => 0,
    'total_dias' => 0
];

try {
    // Obtener lista de departamentos para el filtro
    $queryDepartamentos = "SELECT id_departamento, nombre FROM departamentos ORDER BY nombre";
    $stmtDepartamentos = $db->prepare($queryDepartamentos);
    $stmtDepartamentos->execute();
    $departamentos = $stmtDepartamentos->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener lista de empleados para el filtro
    $queryEmpleados = "SELECT id_empleado, CONCAT(apellidos, ', ', nombres) as nombre_completo, 
                     codigo_empleado
                     FROM empleados 
                     WHERE estado = 'Activo'
                     ORDER BY apellidos, nombres";
    $stmtEmpleados = $db->prepare($queryEmpleados);
    $stmtEmpleados->execute();
    $empleados = $stmtEmpleados->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir consulta base para las vacaciones
    $queryBase = "SELECT v.*, 
                 e.nombres, e.apellidos, e.codigo_empleado,
                 d.nombre as departamento
                 FROM vacaciones v
                 JOIN empleados e ON v.id_empleado = e.id_empleado
                 LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                 WHERE 1=1";
    
    $params = [];
    
    // Agregar filtros a la consulta
    if ($anio > 0) {
        $queryBase .= " AND (YEAR(v.fecha_inicio) = :anio OR YEAR(v.fecha_fin) = :anio)";
        $params[':anio'] = $anio;
    }
    
    if ($mes > 0) {
        $queryBase .= " AND (MONTH(v.fecha_inicio) = :mes OR MONTH(v.fecha_fin) = :mes)";
        $params[':mes'] = $mes;
    }
    
    if ($id_departamento > 0) {
        $queryBase .= " AND e.id_departamento = :id_departamento";
        $params[':id_departamento'] = $id_departamento;
    }
    
    if ($id_empleado > 0) {
        $queryBase .= " AND v.id_empleado = :id_empleado";
        $params[':id_empleado'] = $id_empleado;
    }
    
    if (!empty($estado)) {
        $queryBase .= " AND v.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    $queryBase .= " ORDER BY v.fecha_solicitud DESC, e.apellidos, e.nombres";
    
    $stmt = $db->prepare($queryBase);
    
    // Asignar parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $vacaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular totales
    foreach ($vacaciones as $vacacion) {
        $totales['total_dias'] += $vacacion['dias_solicitados'];
        
        switch ($vacacion['estado']) {
            case 'Pendiente':
                $totales['pendientes']++;
                break;
            case 'Aprobada':
                $totales['aprobadas']++;
                break;
            case 'Rechazada':
                $totales['rechazadas']++;
                break;
            case 'Finalizada':
                $totales['finalizadas']++;
                break;
        }
    }
    
} catch (Exception $e) {
    setFlashMessage('Error al cargar los datos: ' . $e->getMessage(), 'danger');
}

// Obtener los meses para el filtro
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

// Estados de vacaciones para el filtro
$estados = [
    'Pendiente' => 'Pendiente',
    'Aprobada' => 'Aprobada',
    'Rechazada' => 'Rechazada',
    'Finalizada' => 'Finalizada'
];
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-umbrella-beach fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Consulte y genere reportes sobre las solicitudes de vacaciones</p>
    
    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
        </div>
        <div class="card-body">
            <form method="get" id="formFiltro">
                <input type="hidden" name="page" value="vacaciones/reporte">
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="anio" class="form-label">Año</label>
                        <select class="form-select" id="anio" name="anio">
                            <option value="0">Todos</option>
                            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $anio == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="mes" class="form-label">Mes</label>
                        <select class="form-select" id="mes" name="mes">
                            <option value="0">Todos</option>
                            <?php foreach ($meses as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $mes == $key ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="id_departamento" class="form-label">Departamento</label>
                        <select class="form-select" id="id_departamento" name="id_departamento">
                            <option value="0">Todos</option>
                            <?php foreach ($departamentos as $departamento): ?>
                                <option value="<?php echo $departamento['id_departamento']; ?>" 
                                        <?php echo $id_departamento == $departamento['id_departamento'] ? 'selected' : ''; ?>>
                                    <?php echo $departamento['nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="id_empleado" class="form-label">Empleado</label>
                        <select class="form-select" id="id_empleado" name="id_empleado">
                            <option value="0">Todos</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?php echo $empleado['id_empleado']; ?>" 
                                        <?php echo $id_empleado == $empleado['id_empleado'] ? 'selected' : ''; ?>>
                                    <?php echo $empleado['nombre_completo'] . ' (' . $empleado['codigo_empleado'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="">Todos</option>
                            <?php foreach ($estados as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $estado == $key ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-9 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter fa-fw"></i> Filtrar
                        </button>
                        <a href="<?php echo BASE_URL; ?>?page=vacaciones/reporte" class="btn btn-secondary me-2">
                            <i class="fas fa-sync-alt fa-fw"></i> Limpiar Filtros
                        </a>
                        <button type="button" class="btn btn-info me-2" onclick="window.print()">
                            <i class="fas fa-print fa-fw"></i> Imprimir
                        </button>
                        <button type="button" class="btn btn-success me-2" id="btnExportarExcel">
                            <i class="fas fa-file-excel fa-fw"></i> Exportar a Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resumen -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Solicitudes Totales
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo count($vacaciones); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pendientes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $totales['pendientes']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Aprobadas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $totales['aprobadas'] + $totales['finalizadas']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Días
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $totales['total_dias']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla de Vacaciones -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Solicitudes de Vacaciones</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="tablaVacaciones" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Departamento</th>
                            <th>Período</th>
                            <th>Días</th>
                            <th>Solicitud</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($vacaciones) > 0): ?>
                            <?php foreach ($vacaciones as $vacacion): ?>
                                <tr>
                                    <td>
                                        <?php echo $vacacion['apellidos'] . ', ' . $vacacion['nombres']; ?><br>
                                        <small><?php echo $vacacion['codigo_empleado']; ?></small>
                                    </td>
                                    <td><?php echo $vacacion['departamento']; ?></td>
                                    <td>
                                        Del <?php echo date('d/m/Y', strtotime($vacacion['fecha_inicio'])); ?><br>
                                        Al <?php echo date('d/m/Y', strtotime($vacacion['fecha_fin'])); ?>
                                    </td>
                                    <td class="text-center"><?php echo $vacacion['dias_solicitados']; ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($vacacion['fecha_solicitud'])); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $badgeClass = 'secondary';
                                        
                                        switch ($vacacion['estado']) {
                                            case 'Pendiente':
                                                $badgeClass = 'warning';
                                                break;
                                            case 'Aprobada':
                                                $badgeClass = 'success';
                                                break;
                                            case 'Rechazada':
                                                $badgeClass = 'danger';
                                                break;
                                            case 'Finalizada':
                                                $badgeClass = 'primary';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $vacacion['estado']; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No se encontraron solicitudes de vacaciones</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables
    $('#tablaVacaciones').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        },
        order: [[4, 'desc']], // Ordenar por fecha de solicitud descendente
        responsive: true
    });
    
    // Exportar a Excel (simulado)
    const btnExportarExcel = document.getElementById('btnExportarExcel');
    if (btnExportarExcel) {
        btnExportarExcel.addEventListener('click', function() {
            alert('Funcionalidad de exportación a Excel se implementará próximamente');
        });
    }
    
    // Actualizar empleados según departamento seleccionado
    const departamentoSelect = document.getElementById('id_departamento');
    const empleadoSelect = document.getElementById('id_empleado');
    
    if (departamentoSelect && empleadoSelect) {
        departamentoSelect.addEventListener('change', function() {
            // Esta funcionalidad normalmente se implementaría con una llamada AJAX
            // para filtrar empleados por departamento
        });
    }
});
</script>

<style>
@media print {
    .btn, .dataTables_filter, .dataTables_length, .dataTables_paginate, .dataTables_info {
        display: none !important;
    }
    
    .card {
        border: none !important;
    }
    
    .card-header {
        background-color: #fff !important;
    }
    
    body {
        font-size: 12px;
    }
    
    .container-fluid {
        padding: 0;
    }
    
    .table {
        width: 100% !important;
    }
}
</style> 