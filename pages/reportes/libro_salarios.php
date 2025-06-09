<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_GERENCIA, ROL_CONTABILIDAD])) {
    setFlashMessage('danger', ERROR_ACCESS);
    header('Location: index.php');
    exit;
}

// Procesar generación de libro de salarios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anio = isset($_POST['anio']) ? intval($_POST['anio']) : date('Y');
    $formato = isset($_POST['formato']) ? $_POST['formato'] : 'excel';
    $id_departamento = isset($_POST['id_departamento']) ? intval($_POST['id_departamento']) : 0;
    
    // Redirigir a la generación del reporte
    $url = "index.php?page=reportes/generar_libro_salarios&anio={$anio}&formato={$formato}";
    if ($id_departamento > 0) {
        $url .= "&id_departamento={$id_departamento}";
    }
    
    header("Location: {$url}");
    exit;
}

// Obtener años disponibles en la base de datos$sql = "SELECT DISTINCT YEAR(fecha_inicio) as anio         FROM periodos         WHERE EXISTS (SELECT 1 FROM planillas WHERE planillas.id_periodo = periodos.id_periodo)        ORDER BY anio DESC";$aniosDisponibles = fetchAll($sql);

// Si no hay datos, usar el año actual y los 2 anteriores
if (empty($aniosDisponibles)) {
    $anioActual = date('Y');
    $anios = [$anioActual, $anioActual - 1, $anioActual - 2];
} else {
    $anios = array_column($aniosDisponibles, 'anio');
}

// Obtener departamentos
$sql = "SELECT id_departamento, nombre FROM departamentos WHERE estado = 'Activo' ORDER BY nombre";
$departamentos = fetchAll($sql);
?>

<div class="row mb-3">
    <div class="col">
        <h2><i class="fas fa-book me-2"></i> Libro de Salarios</h2>
        <p class="text-muted">Generar libro anual de salarios</p>
    </div>
</div>

<div class="row">
    <!-- Sección de generación del libro -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> El Libro de Salarios es un documento obligatorio según el Código de Trabajo. Contiene el detalle anual de los pagos realizados a cada empleado.
                </div>
                
                <form method="post" action="" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="anio" class="form-label required-field">Año</label>
                            <select class="form-select" id="anio" name="anio" required>
                                <?php foreach ($anios as $anio): ?>
                                <option value="<?php echo $anio; ?>" <?php echo $anio == date('Y') - 1 ? 'selected' : ''; ?>>
                                    <?php echo $anio; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Por favor seleccione un año</div>
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
                        
                        <div class="col-md-6">
                            <label for="formato" class="form-label required-field">Formato</label>
                            <select class="form-select" id="formato" name="formato" required>
                                <option value="excel">Microsoft Excel</option>
                                <option value="pdf">PDF</option>
                                <option value="csv">CSV</option>
                            </select>
                            <div class="invalid-feedback">Por favor seleccione un formato</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="incluir_bajas" class="form-label d-block">Opciones</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="incluir_bajas" name="incluir_bajas" checked>
                                <label class="form-check-label" for="incluir_bajas">Incluir empleados dados de baja</label>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Información sobre el Libro de Salarios</h5>
                                    <p class="card-text">El libro contendrá la siguiente información:</p>
                                    <ul>
                                        <li>Datos personales de cada empleado</li>
                                        <li>Salario base mensual</li>
                                        <li>Horas extra y bonificaciones</li>
                                        <li>Deducciones (IGSS, ISR, etc.)</li>
                                        <li>Vacaciones y aguinaldos</li>
                                        <li>Indemnizaciones y liquidaciones (si aplica)</li>
                                        <li>Totales anuales por cada concepto</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 text-end mt-3">
                            <a href="index.php?page=reportes/index" class="btn btn-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-export me-1"></i> Generar Libro de Salarios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sección de historial -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Libros Generados Recientemente</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php
                    // Aquí debería mostrarse un historial de libros generados
                    // Esta es una muestra estática
                    $librosRecientes = [
                        ['anio' => 2023, 'fecha' => '2024-01-15', 'formato' => 'excel', 'usuario' => 'Admin'],
                        ['anio' => 2022, 'fecha' => '2023-01-20', 'formato' => 'pdf', 'usuario' => 'Admin'],
                    ];
                    
                    if (!empty($librosRecientes)):
                        foreach ($librosRecientes as $libro):
                    ?>
                    <a href="#" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Libro de Salarios <?php echo $libro['anio']; ?></h6>
                            <small>
                                <i class="fas fa-<?php echo $libro['formato'] == 'excel' ? 'file-excel text-success' : 'file-pdf text-danger'; ?>"></i>
                            </small>
                        </div>
                        <small class="text-muted">
                            Generado el <?php echo formatDate($libro['fecha']); ?> por <?php echo $libro['usuario']; ?>
                        </small>
                    </a>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <div class="text-center py-3">
                        <i class="fas fa-folder-open text-muted fa-2x mb-2"></i>
                        <p class="mb-0">No hay libros generados recientemente</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-light">
                <h5 class="mb-0">Requisitos Legales</h5>
            </div>
            <div class="card-body">
                <p><i class="fas fa-check-circle text-success me-2"></i> Obligatorio según el Código de Trabajo</p>
                <p><i class="fas fa-check-circle text-success me-2"></i> Debe mantenerse actualizado</p>
                <p><i class="fas fa-check-circle text-success me-2"></i> Conservar por un mínimo de 4 años</p>
                <p><i class="fas fa-check-circle text-success me-2"></i> Debe estar disponible para inspección</p>
                
                <div class="mt-3">
                    <a href="https://www.mintrabajo.gob.gt/index.php/documentacion/leyes-ordinarias" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> Ver regulaciones
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar validación de formulario
    Forms.initValidation();
});
</script>