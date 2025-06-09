<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_GERENCIA, ROL_CONTABILIDAD])) {
    setFlashMessage('danger', ERROR_ACCESS);
    header('Location: index.php');
    exit;
}

// Obtener el año actual y los 3 años anteriores para el filtro
$anioActual = date('Y');
$anios = [];
for ($i = 0; $i <= 3; $i++) {
    $anio = $anioActual - $i;
    $anios[$anio] = $anio;
}

// Obtener los registros de ISR calculados
$sql = "SELECT c.id_calculo_isr, c.anio, c.descripcion, c.fecha_calculo, c.estado,
        COUNT(d.id_detalle_isr) as total_empleados,
        SUM(d.total_ingresos) as total_ingresos,
        SUM(d.isr_retenido) as total_isr 
        FROM calculos_isr c
        LEFT JOIN detalles_isr d ON c.id_calculo_isr = d.id_calculo_isr
        GROUP BY c.id_calculo_isr
        ORDER BY c.anio DESC, c.fecha_calculo DESC";
$calculos = fetchAll($sql);

// Procesar el cálculo de ISR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'calcular_isr') {
        $anio = isset($_POST['anio']) ? intval($_POST['anio']) : $anioActual;
        $descripcion = $_POST['descripcion'] ?? "Cálculo ISR {$anio}";
        $id_departamento = isset($_POST['id_departamento']) ? intval($_POST['id_departamento']) : 0;
        
        // Redirigir al script de cálculo
        header("Location: index.php?page=reportes/calcular_isr&anio={$anio}&descripcion=" . urlencode($descripcion) . "&id_departamento={$id_departamento}");
        exit;
    }
}

// Obtener departamentos para el filtro
$sql = "SELECT id_departamento, nombre FROM departamentos WHERE estado = 'Activo' ORDER BY nombre";
$departamentos = fetchAll($sql);
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="fas fa-file-invoice me-2"></i> Reportes Tributarios</h2>
    </div>
    <div class="col-md-6 text-md-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCalculoISR">
            <i class="fas fa-calculator me-1"></i> Calcular ISR
        </button>
    </div>
</div>

<!-- Sección Informativa -->
<div class="alert alert-info mb-4">
    <h5><i class="fas fa-info-circle me-2"></i> Información sobre el ISR en Guatemala</h5>
    <p class="mb-2">El Impuesto Sobre la Renta (ISR) en Guatemala para asalariados se calcula de la siguiente manera:</p>
    <ul>
        <li>Monto imponible = Ingresos Totales - Deducciones Obligatorias (IGSS, IPM) - Deducciones Personales (hasta Q.60,000)</li>
        <li>Exento hasta Q.48,000 (no paga ISR)</li>
        <li>De Q.48,001 a Q.150,000: 5% sobre el excedente de Q.48,000</li>
        <li>De Q.150,001 a Q.250,000: Q.5,100 + 7% sobre el excedente de Q.150,000</li>
        <li>De Q.250,001 a Q.400,000: Q.12,100 + 10% sobre el excedente de Q.250,000</li>
        <li>Más de Q.400,000: Q.27,100 + 12% sobre el excedente de Q.400,000</li>
    </ul>
</div>

