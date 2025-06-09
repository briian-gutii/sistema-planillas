<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_GERENCIA, ROL_CONTABILIDAD])) {
    setFlashMessage('danger', ERROR_ACCESS);
    header('Location: index.php');
    exit;
}

// Obtener periodos para el filtro$sql = "SELECT id_periodo, CONCAT(DATE_FORMAT(fecha_inicio, '%d/%m/%Y'), ' - ',         DATE_FORMAT(fecha_fin, '%d/%m/%Y'), ' (', nombre, ')') as periodo_texto         FROM periodos         ORDER BY fecha_inicio DESC         LIMIT 12";$periodos = fetchAll($sql);

// Obtener departamentos para el filtro
$sql = "SELECT id_departamento, nombre FROM departamentos ORDER BY nombre";
$departamentos = fetchAll($sql);
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="fas fa-chart-bar me-2"></i> Reportes del Sistema</h2>
    </div>
</div>

<div class="row">
    <!-- Tarjeta de Reportes de Planillas -->
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card border-left-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Reportes de Planillas</h5>
                    <i class="fas fa-file-invoice-dollar fa-2x text-primary"></i>
                </div>
                <p class="card-text">Genera reportes detallados de planillas por periodo, departamento o empleado.</p>
                <a href="index.php?page=reportes/planillas" class="btn btn-primary">
                    <i class="fas fa-file-export me-1"></i> Generar Reportes
                </a>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de Reportes de Prestaciones -->
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card border-left-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Reportes de Prestaciones</h5>
                    <i class="fas fa-hand-holding-usd fa-2x text-success"></i>
                </div>
                <p class="card-text">Genera reportes de Aguinaldo, Bono 14 e indemnizaciones.</p>
                <a href="index.php?page=reportes/prestaciones" class="btn btn-success">
                    <i class="fas fa-file-export me-1"></i> Generar Reportes
                </a>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de Reportes Tributarios -->
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card border-left-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Reportes Tributarios</h5>
                    <i class="fas fa-file-invoice fa-2x text-danger"></i>
                </div>
                <p class="card-text">Genera reportes de ISR, constancias de ingresos y retenciones para la SAT.</p>
                <a href="index.php?page=reportes/tributarios" class="btn btn-danger">
                    <i class="fas fa-file-export me-1"></i> Generar Reportes
                </a>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de Reportes IGSS -->
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card border-left-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Reportes IGSS</h5>
                    <i class="fas fa-hospital-user fa-2x text-info"></i>
                </div>
                <p class="card-text">Genera reportes para el Instituto Guatemalteco de Seguridad Social.</p>
                <a href="index.php?page=reportes/igss" class="btn btn-info">
                    <i class="fas fa-file-export me-1"></i> Generar Reportes
                </a>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de Reportes de Empleados -->
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card border-left-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Reportes de Empleados</h5>
                    <i class="fas fa-users fa-2x text-warning"></i>
                </div>
                <p class="card-text">Genera listados de empleados, vacaciones, ausencias y más.</p>
                <a href="index.php?page=reportes/empleados" class="btn btn-warning">
                    <i class="fas fa-file-export me-1"></i> Generar Reportes
                </a>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de Boletas de Pago -->
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card border-left-secondary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Boletas de Pago</h5>
                    <i class="fas fa-receipt fa-2x text-secondary"></i>
                </div>
                <p class="card-text">Genera boletas de pago individuales o masivas para los empleados.</p>
                <a href="index.php?page=reportes/boletas" class="btn btn-secondary">
                    <i class="fas fa-file-export me-1"></i> Generar Boletas
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Sección de Exportación Personalizada -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Exportación Personalizada</h5>
    </div>
    <div class="card-body">
        <form action="index.php?page=reportes/personalizado" method="post" class="row g-3">
            <div class="col-md-4">
                <label for="tipo_reporte" class="form-label">Tipo de Reporte</label>
                <select class="form-select" id="tipo_reporte" name="tipo_reporte" required>
                    <option value="">Seleccione...</option>
                    <option value="planilla_detallada">Planilla Detallada</option>
                    <option value="resumen_departamentos">Resumen por Departamentos</option>
                    <option value="historial_empleado">Historial de Empleado</option>
                    <option value="deducciones">Reporte de Deducciones</option>
                    <option value="bonificaciones">Reporte de Bonificaciones</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="id_periodo" class="form-label">Periodo</label>
                <select class="form-select" id="id_periodo" name="id_periodo">
                    <option value="">Todos los periodos</option>
                    <?php foreach ($periodos as $periodo): ?>
                    <option value="<?php echo $periodo['id_periodo']; ?>"><?php echo $periodo['periodo_texto']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="id_departamento" class="form-label">Departamento</label>
                <select class="form-select" id="id_departamento" name="id_departamento">
                    <option value="">Todos los departamentos</option>
                    <?php foreach ($departamentos as $departamento): ?>
                    <option value="<?php echo $departamento['id_departamento']; ?>"><?php echo $departamento['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="formato" class="form-label">Formato de Exportación</label>
                <select class="form-select" id="formato" name="formato" required>
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                    <option value="csv">CSV</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
            </div>
            
            <div class="col-md-4">
                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
            </div>
            
            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-file-export me-1"></i> Generar Reporte Personalizado
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cambiar campos disponibles según el tipo de reporte
    document.getElementById('tipo_reporte').addEventListener('change', function() {
        const tipoReporte = this.value;
        const periodosField = document.getElementById('id_periodo').closest('.col-md-4');
        const departamentosField = document.getElementById('id_departamento').closest('.col-md-4');
        const fechaInicioField = document.getElementById('fecha_inicio').closest('.col-md-4');
        const fechaFinField = document.getElementById('fecha_fin').closest('.col-md-4');
        
        // Mostrar/ocultar campos según el tipo de reporte
        switch(tipoReporte) {
            case 'historial_empleado':
                periodosField.style.display = 'none';
                departamentosField.style.display = 'none';
                fechaInicioField.style.display = 'block';
                fechaFinField.style.display = 'block';
                break;
            case 'resumen_departamentos':
                periodosField.style.display = 'block';
                departamentosField.style.display = 'none';
                fechaInicioField.style.display = 'none';
                fechaFinField.style.display = 'none';
                break;
            default:
                periodosField.style.display = 'block';
                departamentosField.style.display = 'block';
                fechaInicioField.style.display = 'none';
                fechaFinField.style.display = 'none';
        }
    });
});
</script> 