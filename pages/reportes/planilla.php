<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_CONTABILIDAD, ROL_RRHH])) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Reportes de Planillas';
$activeMenu = 'reportes';

// Obtener lista de periodos para el filtro
$db = getDB();
$periodos = [];
$departamentos = [];
$tiposReporte = [
    'planilla_detallada' => 'Planilla Detallada',
    'resumen_planilla' => 'Resumen de Planilla',
    'libro_salarios' => 'Libro de Salarios',
    'recibos_pago' => 'Recibos de Pago',
    'constancia_trabajo' => 'Constancia de Trabajo',
    'igss' => 'Reporte para IGSS',
    'isr' => 'Reporte de ISR'
];

try {
    // Obtener periodos
    $query = "SELECT id_periodo, nombre FROM periodos ORDER BY fecha_inicio DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener departamentos
    $queryDepartamentos = "SELECT id_departamento, nombre FROM departamentos ORDER BY nombre";
    $stmtDepartamentos = $db->prepare($queryDepartamentos);
    $stmtDepartamentos->execute();
    $departamentos = $stmtDepartamentos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    setFlashMessage('Error al cargar datos: ' . $e->getMessage(), 'danger');
}

// Variables para el reporte
$reporte = null;
$empleados = [];
$planillas = [];
$totales = [
    'salario_base' => 0,
    'bonificaciones' => 0,
    'horas_extra' => 0,
    'otras_percepciones' => 0,
    'igss' => 0,
    'isr' => 0,
    'otras_deducciones' => 0,
    'salario_liquido' => 0
];

// Procesar solicitud de reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_reporte = isset($_POST['tipo_reporte']) ? $_POST['tipo_reporte'] : '';
    $id_periodo = isset($_POST['id_periodo']) ? intval($_POST['id_periodo']) : 0;
    $id_departamento = isset($_POST['id_departamento']) ? intval($_POST['id_departamento']) : 0;
    $id_planilla = isset($_POST['id_planilla']) ? intval($_POST['id_planilla']) : 0;
    $id_empleado = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : 0;
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    
    // Validar que se haya seleccionado un tipo de reporte
    if (empty($tipo_reporte) || !array_key_exists($tipo_reporte, $tiposReporte)) {
        setFlashMessage('Debe seleccionar un tipo de reporte válido', 'danger');
    } else {
        try {
            // Generar el reporte según el tipo seleccionado
            switch ($tipo_reporte) {
                case 'planilla_detallada':
                    // Requiere período o planilla específica
                    if ($id_planilla > 0) {
                        $reporte = generarReportePlanillaDetallada($id_planilla);
                    } elseif ($id_periodo > 0) {
                        $reporte = generarReportePlanillaPorPeriodo($id_periodo, $id_departamento);
                    } else {
                        setFlashMessage('Debe seleccionar una planilla o un período', 'danger');
                    }
                    break;
                    
                case 'resumen_planilla':
                    // Requiere período o rango de fechas
                    if ($id_periodo > 0) {
                        $reporte = generarResumenPlanillaPorPeriodo($id_periodo, $id_departamento);
                    } elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
                        $reporte = generarResumenPlanillaPorFechas($fecha_inicio, $fecha_fin, $id_departamento);
                    } else {
                        setFlashMessage('Debe seleccionar un período o un rango de fechas', 'danger');
                    }
                    break;
                    
                case 'libro_salarios':
                    // Requiere año y opcionalmente mes
                    $anio = isset($_POST['anio']) ? intval($_POST['anio']) : date('Y');
                    $mes = isset($_POST['mes']) ? intval($_POST['mes']) : 0;
                    $reporte = generarLibroSalarios($anio, $mes, $id_departamento);
                    break;
                    
                case 'recibos_pago':
                    // Requiere planilla o empleado específico
                    if ($id_planilla > 0) {
                        $reporte = generarRecibosPago($id_planilla, $id_empleado);
                    } else {
                        setFlashMessage('Debe seleccionar una planilla', 'danger');
                    }
                    break;
                    
                case 'constancia_trabajo':
                    // Requiere empleado específico
                    if ($id_empleado > 0) {
                        $reporte = generarConstanciaTrabajo($id_empleado);
                    } else {
                        setFlashMessage('Debe seleccionar un empleado', 'danger');
                    }
                    break;
                    
                case 'igss':
                    // Requiere período o planilla específica
                    if ($id_planilla > 0) {
                        $reporte = generarReporteIGSS($id_planilla);
                    } elseif ($id_periodo > 0) {
                        $reporte = generarReporteIGSSPorPeriodo($id_periodo, $id_departamento);
                    } else {
                        setFlashMessage('Debe seleccionar una planilla o un período', 'danger');
                    }
                    break;
                    
                case 'isr':
                    // Requiere año
                    $anio = isset($_POST['anio']) ? intval($_POST['anio']) : date('Y');
                    $reporte = generarReporteISR($anio, $id_departamento);
                    break;
            }
            
            // Si no se generó ningún reporte, mostrar mensaje
            if ($reporte === null) {
                setFlashMessage('No se pudo generar el reporte con los parámetros proporcionados', 'danger');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al generar el reporte: ' . $e->getMessage(), 'danger');
        }
    }
}