<!-- Tarjetas de Reportes Tributarios -->
<div class="row mb-4">
    <!-- ISR -->
    <div class="col-md-4 mb-4">
        <div class="card border-left-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Declaración Anual ISR</h5>
                    <i class="fas fa-file-invoice-dollar fa-2x text-danger"></i>
                </div>
                <p class="card-text">
                    Genera reportes para la declaración anual del ISR de los empleados según la normativa de la SAT.
                </p>
                <div class="mt-3">
                    <a href="#" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalCalculoISR">
                        <i class="fas fa-calculator me-1"></i> Calcular
                    </a>
                    <a href="index.php?page=reportes/constancias_isr" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i> Constancias
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- IVA -->
    <div class="col-md-4 mb-4">
        <div class="card border-left-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Retenciones IVA</h5>
                    <i class="fas fa-percentage fa-2x text-primary"></i>
                </div>
                <p class="card-text">
                    Genera reportes de retenciones de IVA para facturas especiales y servicios técnicos.
                </p>
                <div class="mt-3">
                    <a href="index.php?page=reportes/retenciones_iva" class="btn btn-primary btn-sm">
                        <i class="fas fa-file-export me-1"></i> Generar Reporte
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Libro de Salarios -->
    <div class="col-md-4 mb-4">
        <div class="card border-left-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Libro de Salarios</h5>
                    <i class="fas fa-book fa-2x text-success"></i>
                </div>
                <p class="card-text">
                    Genera el libro de salarios anual según el formato oficial requerido por la legislación laboral.
                </p>
                <div class="mt-3">
                    <a href="index.php?page=reportes/libro_salarios" class="btn btn-success btn-sm">
                        <i class="fas fa-file-export me-1"></i> Generar Libro
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Cálculos ISR -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Historial de Cálculos ISR</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Año</th>
                        <th>Descripción</th>
                        <th>Fecha Cálculo</th>
                        <th>Empleados</th>
                        <th>Total Ingresos</th>
                        <th>Total ISR</th>
                        <th>Estado</th>
                        <th width="150">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($calculos)): ?>
                        <?php foreach ($calculos as $calculo): ?>
                        <tr>
                            <td><?php echo $calculo['id_calculo_isr']; ?></td>
                            <td><?php echo $calculo['anio']; ?></td>
                            <td><?php echo $calculo['descripcion']; ?></td>
                            <td><?php echo formatDate($calculo['fecha_calculo']); ?></td>
                            <td><?php echo number_format($calculo['total_empleados']); ?></td>
                            <td>Q <?php echo number_format($calculo['total_ingresos'], 2); ?></td>
                            <td>Q <?php echo number_format($calculo['total_isr'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $calculo['estado'] == 'Finalizado' ? 'success' : 'warning'; ?>">
                                    <?php echo $calculo['estado']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="index.php?page=reportes/detalles_isr&id=<?php echo $calculo['id_calculo_isr']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?page=reportes/exportar_isr&id=<?php echo $calculo['id_calculo_isr']; ?>&formato=excel" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Exportar Excel">
                                    <i class="fas fa-file-excel"></i>
                                </a>
                                <a href="index.php?page=reportes/generar_constancias&id=<?php echo $calculo['id_calculo_isr']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Generar Constancias">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <a href="index.php?page=reportes/archivo_sat&id=<?php echo $calculo['id_calculo_isr']; ?>" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Archivo SAT">
                                    <i class="fas fa-upload"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No hay cálculos ISR registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Calcular ISR -->
<div class="modal fade" id="modalCalculoISR" tabindex="-1" aria-labelledby="modalCalculoISRLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="" class="needs-validation" novalidate>
                <input type="hidden" name="accion" value="calcular_isr">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCalculoISRLabel">Calcular ISR Anual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="anio" class="form-label required-field">Año Fiscal</label>
                        <select class="form-select" id="anio" name="anio" required>
                            <?php foreach ($anios as $anio): ?>
                            <option value="<?php echo $anio; ?>" <?php echo ($anio == $anioActual - 1) ? 'selected' : ''; ?>>
                                <?php echo $anio; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Por favor seleccione un año fiscal</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label required-field">Descripción</label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion" value="Cálculo ISR <?php echo $anioActual - 1; ?>" required>
                        <div class="invalid-feedback">Por favor ingrese una descripción</div>
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
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Al calcular el ISR, se procesará la información de todos los empleados activos durante el año fiscal seleccionado. Esta operación puede tardar varios minutos.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calculator me-1"></i> Iniciar Cálculo
                    </button>
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
        order: [[3, 'desc']]
    });
    
    // Inicializar validación de formulario
    Forms.initValidation();
    
    // Actualizar descripción cuando cambia el año
    document.getElementById('anio').addEventListener('change', function() {
        document.getElementById('descripcion').value = 'Cálculo ISR ' + this.value;
    });
});
</script> 