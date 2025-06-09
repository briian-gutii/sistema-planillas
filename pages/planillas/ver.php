<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Detalle de Planilla';
$activeMenu = 'planillas';

// Obtener el ID de la planilla
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_planilla <= 0) {
    setFlashMessage('Planilla no especificada', 'danger');
    header('Location: ' . BASE_URL . '?page=planillas/lista');
    exit;
}

// Obtener datos de la planilla
$db = getDB();
$planilla = [];
$detalles = [];
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

try {
    // Verificar si existe la planilla
    $query = "SELECT p.*, 
             DATE_FORMAT(p.fecha_generacion, '%d/%m/%Y') as fecha_formateada,
             CONCAT('ID Periodo: ', p.id_periodo) as nombre_periodo_generado,
             'N/A' as nombre_departamento_generado
             FROM Planillas p 
             WHERE p.id_planilla = :id_planilla";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('La planilla especificada no existe', 'danger');
        header('Location: ' . BASE_URL . '?page=planillas/lista');
        exit;
    }
    
    $planilla = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($planilla) {
        $planilla['nombre_periodo'] = $planilla['nombre_periodo_generado'] ?? 'Sin período';
        $planilla['nombre_departamento'] = $planilla['nombre_departamento_generado'] ?? 'Todos';
    }
    
    // Obtener los detalles de la planilla
    $queryDetalles = "SELECT pd.*, 
                e.*,
                d.nombre as departamento,
                p.nombre as puesto
                FROM Detalle_Planilla pd
                LEFT JOIN empleados e ON pd.id_empleado = e.id_empleado
                LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
                WHERE pd.id_planilla = :id_planilla";
    $stmtDetalles = $db->prepare($queryDetalles);
    $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtDetalles->execute();
    $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular totales
    foreach ($detalles as $detalle) {
        $totales['salario_base'] += $detalle['salario_base'] ?? 0;
        $totales['bonificaciones'] += ($detalle['bonificacion_incentivo'] ?? 0) + ($detalle['bonificaciones_adicionales'] ?? 0);
        $totales['horas_extra'] += $detalle['monto_horas_extra'] ?? 0;
        $totales['otras_percepciones'] += $detalle['comisiones'] ?? 0;
        $totales['igss'] += $detalle['igss_laboral'] ?? 0;
        $totales['isr'] += $detalle['isr_retenido'] ?? 0;
        $totales['otras_deducciones'] += ($detalle['otras_deducciones'] ?? 0) + ($detalle['anticipos'] ?? 0) +
                                        ($detalle['prestamos'] ?? 0) + ($detalle['descuentos_judiciales'] ?? 0);
        $totales['salario_liquido'] += $detalle['liquido_recibir'] ?? 0;
    }
    
} catch (Exception $e) {
    setFlashMessage('Error al cargar los datos: ' . $e->getMessage(), 'danger');
}