// Función para obtener planillas disponibles según el período seleccionado (para JavaScript)
try {
    $queryPlanillas = "SELECT id_planilla, CONCAT('Planilla #', id_planilla, ' - ', tipo_planilla) as nombre 
                     FROM planillas ORDER BY fecha_generacion DESC LIMIT 50";
    $stmtPlanillas = $db->prepare($queryPlanillas);
    $stmtPlanillas->execute();
    $planillas = $stmtPlanillas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Si hay error, mantener arreglo vacío
}

// Funciones para generar reportes
function generarReportePlanillaDetallada($id_planilla) {
    global $db, $empleados, $totales;
    
    try {
        // Obtener datos de la planilla
        $query = "SELECT p.*, 
                 DATE_FORMAT(p.fecha_generacion, '%d/%m/%Y') as fecha_formateada,
                 IFNULL(per.nombre, 'Sin período') as nombre_periodo,
                 IFNULL(d.nombre, 'Todos') as nombre_departamento
                 FROM planillas p 
                 LEFT JOIN periodos per ON p.id_periodo = per.id_periodo
                 LEFT JOIN departamentos d ON p.id_departamento = d.id_departamento
                 WHERE p.id_planilla = :id_planilla";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            setFlashMessage('La planilla especificada no existe', 'danger');
            return null;
        }
        
        $planilla = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener los detalles de la planilla
        $queryDetalles = "SELECT pd.*, 
                        e.nombres, e.apellidos, e.codigo_empleado, e.dpi,
                        d.nombre as departamento, p.nombre as puesto
                        FROM planilla_detalle pd
                        LEFT JOIN empleados e ON pd.id_empleado = e.id_empleado
                        LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                        LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
                        WHERE pd.id_planilla = :id_planilla
                        ORDER BY e.apellidos, e.nombres";
        
        $stmtDetalles = $db->prepare($queryDetalles);
        $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
        $stmtDetalles->execute();
        $empleados = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular totales
        foreach ($empleados as $empleado) {
            $totales['salario_base'] += $empleado['salario_base'];
            $totales['bonificaciones'] += $empleado['bonificaciones'];
            $totales['horas_extra'] += $empleado['horas_extra'];
            $totales['otras_percepciones'] += $empleado['otras_percepciones'];
            $totales['igss'] += $empleado['igss'];
            $totales['isr'] += $empleado['isr'];
            $totales['otras_deducciones'] += $empleado['otras_deducciones'];
            $totales['salario_liquido'] += $empleado['salario_liquido'];
        }
        
        return [
            'tipo' => 'planilla_detallada',
            'titulo' => 'Planilla Detallada',
            'subtitulo' => 'Planilla #' . $planilla['id_planilla'] . ' - ' . $planilla['nombre_periodo'],
            'planilla' => $planilla,
            'empleados' => $empleados,
            'totales' => $totales
        ];
    } catch (Exception $e) {
        setFlashMessage('Error al generar el reporte: ' . $e->getMessage(), 'danger');
        return null;
    }
}

