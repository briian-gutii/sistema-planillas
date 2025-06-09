<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_CONTABILIDAD])) {
    setFlashMessage('danger', ERROR_ACCESS);
    header('Location: index.php');
    exit;
}

// Procesar formulario de generación de planilla
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_periodo = isset($_POST['id_periodo']) ? intval($_POST['id_periodo']) : 0;
    $id_departamento = isset($_POST['id_departamento']) ? intval($_POST['id_departamento']) : 0;
    $descripcion = trim($_POST['descripcion'] ?? '');
    $incluir_bonos = isset($_POST['incluir_bonos']) ? 1 : 0;
    $incluir_horas_extra = isset($_POST['incluir_horas_extra']) ? 1 : 0;
    
    if ($id_periodo <= 0) {
        setFlashMessage('danger', 'Debe seleccionar un periodo válido.');
    } else {
        try {
            // Verificar si ya existe una planilla para este periodo y departamento
            $sqlVerificar = "SELECT id_planilla FROM Planillas 
                           WHERE id_periodo = :id_periodo";
            $planillaExistente = fetchRow($sqlVerificar, [
                ':id_periodo' => $id_periodo
            ]);
            
            if ($planillaExistente) {
                setFlashMessage('warning', 'Ya existe una planilla para el periodo seleccionado.');
                header('Location: index.php?page=planillas/lista');
                exit;
            }
            
            // Iniciar generación de planilla
            $url = "index.php?page=planillas/procesar&id_periodo={$id_periodo}&id_departamento={$id_departamento}&descripcion=" . urlencode($descripcion) . "&incluir_bonos={$incluir_bonos}&incluir_horas_extra={$incluir_horas_extra}";
            
            // Log para depuración
            error_log("Redireccionando a: " . $url);
            
            // Redireccionar al procesamiento
            header("Location: " . $url);
            exit;
        } catch (Exception $e) {
            setFlashMessage('danger', 'Error al verificar planilla: ' . $e->getMessage());
        }
    }
}

// Obtener periodos disponibles
$sql = "SELECT p.id_periodo, 
        CONCAT(DATE_FORMAT(p.fecha_inicio, '%d/%m/%Y'), ' - ', 
        DATE_FORMAT(p.fecha_fin, '%d/%m/%Y'), ' (', p.tipo, ')') as periodo_texto,
        (SELECT COUNT(*) FROM Planillas pl WHERE pl.id_periodo = p.id_periodo) as tiene_planilla
        FROM periodos_pago p
        WHERE p.estado = 'Abierto'
        ORDER BY p.fecha_inicio DESC 
        LIMIT 12";
$periodos = fetchAll($sql);

// Obtener departamentos
$sql = "SELECT id_departamento, nombre FROM Departamentos ORDER BY nombre";
$departamentos = fetchAll($sql);
?>

<div class="row mb-3">
    <div class="col">
        <h2><i class="fas fa-file-invoice-dollar me-2"></i> Generar Planilla</h2>
        <p class="text-muted">Crear una nueva planilla de pago</p>
    </div>
</div>

<?php if (empty($periodos)): ?>
<div class="alert alert-warning">
    <strong>No se encontraron períodos disponibles.</strong> Antes de generar una planilla, debe haber períodos en estado "Abierto".
    <hr>
    <a href="<?php echo BASE_URL; ?>periodos_pago_diagnostico.php" target="_blank" class="btn btn-primary">Diagnosticar Períodos</a>
    <a href="<?php echo BASE_URL; ?>insertar_periodos_pago.php" target="_blank" class="btn btn-success">Insertar Períodos</a>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Seleccione el periodo y departamento (opcional) para la generación de la planilla. El proceso calculará automáticamente los salarios, deducciones y asignaciones para cada empleado.
        </div>
        
        <form method="post" action="" class="needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="id_periodo" class="form-label required-field">Periodo de Pago</label>
                    <select class="form-select" id="id_periodo" name="id_periodo" required>
                        <option value="">Seleccione un periodo...</option>
                        <?php foreach ($periodos as $periodo): ?>
                        <option value="<?php echo $periodo['id_periodo']; ?>" <?php echo $periodo['tiene_planilla'] > 0 ? 'class="text-warning"' : ''; ?>>
                            <?php echo $periodo['periodo_texto']; ?>
                            <?php if ($periodo['tiene_planilla'] > 0): ?>
                            (Tiene planilla)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Por favor seleccione un periodo</div>
                </div>
                
                <div class="col-md-6">
                    <label for="id_departamento" class="form-label">Departamento (opcional)</label>
                    <select class="form-select" id="id_departamento" name="id_departamento">
                        <option value="0">Todos los departamentos</option>
                        <?php foreach ($departamentos as $departamento): ?>
                        <option value="<?php echo $departamento['id_departamento']; ?>">
                            <?php echo $departamento['nombre']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-12">
                    <label for="descripcion" class="form-label required-field">Descripción</label>
                    <input type="text" class="form-control" id="descripcion" name="descripcion" required>
                    <div class="invalid-feedback">Por favor ingrese una descripción</div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="incluir_bonos" name="incluir_bonos" checked>
                        <label class="form-check-label" for="incluir_bonos">Incluir bonificaciones</label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="incluir_horas_extra" name="incluir_horas_extra" checked>
                        <label class="form-check-label" for="incluir_horas_extra">Incluir horas extra</label>
                    </div>
                </div>
                
                <div class="col-12 mt-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Información del Proceso</h5>
                            <p class="card-text">Al generar la planilla ocurrirá lo siguiente:</p>
                            <ul>
                                <li>Se crearán registros para todos los empleados activos en el periodo seleccionado</li>
                                <li>Se calcularán automáticamente las deducciones legales (IGSS, ISR, etc.)</li>
                                <li>Las bonificaciones y horas extra se incluirán si están seleccionadas</li>
                                <li>Los préstamos y descuentos serán considerados según su programación</li>
                                <li>El estado inicial de la planilla será "Borrador" para su revisión</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 text-end mt-3">
                    <a href="index.php?page=planillas/lista" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calculator me-1"></i> Generar Planilla
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar validación de formulario
    Forms.initValidation();
    
    // Actualizar descripción basada en el periodo seleccionado
    document.getElementById('id_periodo').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value !== '') {
            const periodoText = selectedOption.text.split('(')[0].trim();
            document.getElementById('descripcion').value = 'Planilla - ' + periodoText;
        }
    });
    
    // Mostrar advertencia si selecciona un periodo con planilla existente
    document.getElementById('id_periodo').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.classList.contains('text-warning')) {
            alert('Advertencia: Ya existe una planilla para este periodo. Si continúa, se generará una planilla adicional.');
        }
    });
});
</script> 