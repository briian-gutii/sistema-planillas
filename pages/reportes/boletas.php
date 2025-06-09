<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_GERENCIA, ROL_CONTABILIDAD, ROL_RRHH])) {
    setFlashMessage('danger', ERROR_ACCESS);
    header('Location: index.php');
    exit;
}

// Procesar generación de boletas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_periodo = isset($_POST['id_periodo']) ? intval($_POST['id_periodo']) : 0;
    $id_empleado = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : 0;
    $id_departamento = isset($_POST['id_departamento']) ? intval($_POST['id_departamento']) : 0;
    $formato = $_POST['formato'] ?? 'pdf';
    
    // Redirigir a la generación de boletas con los parámetros seleccionados
    $url = "index.php?page=reportes/generar_boletas&id_periodo={$id_periodo}";
    
    if ($id_empleado > 0) {
        $url .= "&id_empleado={$id_empleado}";
    }
    
    if ($id_departamento > 0) {
        $url .= "&id_departamento={$id_departamento}";
    }
    
    $url .= "&formato={$formato}";
    
    header("Location: {$url}");
    exit;
}

// Obtener periodos para el filtro$sql = "SELECT p.id_periodo, CONCAT(DATE_FORMAT(p.fecha_inicio, '%d/%m/%Y'), ' - ',         DATE_FORMAT(p.fecha_fin, '%d/%m/%Y'), ' (', p.nombre, ')') as periodo_texto,        COUNT(dp.id_detalle_planilla) as total_empleados         FROM periodos p        LEFT JOIN planillas pl ON p.id_periodo = pl.id_periodo        LEFT JOIN detalles_planilla dp ON pl.id_planilla = dp.id_planilla        WHERE pl.estado = 'Procesada'        GROUP BY p.id_periodo        ORDER BY p.fecha_inicio DESC         LIMIT 12";
$periodos = fetchAll($sql);

// Obtener departamentos para el filtro
$sql = "SELECT id_departamento, nombre FROM departamentos WHERE estado = 'Activo' ORDER BY nombre";
$departamentos = fetchAll($sql);

// Obtener empleados para el filtro
$sql = "SELECT e.id_empleado, e.codigo_empleado, 
        CONCAT(e.primer_apellido, ' ', IFNULL(e.segundo_apellido, ''), ', ', e.primer_nombre, ' ', IFNULL(e.segundo_nombre, '')) AS nombre_completo,
        d.nombre as departamento
        FROM empleados e
        LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
        WHERE e.estado = 'Activo'
        ORDER BY e.primer_apellido, e.primer_nombre";
$empleados = fetchAll($sql);
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="fas fa-receipt me-2"></i> Generación de Boletas de Pago</h2>
    </div>
</div>

<div class="row mb-4">
    <!-- Boletas Individuales -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-check me-2"></i> Boletas Individuales</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Generación de boletas de pago para un empleado específico.</p>
                <form action="" method="post" class="row g-3 needs-validation" novalidate>
                    <input type="hidden" name="tipo" value="individual">
                    
                    <div class="col-md-12">
                        <label for="id_periodo_individual" class="form-label required-field">Periodo</label>
                        <select class="form-select" id="id_periodo_individual" name="id_periodo" required>
                            <option value="">Seleccione un periodo...</option>
                            <?php foreach ($periodos as $periodo): ?>
                            <option value="<?php echo $periodo['id_periodo']; ?>">
                                <?php echo $periodo['periodo_texto']; ?> 
                                (<?php echo $periodo['total_empleados']; ?> empleados)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Por favor seleccione un periodo</div>
                    </div>
                    
                    <div class="col-md-12">
                        <label for="id_empleado" class="form-label required-field">Empleado</label>
                        <select class="form-select selectpicker" id="id_empleado" name="id_empleado" required data-live-search="true">
                            <option value="">Seleccione un empleado...</option>
                            <?php foreach ($empleados as $empleado): ?>
                            <option value="<?php echo $empleado['id_empleado']; ?>" data-subtext="<?php echo $empleado['departamento']; ?>">
                                <?php echo $empleado['codigo_empleado']; ?> - <?php echo $empleado['nombre_completo']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Por favor seleccione un empleado</div>
                    </div>
                    
                    <div class="col-md-12">
                        <label for="formato_individual" class="form-label">Formato</label>
                        <select class="form-select" id="formato_individual" name="formato">
                            <option value="pdf">PDF</option>
                            <option value="html">Vista previa HTML</option>
                        </select>
                    </div>
                    
                    <div class="col-12 text-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-export me-1"></i> Generar Boleta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Boletas Masivas -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i> Boletas Masivas</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Generación de boletas para múltiples empleados o departamentos.</p>
                <form action="" method="post" class="row g-3 needs-validation" novalidate>
                    <input type="hidden" name="tipo" value="masivo">
                    
                    <div class="col-md-12">
                        <label for="id_periodo_masivo" class="form-label required-field">Periodo</label>
                        <select class="form-select" id="id_periodo_masivo" name="id_periodo" required>
                            <option value="">Seleccione un periodo...</option>
                            <?php foreach ($periodos as $periodo): ?>
                            <option value="<?php echo $periodo['id_periodo']; ?>">
                                <?php echo $periodo['periodo_texto']; ?> 
                                (<?php echo $periodo['total_empleados']; ?> empleados)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Por favor seleccione un periodo</div>
                    </div>
                    
                    <div class="col-md-12">
                        <label for="id_departamento" class="form-label">Departamento (opcional)</label>
                        <select class="form-select" id="id_departamento" name="id_departamento">
                            <option value="">Todos los departamentos</option>
                            <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?php echo $departamento['id_departamento']; ?>">
                                <?php echo $departamento['nombre']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12">
                        <label for="formato_masivo" class="form-label">Formato</label>
                        <select class="form-select" id="formato_masivo" name="formato">
                            <option value="pdf">PDF consolidado</option>
                            <option value="pdf_individual">PDF individuales (ZIP)</option>
                            <option value="html">Vista previa HTML</option>
                        </select>
                    </div>
                    
                    <div class="col-12 text-end mt-3">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-file-export me-1"></i> Generar Boletas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Historial de Boletas Generadas -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">Historial de Boletas Generadas</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Periodo</th>
                        <th>Tipo</th>
                        <th>Generado por</th>
                        <th>Fecha Generación</th>
                        <th>Empleados</th>
                        <th width="120">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Aquí se mostrarían las boletas generadas previamente -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('.datatable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[4, 'desc']]
    });
    
    // Inicializar validación de formulario
    Forms.initValidation();
    
    // Inicializar selectpicker para la búsqueda de empleados
    $('.selectpicker').selectpicker({
        liveSearch: true,
        size: 10,
        noneResultsText: 'No se encontraron resultados para {0}',
        liveSearchPlaceholder: 'Buscar empleado...'
    });
});
</script> 