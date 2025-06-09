<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_GERENCIA, ROL_CONTABILIDAD, ROL_RRHH])) {
    setFlashMessage('danger', ERROR_ACCESS);
    header('Location: index.php');
    exit;
}

// Obtener listado de tipos de prestaciones
$tiposPrestaciones = [
    'AGUINALDO' => 'Aguinaldo',
    'BONO14' => 'Bono 14',
    'VACACIONES' => 'Vacaciones',
    'INDEMNIZACION' => 'Indemnización'
];

// Obtener el periodo fiscal actual
$anioActual = date('Y');
$periodoFiscal = ($anioActual - 1) . '-' . $anioActual;

// Si estamos en noviembre o diciembre, mostrar el periodo fiscal siguiente
if (date('n') >= 11) {
    $periodoFiscal = $anioActual . '-' . ($anioActual + 1);
}

// Obtener listado de prestaciones calculadas
$sql = "SELECT p.*, tp.nombre as tipo_prestacion, 
        COUNT(dp.id_detalle_prestacion) as total_empleados,
        SUM(dp.monto) as monto_total
        FROM prestaciones p
        INNER JOIN tipos_prestacion tp ON p.id_tipo_prestacion = tp.id_tipo_prestacion
        LEFT JOIN detalles_prestacion dp ON p.id_prestacion = dp.id_prestacion
        GROUP BY p.id_prestacion
        ORDER BY p.fecha_calculo DESC, p.periodo DESC";
$prestaciones = fetchAll($sql);
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="fas fa-gift me-2"></i> Gestión de Prestaciones</h2>
    </div>
    <div class="col-md-6 text-md-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCalculoPrestacion">
            <i class="fas fa-plus-circle me-1"></i> Nuevo Cálculo
        </button>
    </div>
</div>

