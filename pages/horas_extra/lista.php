<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_CONTABILIDAD, ROL_RRHH])) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Horas Extra';
$activeMenu = 'horas_extra';

// Parámetros de filtro
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');
$id_departamento = isset($_GET['id_departamento']) ? intval($_GET['id_departamento']) : 0;
$id_empleado = isset($_GET['id_empleado']) ? intval($_GET['id_empleado']) : 0;
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

$db = getDB();
$horas_extra = [];
$departamentos = [];
$empleados = [];

try {
    // Obtener departamentos para el filtro
    $queryDepartamentos = "SELECT id_departamento, nombre FROM departamentos ORDER BY nombre";
    $stmtDepartamentos = $db->prepare($queryDepartamentos);
    $stmtDepartamentos->execute();
    $departamentos = $stmtDepartamentos->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener empleados para el filtro
    $queryEmpleados = "SELECT id_empleado, CONCAT(apellidos, ', ', nombres) as nombre_completo, 
                     codigo_empleado
                     FROM empleados 
                     WHERE estado = 'Activo'
                     ORDER BY apellidos, nombres";
    $stmtEmpleados = $db->prepare($queryEmpleados);
    $stmtEmpleados->execute();
    $empleados = $stmtEmpleados->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta base para horas extra
    $queryBase = "SELECT he.*, 
                 e.nombres, e.apellidos, e.codigo_empleado,
                 d.nombre as departamento
                 FROM horas_extra he
                 JOIN empleados e ON he.id_empleado = e.id_empleado
                 LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                 WHERE he.fecha BETWEEN :fecha_inicio AND :fecha_fin";
    
    $params = [
        ':fecha_inicio' => $fecha_inicio,
        ':fecha_fin' => $fecha_fin
    ];
    
    // Agregar filtros adicionales
    if ($id_departamento > 0) {
        $queryBase .= " AND e.id_departamento = :id_departamento";
        $params[':id_departamento'] = $id_departamento;
    }
    
    if ($id_empleado > 0) {
        $queryBase .= " AND he.id_empleado = :id_empleado";
        $params[':id_empleado'] = $id_empleado;
    }
    
    if (!empty($estado)) {
        $queryBase .= " AND he.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    $queryBase .= " ORDER BY he.fecha DESC, e.apellidos, e.nombres";
    
    $stmt = $db->prepare($queryBase);
    
    // Asignar parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $horas_extra = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    setFlashMessage('Error al cargar los datos: ' . $e->getMessage(), 'danger');
}

// Estados de horas extra para el filtro
$estados = [
    'Pendiente' => 'Pendiente',
    'Aprobado' => 'Aprobado',
    'Rechazado' => 'Rechazado'
];
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-clock fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Administre las horas extra de los empleados</p>
    
    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
        </div>
        <div class="card-body">
            <form method="get" id="formFiltro">
                <input type="hidden" name="page" value="horas_extra/lista">
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                               value="<?php echo $fecha_inicio; ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                               value="<?php echo $fecha_fin; ?>">
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
                                <option value="<?php echo $key; ?>" <?php echo $estado === $key ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-9 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter fa-fw"></i> Filtrar
                        </button>
                        <a href="<?php echo BASE_URL; ?>?page=horas_extra/lista" class="btn btn-secondary me-2">
                            <i class="fas fa-sync-alt fa-fw"></i> Limpiar Filtros
                        </a>
                        <a href="<?php echo BASE_URL; ?>?page=horas_extra/nuevo" class="btn btn-success">
                            <i class="fas fa-plus fa-fw"></i> Registrar Horas Extra
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de Horas Extra -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Registros de Horas Extra</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="tablaHorasExtra" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empleado</th>
                            <th>Departamento</th>
                            <th>Fecha</th>
                            <th>Horas</th>
                            <th>Valor Hora</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($horas_extra) > 0): ?>
                            <?php foreach ($horas_extra as $hora): ?>
                                <tr>
                                    <td><?php echo $hora['id_hora_extra']; ?></td>
                                    <td>
                                        <?php echo $hora['apellidos'] . ', ' . $hora['nombres']; ?><br>
                                        <small><?php echo $hora['codigo_empleado']; ?></small>
                                    </td>
                                    <td><?php echo $hora['departamento']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($hora['fecha'])); ?></td>
                                    <td class="text-center"><?php echo $hora['horas']; ?></td>
                                    <td class="text-end"><?php echo formatMoney($hora['valor_hora']); ?></td>
                                    <td class="text-end"><?php echo formatMoney($hora['horas'] * $hora['valor_hora']); ?></td>
                                    <td>
                                        <?php 
                                        $badgeClass = 'secondary';
                                        
                                        switch ($hora['estado']) {
                                            case 'Pendiente':
                                                $badgeClass = 'warning';
                                                break;
                                            case 'Aprobado':
                                                $badgeClass = 'success';
                                                break;
                                            case 'Rechazado':
                                                $badgeClass = 'danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $hora['estado']; ?></span>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>?page=horas_extra/ver&id=<?php echo $hora['id_hora_extra']; ?>" 
                                           class="btn btn-info btn-sm" title="Ver Detalles">
                                            <i class="fas fa-eye fa-fw"></i>
                                        </a>
                                        
                                        <?php if ($hora['estado'] == 'Pendiente'): ?>
                                            <a href="<?php echo BASE_URL; ?>?page=horas_extra/editar&id=<?php echo $hora['id_hora_extra']; ?>" 
                                               class="btn btn-primary btn-sm" title="Editar">
                                                <i class="fas fa-edit fa-fw"></i>
                                            </a>
                                            
                                            <a href="<?php echo BASE_URL; ?>?page=horas_extra/aprobar&id=<?php echo $hora['id_hora_extra']; ?>" 
                                               class="btn btn-success btn-sm" title="Aprobar/Rechazar">
                                                <i class="fas fa-check-circle fa-fw"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No se encontraron registros de horas extra</td>
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
    $('#tablaHorasExtra').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        },
        order: [[3, 'desc']], // Ordenar por fecha descendente
        responsive: true
    });
    
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