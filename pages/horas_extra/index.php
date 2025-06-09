<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_CONTABILIDAD, ROL_RRHH])) {
    setFlashMessage('danger', ERROR_ACCESS);
    header('Location: index.php');
    exit;
}

// Procesar eliminación de hora extra
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    $id_hora_extra = intval($_GET['id']);
    
    try {
        // Verificar si la hora extra está asociada a una planilla procesada
        $sqlVerificar = "SELECT h.id_hora_extra 
                         FROM horas_extra h
                         LEFT JOIN planillas p ON h.id_periodo = p.id_periodo
                         WHERE h.id_hora_extra = :id_hora_extra AND p.estado = 'Procesada'";
        $horaExtraProcesada = fetchRow($sqlVerificar, [':id_hora_extra' => $id_hora_extra]);
        
        if ($horaExtraProcesada) {
            setFlashMessage('warning', 'No se puede eliminar una hora extra asociada a una planilla procesada.');
        } else {
            // Eliminar la hora extra
            $sql = "DELETE FROM horas_extra WHERE id_hora_extra = :id_hora_extra";
            query($sql, [':id_hora_extra' => $id_hora_extra]);
            
            setFlashMessage('success', 'Hora extra eliminada correctamente.');
            
            // Registrar en bitácora
            registrarBitacora('Eliminación de hora extra', 'horas_extra', $id_hora_extra, 'Eliminación de registro');
        }
    } catch (Exception $e) {
        setFlashMessage('danger', 'Error al eliminar la hora extra: ' . $e->getMessage());
    }
    
    header('Location: index.php?page=horas_extra/index');
    exit;
}

// Obtener periodos de nómina activos$sql = "SELECT id_periodo, CONCAT(DATE_FORMAT(fecha_inicio, '%d/%m/%Y'), ' - ',         DATE_FORMAT(fecha_fin, '%d/%m/%Y'), ' (', nombre, ')') as periodo_texto         FROM periodos         WHERE estado = 'Activo'        ORDER BY fecha_inicio DESC         LIMIT 12";
$periodos = fetchAll($sql);

// Filtros
$id_periodo = isset($_GET['id_periodo']) ? intval($_GET['id_periodo']) : 0;
$id_empleado = isset($_GET['id_empleado']) ? intval($_GET['id_empleado']) : 0;
$id_departamento = isset($_GET['id_departamento']) ? intval($_GET['id_departamento']) : 0;

// Construir consulta con filtros
$params = [];
$whereConditions = [];

if ($id_periodo > 0) {
    $whereConditions[] = "h.id_periodo = :id_periodo";
    $params[':id_periodo'] = $id_periodo;
}

if ($id_empleado > 0) {
    $whereConditions[] = "h.id_empleado = :id_empleado";
    $params[':id_empleado'] = $id_empleado;
}

if ($id_departamento > 0) {
    $whereConditions[] = "e.id_departamento = :id_departamento";
    $params[':id_departamento'] = $id_departamento;
}

$whereClause = empty($whereConditions) ? "" : "WHERE " . implode(" AND ", $whereConditions);

// Obtener listado de horas extra
$sql = "SELECT h.*, e.codigo_empleado, 
        CONCAT(e.primer_nombre, ' ', IFNULL(e.segundo_nombre, ''), ' ', e.primer_apellido, ' ', IFNULL(e.segundo_apellido, '')) as nombre_empleado,
        d.nombre as departamento,
        p.descripcion as periodo,
        CONCAT(DATE_FORMAT(p.fecha_inicio, '%d/%m/%Y'), ' - ', DATE_FORMAT(p.fecha_fin, '%d/%m/%Y')) as periodo_fechas,
        pl.estado as estado_planilla
        FROM horas_extra h
        INNER JOIN empleados e ON h.id_empleado = e.id_empleado
        INNER JOIN departamentos d ON e.id_departamento = d.id_departamento
        INNER JOIN periodos_nomina p ON h.id_periodo = p.id_periodo
        LEFT JOIN planillas pl ON p.id_periodo = pl.id_periodo AND (pl.id_departamento = e.id_departamento OR pl.id_departamento IS NULL)
        {$whereClause}
        ORDER BY h.fecha DESC, e.primer_apellido ASC";
$horasExtra = fetchAll($sql, $params);

// Obtener departamentos para el filtro
$sql = "SELECT id_departamento, nombre FROM departamentos WHERE estado = 'Activo' ORDER BY nombre";
$departamentos = fetchAll($sql);

// Obtener empleados para el filtro
$sql = "SELECT e.id_empleado, e.codigo_empleado,
        CONCAT(e.primer_apellido, ' ', IFNULL(e.segundo_apellido, ''), ', ', e.primer_nombre, ' ', IFNULL(e.segundo_nombre, '')) as nombre_completo,
        d.nombre as departamento
        FROM empleados e
        INNER JOIN departamentos d ON e.id_departamento = d.id_departamento
        WHERE e.estado = 'Activo'
        ORDER BY e.primer_apellido, e.primer_nombre";