<!-- Tarjetas de Prestaciones -->
<div class="row mb-4">
    <!-- Aguinaldo -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Aguinaldo</h5>
                    <i class="fas fa-gift fa-2x text-danger"></i>
                </div>
                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                    Fecha límite: 15 de diciembre
                </div>
                <p class="card-text">
                    Salario promedio mensual devengado durante el último año.
                </p>
                <a href="index.php?page=prestaciones/aguinaldo" class="btn btn-sm btn-danger">
                    Gestionar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bono 14 -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Bono 14</h5>
                    <i class="fas fa-money-check-alt fa-2x text-primary"></i>
                </div>
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    Fecha límite: 15 de julio
                </div>
                <p class="card-text">
                    Salario promedio mensual devengado en el periodo julio-junio.
                </p>
                <a href="index.php?page=prestaciones/bono14" class="btn btn-sm btn-primary">
                    Gestionar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Vacaciones -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Vacaciones</h5>
                    <i class="fas fa-umbrella-beach fa-2x text-success"></i>
                </div>
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                    15 días hábiles anuales
                </div>
                <p class="card-text">
                    Control y pago de vacaciones según la ley.
                </p>
                <a href="index.php?page=prestaciones/vacaciones" class="btn btn-sm btn-success">
                    Gestionar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Indemnización -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Indemnización</h5>
                    <i class="fas fa-hand-holding-usd fa-2x text-warning"></i>
                </div>
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                    Un mes por año laborado
                </div>
                <p class="card-text">
                    Cálculo de indemnizaciones por terminación laboral.
                </p>
                <a href="index.php?page=prestaciones/indemnizacion" class="btn btn-sm btn-warning">
                    Gestionar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Prestaciones Calculadas -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Historial de Cálculos de Prestaciones</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Periodo</th>
                        <th>Fecha Cálculo</th>
                        <th>Empleados</th>
                        <th>Monto Total</th>
                        <th>Estado</th>
                        <th width="120">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($prestaciones)): ?>
                        <?php foreach ($prestaciones as $prestacion): ?>
                        <tr>
                            <td><?php echo $prestacion['id_prestacion']; ?></td>
                            <td><?php echo $prestacion['tipo_prestacion']; ?></td>
                            <td><?php echo $prestacion['descripcion']; ?></td>
                            <td><?php echo $prestacion['periodo']; ?></td>
                            <td><?php echo formatDate($prestacion['fecha_calculo']); ?></td>
                            <td><?php echo number_format($prestacion['total_empleados']); ?></td>
                            <td>Q <?php echo number_format($prestacion['monto_total'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $prestacion['estado'] == 'Pagado' ? 'success' : 'warning'; ?>">
                                    <?php echo $prestacion['estado']; ?>
                                </span>
                            </td>
                            <td class="datatable-actions">
                                <a href="index.php?page=prestaciones/detalles&id=<?php echo $prestacion['id_prestacion']; ?>" 
                                   class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($prestacion['estado'] != 'Pagado'): ?>
                                <a href="index.php?page=prestaciones/procesar&id=<?php echo $prestacion['id_prestacion']; ?>" 
                                   class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Procesar Pago">
                                    <i class="fas fa-check-circle"></i>
                                </a>
                                <?php endif; ?>
                                <a href="index.php?page=reportes/prestacion_pdf&id=<?php echo $prestacion['id_prestacion']; ?>" 
                                   class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Generar PDF" target="_blank">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No hay prestaciones calculadas</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Nuevo Cálculo de Prestación -->
<div class="modal fade" id="modalCalculoPrestacion" tabindex="-1" aria-labelledby="modalCalculoPrestacionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="index.php?page=prestaciones/calcular" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCalculoPrestacionLabel">Nuevo Cálculo de Prestación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tipo_prestacion" class="form-label required-field">Tipo de Prestación</label>
                        <select class="form-select" id="tipo_prestacion" name="tipo_prestacion" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($tiposPrestaciones as $key => $value): ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Por favor seleccione un tipo de prestación</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label required-field">Descripción</label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion" required>
                        <div class="invalid-feedback">Por favor ingrese una descripción</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="periodo" class="form-label required-field">Periodo Fiscal</label>
                        <input type="text" class="form-control" id="periodo" name="periodo" value="<?php echo $periodoFiscal; ?>" required>
                        <div class="form-text" id="periodoHelp">Formato: YYYY-YYYY (ej. 2023-2024)</div>
                        <div class="invalid-feedback">Por favor ingrese el periodo fiscal</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_departamento" class="form-label">Departamento (opcional)</label>
                        <select class="form-select" id="id_departamento" name="id_departamento">
                            <option value="">Todos los departamentos</option>
                            <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?php echo $departamento['id_departamento']; ?>"><?php echo $departamento['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fecha_inicio" class="form-label required-field" id="label_fecha_inicio">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                        <div class="invalid-feedback">Por favor seleccione la fecha de inicio</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fecha_fin" class="form-label required-field" id="label_fecha_fin">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                        <div class="invalid-feedback">Por favor seleccione la fecha fin</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Calcular Prestación</button>
                </div>
            </form>
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
    
    // Configurar fechas según tipo de prestación
    document.getElementById('tipo_prestacion').addEventListener('change', function() {
        const tipo = this.value;
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');
        const labelInicio = document.getElementById('label_fecha_inicio');
        const labelFin = document.getElementById('label_fecha_fin');
        const hoy = new Date();
        const anioActual = hoy.getFullYear();
        
        switch(tipo) {
            case 'AGUINALDO':
                labelInicio.textContent = 'Fecha Inicio (Dic. año anterior)';
                labelFin.textContent = 'Fecha Fin (Nov. año actual)';
                fechaInicio.value = `${anioActual-1}-12-01`;
                fechaFin.value = `${anioActual}-11-30`;
                break;
            case 'BONO14':
                labelInicio.textContent = 'Fecha Inicio (Jul. año anterior)';
                labelFin.textContent = 'Fecha Fin (Jun. año actual)';
                fechaInicio.value = `${anioActual-1}-07-01`;
                fechaFin.value = `${anioActual}-06-30`;
                break;
            default:
                labelInicio.textContent = 'Fecha Inicio';
                labelFin.textContent = 'Fecha Fin';
                fechaInicio.value = '';
                fechaFin.value = '';
        }
    });
});
</script> 