// Obtener color del badge según el estado
$estadoBadge = 'secondary';
switch ($planilla['estado'] ?? 'Desconocido') {
    case 'Borrador':
        $estadoBadge = 'warning';
        break;
    case 'Aprobada':
        $estadoBadge = 'success';
        break;
    case 'Pagada':
        $estadoBadge = 'primary';
        break;
    case 'Anulada':
        $estadoBadge = 'danger';
        break;
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-file-invoice-dollar fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Información detallada de la planilla</p>
    
    <!-- Resumen de Planilla -->
    <div class="row">
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Planilla #<?php echo $planilla['id_planilla'] ?? 'N/A'; ?> - <?php 
                // Obtener nombre del tipo de planilla
                if (isset($planilla['id_tipo_planilla'])) {
                    try {
                        $tipoQuery = $db->prepare("SELECT nombre FROM tipo_planilla WHERE id_tipo_planilla = :id");
                        $tipoQuery->bindParam(':id', $planilla['id_tipo_planilla'], PDO::PARAM_INT);
                        $tipoQuery->execute();
                        $tipoData = $tipoQuery->fetch(PDO::FETCH_ASSOC);
                        echo $tipoData ? $tipoData['nombre'] : 'N/A';
                    } catch (Exception $e) {
                        echo 'N/A';
                    }
                } else {
                    echo 'N/A';
                }
            ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $planilla['nombre_periodo'] ?? 'Sin período'; ?> - <?php echo $planilla['nombre_departamento'] ?? 'Todos'; ?>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-<?php echo $estadoBadge; ?>"><?php echo $planilla['estado'] ?? 'Desconocido'; ?></span>
                                <span class="ms-2">
                                    <i class="fas fa-calendar-alt fa-fw"></i> <?php echo $planilla['fecha_formateada'] ?? 'N/A'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=planillas/lista" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-fw"></i> Volver a la Lista
        </a>
        <?php if (($planilla['estado'] ?? '') == 'Borrador'): ?>
            <a href="<?php echo BASE_URL; ?>?page=planillas/editar&id=<?php echo $id_planilla; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-edit fa-fw"></i> Editar
            </a>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAprobarPlanilla">
                <i class="fas fa-check fa-fw"></i> Aprobar
            </button>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalAnularPlanilla">
                <i class="fas fa-times fa-fw"></i> Anular
            </button>
        <?php elseif (($planilla['estado'] ?? '') == 'Aprobada'): ?>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPagarPlanilla">
                <i class="fas fa-dollar-sign fa-fw"></i> Marcar como Pagada
            </button>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>?page=planillas/imprimir&id=<?php echo $id_planilla; ?>" class="btn btn-info btn-sm" target="_blank">
            <i class="fas fa-print fa-fw"></i> Imprimir
        </a>
        <?php if (($planilla['estado'] ?? '') == 'Pagada'): ?>
            <a href="<?php echo BASE_URL; ?>?page=planillas/reporte&id=<?php echo $id_planilla; ?>" class="btn btn-warning btn-sm" target="_blank">
                <i class="fas fa-file-pdf fa-fw"></i> Generar Reporte
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Resumen de totales -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Empleados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo count($detalles); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                Total Sueldos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatMoney($totales['salario_base']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Deducciones
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatMoney($totales['igss'] + $totales['isr'] + $totales['otras_deducciones']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-minus-circle fa-2x text-gray-300"></i>
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
                                Total Líquido
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatMoney($totales['salario_liquido']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla de Detalles -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detalles de la Planilla</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="detallesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Departamento</th>
                            <th>Salario Base</th>
                            <th>Bonificaciones</th>
                            <th>Horas Extra</th>
                            <th>IGSS</th>
                            <th>ISR</th>
                            <th>Otras Deducciones</th>
                            <th>Líquido a Recibir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($detalles) > 0): ?>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td>
                                        <strong>
                                        <?php 
                                        // Handle employee name display flexibly
                                        $name = '';
                                        
                                        // Try different column combinations
                                        if (isset($detalle['primer_apellido']) && isset($detalle['primer_nombre'])) {
                                            $name = $detalle['primer_apellido'];
                                            if (isset($detalle['segundo_apellido']) && !empty($detalle['segundo_apellido'])) {
                                                $name .= ' ' . $detalle['segundo_apellido'];
                                            }
                                            $name .= ', ' . $detalle['primer_nombre'];
                                            if (isset($detalle['segundo_nombre']) && !empty($detalle['segundo_nombre'])) {
                                                $name .= ' ' . $detalle['segundo_nombre'];
                                            }
                                        } else if (isset($detalle['apellidos']) && isset($detalle['nombres'])) {
                                            $name = $detalle['apellidos'] . ', ' . $detalle['nombres'];
                                        } else {
                                            // Try to construct a name from whatever is available
                                            foreach ($detalle as $key => $value) {
                                                if (strpos(strtolower($key), 'nombre') !== false || 
                                                    strpos(strtolower($key), 'apellido') !== false) {
                                                    if (!empty($name)) $name .= ' ';
                                                    $name .= $value;
                                                }
                                            }
                                            if (empty($name)) $name = 'N/A';
                                        }
                                        
                                        echo $name;
                                        ?>
                                        </strong><br>
                                        <small>
                                        <?php
                                        // Try to find identification field
                                        $id_shown = false;
                                        foreach(['DPI', 'dpi', 'NIT', 'nit', 'numero_IGSS'] as $idField) {
                                            if (isset($detalle[$idField]) && !empty($detalle[$idField])) {
                                                echo $idField . ': ' . $detalle[$idField];
                                                $id_shown = true;
                                                break;
                                            }
                                        }
                                        if (!$id_shown) echo 'ID: N/A';
                                        ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php 
                                        // Try to find department information
                                        $departamento = '';
                                        $puesto = '';
                                        
                                        // Check if we have departamento from a join
                                        if (isset($detalle['departamento'])) {
                                            $departamento = $detalle['departamento'];
                                        } 
                                        // Try to find department in employee data
                                        else {
                                            foreach ($detalle as $key => $value) {
                                                if (stripos($key, 'departamento') !== false) {
                                                    $departamento = $value;
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // Check if we have puesto from a join
                                        if (isset($detalle['puesto'])) {
                                            $puesto = $detalle['puesto'];
                                        }
                                        // Try to find position in employee data
                                        else {
                                            foreach ($detalle as $key => $value) {
                                                if (stripos($key, 'puesto') !== false) {
                                                    $puesto = $value;
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        echo (!empty($departamento) ? htmlspecialchars($departamento) : 'Sin asignar');
                                        
                                        if (!empty($puesto)) {
                                            echo '<br><small>' . htmlspecialchars($puesto) . '</small>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end"><?php echo formatMoney($detalle['salario_base'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo formatMoney(($detalle['bonificacion_incentivo'] ?? 0) + ($detalle['bonificaciones_adicionales'] ?? 0)); ?></td>
                                    <td class="text-end"><?php echo formatMoney($detalle['monto_horas_extra'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo formatMoney($detalle['igss_laboral'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo formatMoney($detalle['isr_retenido'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo formatMoney(($detalle['otras_deducciones'] ?? 0) + ($detalle['anticipos'] ?? 0) +
                                                                            ($detalle['prestamos'] ?? 0) + ($detalle['descuentos_judiciales'] ?? 0)); ?></td>
                                    <td class="text-end fw-bold"><?php echo formatMoney($detalle['liquido_recibir'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No hay detalles para esta planilla</td>
                            </tr>
                        <?php endif; ?>
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
        </div>
    </div>
    
    <?php if (!empty($planilla['observaciones'] ?? '')): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Observaciones</h6>
        </div>
        <div class="card-body">
            <?php echo nl2br(htmlspecialchars($planilla['observaciones'] ?? '')); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (($planilla['estado'] ?? '') == 'Pagada' && !empty($planilla['fecha_pago'] ?? '')): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Datos de Pago</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Fecha de Pago:</strong> <?php echo date('d/m/Y', strtotime($planilla['fecha_pago'] ?? date('Y-m-d'))); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Referencia de Pago:</strong> <?php echo !empty($planilla['referencia_pago'] ?? '') ? $planilla['referencia_pago'] : 'N/A'; ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modales de confirmación -->
<!-- Modal Aprobar Planilla -->
<div class="modal fade" id="modalAprobarPlanilla" tabindex="-1" aria-labelledby="modalAprobarPlanillaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAprobarPlanillaLabel">Confirmar Aprobación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea aprobar esta planilla? Una vez aprobada, no podrá editar los detalles.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" action="<?php echo BASE_URL; ?>?page=planillas/aprobar">
                    <input type="hidden" name="id_planilla" value="<?php echo $id_planilla; ?>">
                    <button type="submit" class="btn btn-success">Aprobar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Anular Planilla -->
<div class="modal fade" id="modalAnularPlanilla" tabindex="-1" aria-labelledby="modalAnularPlanillaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAnularPlanillaLabel">Confirmar Anulación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea anular esta planilla? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" action="<?php echo BASE_URL; ?>?page=planillas/anular">
                    <input type="hidden" name="id_planilla" value="<?php echo $id_planilla; ?>">
                    <button type="submit" class="btn btn-danger">Anular</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pagar Planilla -->
<div class="modal fade" id="modalPagarPlanilla" tabindex="-1" aria-labelledby="modalPagarPlanillaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPagarPlanillaLabel">Confirmar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea marcar esta planilla como pagada?</p>
                <form id="formPagarPlanilla">
                    <div class="mb-3">
                        <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                        <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" required>
                    </div>
                    <div class="mb-3">
                        <label for="referencia_pago" class="form-label">Referencia de Pago</label>
                        <input type="text" class="form-control" id="referencia_pago" name="referencia_pago" placeholder="Ej: Transferencia #123456">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" action="<?php echo BASE_URL; ?>?page=planillas/pagar">
                    <input type="hidden" name="id_planilla" value="<?php echo $id_planilla; ?>">
                    <input type="hidden" name="fecha_pago" id="hiddenFechaPago" value="">
                    <input type="hidden" name="referencia_pago" id="hiddenReferenciaPago" value="">
                    <button type="submit" class="btn btn-primary">Registrar Pago</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para tabla sin DataTables */
#detallesTable {
    width: 100% !important;
    margin-bottom: 1rem;
    border-collapse: collapse;
}

#detallesTable th, 
#detallesTable td {
    padding: 0.75rem;
    vertical-align: middle;
}

#detallesTable thead th {
    background-color: #f8f9fc;
    border-bottom: 2px solid #e3e6f0;
    font-weight: bold;
    text-align: left;
}

#detallesTable tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

/* Estilos para buscador */
input[type="text"].form-control {
    width: 300px;
    margin-bottom: 15px;
    float: right;
}

/* Asegurar que valores monetarios estén alineados a la derecha */
.text-end {
    text-align: right !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Implementación simple para tabla de detalles
    const tabla = document.getElementById('detallesTable');
    if (tabla) {
        // Añadir campo de búsqueda
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Buscar empleados...';
        searchInput.className = 'form-control form-control-sm mb-3';
        tabla.parentNode.insertBefore(searchInput, tabla);
        
        // Filas de la tabla
        const filas = tabla.querySelectorAll('tbody tr');
        
        // Añadir evento de búsqueda
        searchInput.addEventListener('keyup', function() {
            const texto = this.value.toLowerCase();
            
            filas.forEach(function(fila) {
                const contenido = fila.textContent.toLowerCase();
                if (contenido.indexOf(texto) > -1) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });
    }
    
    // Establecer fecha actual por defecto para el pago
    const fechaPagoInput = document.getElementById('fecha_pago');
    if (fechaPagoInput) {
        fechaPagoInput.valueAsDate = new Date();
    }
    
    // Transferir valores al enviar el formulario
    const formPagarPlanilla = document.querySelector('form[action*="planillas/pagar"]');
    if (formPagarPlanilla) {
        formPagarPlanilla.addEventListener('submit', function(e) {
            document.getElementById('hiddenFechaPago').value = document.getElementById('fecha_pago').value;
            document.getElementById('hiddenReferenciaPago').value = document.getElementById('referencia_pago').value;
        });
    }
});
</script> 