function generarReportePlanillaPorPeriodo($id_periodo, $id_departamento) {
    global $db, $empleados, $totales;
    
    try {
        // Construir la consulta base
        $queryBase = "SELECT p.id_planilla FROM planillas p WHERE p.id_periodo = :id_periodo";
        
        // Agregar filtro por departamento si se especificó
        if ($id_departamento > 0) {
            $queryBase .= " AND p.id_departamento = :id_departamento";
        }
        
        $stmt = $db->prepare($queryBase);
        $stmt->bindParam(':id_periodo', $id_periodo, PDO::PARAM_INT);
        
        if ($id_departamento > 0) {
            $stmt->bindParam(':id_departamento', $id_departamento, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            setFlashMessage('No se encontraron planillas para los criterios seleccionados', 'danger');
            return null;
        }
        
        $planilla = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Usar la función existente para generar el reporte con el ID de la planilla
        return generarReportePlanillaDetallada($planilla['id_planilla']);
    } catch (Exception $e) {
        setFlashMessage('Error al generar el reporte: ' . $e->getMessage(), 'danger');
        return null;
    }
}

// Las demás funciones de reporte se implementarían de manera similar

function generarResumenPlanillaPorPeriodo($id_periodo, $id_departamento) {
    // Esta función generaría un resumen de planilla por período
    // Se implementaría similar a generarReportePlanillaPorPeriodo pero con diferente estructura de datos
    return [
        'tipo' => 'resumen_planilla',
        'titulo' => 'Resumen de Planilla',
        'subtitulo' => 'Período #' . $id_periodo,
        'mensaje' => 'Esta función se implementará próximamente'
    ];
}

function generarResumenPlanillaPorFechas($fecha_inicio, $fecha_fin, $id_departamento) {
    // Esta función generaría un resumen de planilla por rango de fechas
    return [
        'tipo' => 'resumen_planilla',
        'titulo' => 'Resumen de Planilla',
        'subtitulo' => 'Del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)),
        'mensaje' => 'Esta función se implementará próximamente'
    ];
}

function generarLibroSalarios($anio, $mes, $id_departamento) {
    // Esta función generaría el libro de salarios
    return [
        'tipo' => 'libro_salarios',
        'titulo' => 'Libro de Salarios',
        'subtitulo' => 'Año ' . $anio . ($mes > 0 ? ' - Mes ' . $mes : ''),
        'mensaje' => 'Esta función se implementará próximamente'
    ];
}

function generarRecibosPago($id_planilla, $id_empleado) {
    // Esta función generaría recibos de pago
    return [
        'tipo' => 'recibos_pago',
        'titulo' => 'Recibos de Pago',
        'subtitulo' => 'Planilla #' . $id_planilla,
        'mensaje' => 'Esta función se implementará próximamente'
    ];
}

function generarConstanciaTrabajo($id_empleado) {
    // Esta función generaría una constancia de trabajo
    return [
        'tipo' => 'constancia_trabajo',
        'titulo' => 'Constancia de Trabajo',
        'subtitulo' => 'Empleado #' . $id_empleado,
        'mensaje' => 'Esta función se implementará próximamente'
    ];
}

function generarReporteIGSS($id_planilla) {
    // Esta función generaría un reporte para el IGSS
    return [
        'tipo' => 'igss',
        'titulo' => 'Reporte para IGSS',
        'subtitulo' => 'Planilla #' . $id_planilla,
        'mensaje' => 'Esta función se implementará próximamente'
    ];
}

function generarReporteIGSSPorPeriodo($id_periodo, $id_departamento) {
    // Esta función generaría un reporte para el IGSS por período
    return [
        'tipo' => 'igss',
        'titulo' => 'Reporte para IGSS',
        'subtitulo' => 'Período #' . $id_periodo,
        'mensaje' => 'Esta función se implementará próximamente'
    ];
}

function generarReporteISR($anio, $id_departamento) {
    // Esta función generaría un reporte de ISR
    return [
        'tipo' => 'isr',
        'titulo' => 'Reporte de ISR',
        'subtitulo' => 'Año ' . $anio,
        'mensaje' => 'Esta función se implementará próximamente'
    ];
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-file-alt fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Genere diferentes tipos de reportes relacionados con planillas y empleados</p>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Parámetros del Reporte</h6>
                </div>
                <div class="card-body">
                    <form method="post" id="formReporte">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="tipo_reporte" class="form-label">Tipo de Reporte *</label>
                                <select class="form-select" id="tipo_reporte" name="tipo_reporte" required>
                                    <option value="">Seleccione un tipo de reporte</option>
                                    <?php foreach ($tiposReporte as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" 
                                                <?php echo (isset($_POST['tipo_reporte']) && $_POST['tipo_reporte'] == $key) ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 campo-filtro" id="campo-periodo">
                                <label for="id_periodo" class="form-label">Período</label>
                                <select class="form-select" id="id_periodo" name="id_periodo">
                                    <option value="">Seleccione un período</option>
                                    <?php foreach ($periodos as $periodo): ?>
                                        <option value="<?php echo $periodo['id_periodo']; ?>" 
                                                <?php echo (isset($_POST['id_periodo']) && $_POST['id_periodo'] == $periodo['id_periodo']) ? 'selected' : ''; ?>>
                                            <?php echo $periodo['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 campo-filtro" id="campo-planilla">
                                <label for="id_planilla" class="form-label">Planilla</label>
                                <select class="form-select" id="id_planilla" name="id_planilla">
                                    <option value="">Seleccione una planilla</option>
                                    <?php foreach ($planillas as $planilla): ?>
                                        <option value="<?php echo $planilla['id_planilla']; ?>" 
                                                <?php echo (isset($_POST['id_planilla']) && $_POST['id_planilla'] == $planilla['id_planilla']) ? 'selected' : ''; ?>>
                                            <?php echo $planilla['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 campo-filtro" id="campo-departamento">
                                <label for="id_departamento" class="form-label">Departamento</label>
                                <select class="form-select" id="id_departamento" name="id_departamento">
                                    <option value="">Todos los departamentos</option>
                                    <?php foreach ($departamentos as $departamento): ?>
                                        <option value="<?php echo $departamento['id_departamento']; ?>" 
                                                <?php echo (isset($_POST['id_departamento']) && $_POST['id_departamento'] == $departamento['id_departamento']) ? 'selected' : ''; ?>>
                                            <?php echo $departamento['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 campo-filtro" id="campo-empleado">
                                <label for="id_empleado" class="form-label">Empleado</label>
                                <select class="form-select" id="id_empleado" name="id_empleado">
                                    <option value="">Todos los empleados</option>
                                    <!-- Se llenaría con JavaScript o con un listado preexistente -->
                                </select>
                            </div>
                            
                            <div class="col-md-4 campo-filtro" id="campo-anio">
                                <label for="anio" class="form-label">Año</label>
                                <select class="form-select" id="anio" name="anio">
                                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php echo (isset($_POST['anio']) && $_POST['anio'] == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 campo-filtro" id="campo-mes">
                                <label for="mes" class="form-label">Mes</label>
                                <select class="form-select" id="mes" name="mes">
                                    <option value="">Todos los meses</option>
                                    <?php 
                                    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                             'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                    for ($i = 1; $i <= 12; $i++): 
                                    ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php echo (isset($_POST['mes']) && $_POST['mes'] == $i) ? 'selected' : ''; ?>>
                                            <?php echo $meses[$i-1]; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 campo-filtro" id="campo-fecha-inicio">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                       value="<?php echo isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : ''; ?>">
                            </div>
                            
                            <div class="col-md-4 campo-filtro" id="campo-fecha-fin">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                       value="<?php echo isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-file-alt fa-fw"></i> Generar Reporte
                                </button>
                                <?php if ($reporte): ?>
                                <button type="button" class="btn btn-info ms-2" onclick="window.print()">
                                    <i class="fas fa-print fa-fw"></i> Imprimir
                                </button>
                                <button type="button" class="btn btn-success ms-2" id="btnExportarExcel">
                                    <i class="fas fa-file-excel fa-fw"></i> Exportar a Excel
                                </button>
                                <button type="button" class="btn btn-danger ms-2" id="btnExportarPDF">
                                    <i class="fas fa-file-pdf fa-fw"></i> Exportar a PDF
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($reporte): ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo $reporte['titulo']; ?></h6>
                </div>
                <div class="card-body">
                    <h4 class="text-center mb-4"><?php echo $reporte['subtitulo']; ?></h4>
                    
                    <?php if (isset($reporte['mensaje'])): ?>
                        <div class="alert alert-info">
                            <?php echo $reporte['mensaje']; ?>
                        </div>
                    <?php else: ?>
                        <?php if ($reporte['tipo'] === 'planilla_detallada'): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="tablaReporte">
                                    <thead>
                                        <tr>
                                            <th>Empleado</th>
                                            <th>Departamento</th>
                                            <th class="text-end">Salario Base</th>
                                            <th class="text-end">Bonificaciones</th>
                                            <th class="text-end">Horas Extra</th>
                                            <th class="text-end">IGSS</th>
                                            <th class="text-end">ISR</th>
                                            <th class="text-end">Otras Deducciones</th>
                                            <th class="text-end">Líquido a Recibir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($empleados as $empleado): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $empleado['apellidos'] . ', ' . $empleado['nombres']; ?></strong><br>
                                                    <small>Código: <?php echo $empleado['codigo_empleado']; ?></small>
                                                </td>
                                                <td><?php echo $empleado['departamento']; ?><br>
                                                    <small><?php echo $empleado['puesto']; ?></small>
                                                </td>
                                                <td class="text-end"><?php echo formatMoney($empleado['salario_base']); ?></td>
                                                <td class="text-end"><?php echo formatMoney($empleado['bonificaciones']); ?></td>
                                                <td class="text-end"><?php echo formatMoney($empleado['horas_extra']); ?></td>
                                                <td class="text-end"><?php echo formatMoney($empleado['igss']); ?></td>
                                                <td class="text-end"><?php echo formatMoney($empleado['isr']); ?></td>
                                                <td class="text-end"><?php echo formatMoney($empleado['otras_deducciones']); ?></td>
                                                <td class="text-end fw-bold"><?php echo formatMoney($empleado['salario_liquido']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <th colspan="2" class="text-end">TOTALES:</th>
                                            <th class="text-end"><?php echo formatMoney($totales['salario_base']); ?></th>
                                            <th class="text-end"><?php echo formatMoney($totales['bonificaciones']); ?></th>
                                            <th class="text-end"><?php echo formatMoney($totales['horas_extra']); ?></th>
                                            <th class="text-end"><?php echo formatMoney($totales['igss']); ?></th>
                                            <th class="text-end"><?php echo formatMoney($totales['isr']); ?></th>
                                            <th class="text-end"><?php echo formatMoney($totales['otras_deducciones']); ?></th>
                                            <th class="text-end"><?php echo formatMoney($totales['salario_liquido']); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar campos según el tipo de reporte seleccionado
    const tipoReporte = document.getElementById('tipo_reporte');
    const camposFiltro = document.querySelectorAll('.campo-filtro');
    
    function actualizarCampos() {
        // Ocultar todos los campos
        camposFiltro.forEach(campo => {
            campo.style.display = 'none';
        });
        
        // Mostrar campos según el tipo de reporte
        switch (tipoReporte.value) {
            case 'planilla_detallada':
                document.getElementById('campo-planilla').style.display = 'block';
                document.getElementById('campo-periodo').style.display = 'block';
                document.getElementById('campo-departamento').style.display = 'block';
                break;
                
            case 'resumen_planilla':
                document.getElementById('campo-periodo').style.display = 'block';
                document.getElementById('campo-departamento').style.display = 'block';
                document.getElementById('campo-fecha-inicio').style.display = 'block';
                document.getElementById('campo-fecha-fin').style.display = 'block';
                break;
                
            case 'libro_salarios':
                document.getElementById('campo-anio').style.display = 'block';
                document.getElementById('campo-mes').style.display = 'block';
                document.getElementById('campo-departamento').style.display = 'block';
                break;
                
            case 'recibos_pago':
                document.getElementById('campo-planilla').style.display = 'block';
                document.getElementById('campo-empleado').style.display = 'block';
                break;
                
            case 'constancia_trabajo':
                document.getElementById('campo-empleado').style.display = 'block';
                break;
                
            case 'igss':
                document.getElementById('campo-planilla').style.display = 'block';
                document.getElementById('campo-periodo').style.display = 'block';
                document.getElementById('campo-departamento').style.display = 'block';
                break;
                
            case 'isr':
                document.getElementById('campo-anio').style.display = 'block';
                document.getElementById('campo-departamento').style.display = 'block';
                break;
        }
    }
    
    // Inicializar campos
    actualizarCampos();
    
    // Actualizar campos cuando cambia el tipo de reporte
    tipoReporte.addEventListener('change', actualizarCampos);
    
    // Exportar a Excel (simulado)
    const btnExportarExcel = document.getElementById('btnExportarExcel');
    if (btnExportarExcel) {
        btnExportarExcel.addEventListener('click', function() {
            alert('Esta funcionalidad se implementará próximamente');
        });
    }
    
    // Exportar a PDF (simulado)
    const btnExportarPDF = document.getElementById('btnExportarPDF');
    if (btnExportarPDF) {
        btnExportarPDF.addEventListener('click', function() {
            alert('Esta funcionalidad se implementará próximamente');
        });
    }
});
</script>

<style>
@media print {
    .card-header, .btn, form {
        display: none;
    }
    .container-fluid {
        width: 100%;
        margin: 0;
        padding: 0;
    }
    body {
        font-size: 12px;
    }
    h4 {
        font-size: 16px;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table th, .table td {
        padding: 4px;
        font-size: 11px;
    }
}
</style> 