$empleados = fetchAll($sql);
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="fas fa-clock me-2"></i> Horas Extra</h2>
        <p class="text-muted">Gestionar horas extra de empleados</p>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="index.php?page=horas_extra/nuevo" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i> Registrar Horas Extra
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form action="" method="get" class="row g-3">
            <input type="hidden" name="page" value="horas_extra/index">
            
            <div class="col-md-4">
                <label for="id_periodo" class="form-label">Periodo</label>
                <select class="form-select" id="id_periodo" name="id_periodo">
                    <option value="">Todos los periodos</option>
                    <?php foreach ($periodos as $periodo): ?>
                    <option value="<?php echo $periodo['id_periodo']; ?>" <?php echo $id_periodo == $periodo['id_periodo'] ? 'selected' : ''; ?>>
                        <?php echo $periodo['periodo_texto']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="id_departamento" class="form-label">Departamento</label>
                <select class="form-select" id="id_departamento" name="id_departamento">
                    <option value="">Todos los departamentos</option>
                    <?php foreach ($departamentos as $departamento): ?>
                    <option value="<?php echo $departamento['id_departamento']; ?>" <?php echo $id_departamento == $departamento['id_departamento'] ? 'selected' : ''; ?>>
                        <?php echo $departamento['nombre']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="id_empleado" class="form-label">Empleado</label>
                <select class="form-select selectpicker" id="id_empleado" name="id_empleado" data-live-search="true">
                    <option value="">Todos los empleados</option>
                    <?php foreach ($empleados as $empleado): ?>
                    <option value="<?php echo $empleado['id_empleado']; ?>" <?php echo $id_empleado == $empleado['id_empleado'] ? 'selected' : ''; ?> data-subtext="<?php echo $empleado['departamento']; ?>">
                        <?php echo $empleado['codigo_empleado']; ?> - <?php echo $empleado['nombre_completo']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-12 text-end">
                <a href="index.php?page=horas_extra/index" class="btn btn-secondary me-2">Limpiar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Listado de Horas Extra -->
<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0">Horas Extra Registradas</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Cantidad</th>
                        <th>Valor</th>
                        <th>Total</th>
                        <th>Periodo</th>
                        <th>Estado</th>
                        <th width="120">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($horasExtra)): ?>
                        <?php foreach ($horasExtra as $horaExtra): ?>
                            <?php 
                            $puedeEditar = $horaExtra['estado_planilla'] != 'Procesada';
                            $total = floatval($horaExtra['cantidad']) * floatval($horaExtra['valor_hora']);
                            ?>
                            <tr>
                                <td><?php echo $horaExtra['id_hora_extra']; ?></td>
                                <td>
                                    <div><?php echo $horaExtra['nombre_empleado']; ?></div>
                                    <small class="text-muted"><?php echo $horaExtra['codigo_empleado']; ?> - <?php echo $horaExtra['departamento']; ?></small>
                                </td>
                                <td><?php echo formatDate($horaExtra['fecha']); ?></td>
                                <td><?php echo number_format($horaExtra['cantidad'], 2); ?> hrs</td>
                                <td>Q <?php echo number_format($horaExtra['valor_hora'], 2); ?></td>
                                <td><strong>Q <?php echo number_format($total, 2); ?></strong></td>
                                <td>
                                    <div><?php echo $horaExtra['periodo']; ?></div>
                                    <small class="text-muted"><?php echo $horaExtra['periodo_fechas']; ?></small>
                                </td>
                                <td>
                                    <?php if ($horaExtra['estado_planilla'] == 'Procesada'): ?>
                                    <span class="badge bg-success">Procesada</span>
                                    <?php elseif ($horaExtra['estado_planilla'] == 'Borrador'): ?>
                                    <span class="badge bg-warning">Borrador</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($puedeEditar): ?>
                                    <a href="index.php?page=horas_extra/editar&id=<?php echo $horaExtra['id_hora_extra']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?page=horas_extra/index&accion=eliminar&id=<?php echo $horaExtra['id_hora_extra']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este registro?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Procesada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No hay horas extra registradas</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Resumen -->
<?php if (!empty($horasExtra)): ?>
<div class="card shadow-sm mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Resumen</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="card border-left-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Horas Extra</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php 
                                    $totalHoras = array_reduce($horasExtra, function($carry, $item) {
                                        return $carry + floatval($item['cantidad']);
                                    }, 0);
                                    echo number_format($totalHoras, 2);
                                    ?> hrs
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-left-success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Valor Total</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    Q <?php 
                                    $totalValor = array_reduce($horasExtra, function($carry, $item) {
                                        return $carry + (floatval($item['cantidad']) * floatval($item['valor_hora']));
                                    }, 0);
                                    echo number_format($totalValor, 2);
                                    ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-left-info h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Empleados con Horas Extra</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php 
                                    $empleadosUnicos = count(array_unique(array_column($horasExtra, 'id_empleado')));
                                    echo $empleadosUnicos;
                                    ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('.datatable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[2, 'desc']]
    });
    
    // Inicializar selectpicker para la búsqueda de empleados
    $('.selectpicker').selectpicker({
        liveSearch: true,
        size: 10,
        noneResultsText: 'No se encontraron resultados para {0}',
        liveSearchPlaceholder: 'Buscar empleado...'
    });
    
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script> 