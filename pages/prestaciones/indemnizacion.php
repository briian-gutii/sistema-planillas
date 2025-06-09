<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_CONTABILIDAD, ROL_RRHH])) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Indemnización';
$activeMenu = 'prestaciones';

// Obtener lista de empleados activos
$db = getDB();
$empleados = [];

try {
    $query = "SELECT e.id_empleado, e.codigo_empleado, e.nombres, e.apellidos, e.dpi, 
             e.fecha_ingreso, c.salario_base,
             d.nombre as departamento, p.nombre as puesto
             FROM empleados e
             JOIN contratos c ON e.id_empleado = c.id_empleado AND c.estado = 'Activo'
             LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
             LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
             WHERE e.estado = 'Activo'
             ORDER BY e.apellidos, e.nombres";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    setFlashMessage('Error al cargar los empleados: ' . $e->getMessage(), 'danger');
}

// Procesar cálculo de indemnización
$empleadoSeleccionado = null;
$datosCalculo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_empleado'])) {
    $id_empleado = intval($_POST['id_empleado']);
    $fecha_retiro = $_POST['fecha_retiro'] ?? date('Y-m-d');
    $motivo_retiro = $_POST['motivo_retiro'] ?? '';
    
    try {
        // Obtener datos del empleado
        $queryEmpleado = "SELECT e.id_empleado, e.codigo_empleado, e.nombres, e.apellidos, e.dpi, 
                         e.fecha_ingreso, c.salario_base,
                         d.nombre as departamento, p.nombre as puesto
                         FROM empleados e
                         JOIN contratos c ON e.id_empleado = c.id_empleado AND c.estado = 'Activo'
                         LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                         LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
                         WHERE e.id_empleado = :id_empleado";
        
        $stmtEmpleado = $db->prepare($queryEmpleado);
        $stmtEmpleado->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
        $stmtEmpleado->execute();
        
        if ($stmtEmpleado->rowCount() > 0) {
            $empleadoSeleccionado = $stmtEmpleado->fetch(PDO::FETCH_ASSOC);
            
            // Calcular tiempo de servicio
            $fechaIngreso = new DateTime($empleadoSeleccionado['fecha_ingreso']);
            $fechaRetiro = new DateTime($fecha_retiro);
            $intervalo = $fechaIngreso->diff($fechaRetiro);
            
            $anios = $intervalo->y;
            $meses = $intervalo->m;
            $dias = $intervalo->d;
            
            // Calcular salario promedio (último año)
            $salarioPromedio = $empleadoSeleccionado['salario_base'];
            
            // Calcular indemnización (un mes de salario por cada año de servicio)
            $indemnizacionAnios = $anios * $salarioPromedio;
            
            // Calcular la parte proporcional de meses y días
            $indemnizacionMeses = ($meses / 12) * $salarioPromedio;
            $indemnizacionDias = ($dias / 365) * $salarioPromedio;
            
            $totalIndemnizacion = $indemnizacionAnios + $indemnizacionMeses + $indemnizacionDias;
            
            // Preparar datos para mostrar
            $datosCalculo = [
                'id_empleado' => $id_empleado,
                'fecha_retiro' => $fecha_retiro,
                'motivo_retiro' => $motivo_retiro,
                'anios_servicio' => $anios,
                'meses_servicio' => $meses,
                'dias_servicio' => $dias,
                'salario_promedio' => $salarioPromedio,
                'indemnizacion_anios' => $indemnizacionAnios,
                'indemnizacion_meses' => $indemnizacionMeses,
                'indemnizacion_dias' => $indemnizacionDias,
                'total_indemnizacion' => $totalIndemnizacion
            ];
        }
    } catch (Exception $e) {
        setFlashMessage('Error al calcular la indemnización: ' . $e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-hand-holding-usd fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Cálculo de indemnización para empleados</p>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Cálculo de Indemnización</h6>
                </div>
                <div class="card-body">
                    <form method="post" id="formCalculoIndemnizacion">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="id_empleado" class="form-label">Empleado *</label>
                                <select class="form-select" id="id_empleado" name="id_empleado" required>
                                    <option value="">Seleccione un empleado</option>
                                    <?php foreach ($empleados as $empleado): ?>
                                        <option value="<?php echo $empleado['id_empleado']; ?>" 
                                                <?php echo (isset($_POST['id_empleado']) && $_POST['id_empleado'] == $empleado['id_empleado']) ? 'selected' : ''; ?>>
                                            <?php echo $empleado['apellidos'] . ', ' . $empleado['nombres'] . ' (' . $empleado['codigo_empleado'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_retiro" class="form-label">Fecha de Retiro *</label>
                                <input type="date" class="form-control" id="fecha_retiro" name="fecha_retiro" required
                                       value="<?php echo isset($_POST['fecha_retiro']) ? $_POST['fecha_retiro'] : date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="motivo_retiro" class="form-label">Motivo de Retiro</label>
                                <select class="form-select" id="motivo_retiro" name="motivo_retiro">
                                    <option value="Renuncia" <?php echo (isset($_POST['motivo_retiro']) && $_POST['motivo_retiro'] == 'Renuncia') ? 'selected' : ''; ?>>Renuncia</option>
                                    <option value="Despido justificado" <?php echo (isset($_POST['motivo_retiro']) && $_POST['motivo_retiro'] == 'Despido justificado') ? 'selected' : ''; ?>>Despido justificado</option>
                                    <option value="Despido injustificado" <?php echo (isset($_POST['motivo_retiro']) && $_POST['motivo_retiro'] == 'Despido injustificado') ? 'selected' : ''; ?>>Despido injustificado</option>
                                    <option value="Finalización de contrato" <?php echo (isset($_POST['motivo_retiro']) && $_POST['motivo_retiro'] == 'Finalización de contrato') ? 'selected' : ''; ?>>Finalización de contrato</option>
                                    <option value="Jubilación" <?php echo (isset($_POST['motivo_retiro']) && $_POST['motivo_retiro'] == 'Jubilación') ? 'selected' : ''; ?>>Jubilación</option>
                                    <option value="Otro" <?php echo (isset($_POST['motivo_retiro']) && $_POST['motivo_retiro'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-calculator fa-fw"></i> Calcular Indemnización
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($empleadoSeleccionado && $datosCalculo): ?>
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Datos del Empleado</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th width="35%">Código de Empleado:</th>
                                <td><?php echo $empleadoSeleccionado['codigo_empleado']; ?></td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo $empleadoSeleccionado['nombres'] . ' ' . $empleadoSeleccionado['apellidos']; ?></td>
                            </tr>
                            <tr>
                                <th>DPI:</th>
                                <td><?php echo $empleadoSeleccionado['dpi']; ?></td>
                            </tr>
                            <tr>
                                <th>Puesto:</th>
                                <td><?php echo $empleadoSeleccionado['puesto']; ?></td>
                            </tr>
                            <tr>
                                <th>Departamento:</th>
                                <td><?php echo $empleadoSeleccionado['departamento']; ?></td>
                            </tr>
                            <tr>
                                <th>Fecha de Ingreso:</th>
                                <td><?php echo date('d/m/Y', strtotime($empleadoSeleccionado['fecha_ingreso'])); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha de Retiro:</th>
                                <td><?php echo date('d/m/Y', strtotime($datosCalculo['fecha_retiro'])); ?></td>
                            </tr>
                            <tr>
                                <th>Motivo de Retiro:</th>
                                <td><?php echo $datosCalculo['motivo_retiro']; ?></td>
                            </tr>
                            <tr>
                                <th>Tiempo de Servicio:</th>
                                <td>
                                    <?php echo $datosCalculo['anios_servicio']; ?> años, 
                                    <?php echo $datosCalculo['meses_servicio']; ?> meses, 
                                    <?php echo $datosCalculo['dias_servicio']; ?> días
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Cálculo de Indemnización</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th width="60%">Salario Promedio Mensual:</th>
                                <td class="text-end"><?php echo formatMoney($datosCalculo['salario_promedio']); ?></td>
                            </tr>
                            <tr>
                                <th>Indemnización por Años (<?php echo $datosCalculo['anios_servicio']; ?> años):</th>
                                <td class="text-end"><?php echo formatMoney($datosCalculo['indemnizacion_anios']); ?></td>
                            </tr>
                            <tr>
                                <th>Indemnización por Meses (<?php echo $datosCalculo['meses_servicio']; ?> meses):</th>
                                <td class="text-end"><?php echo formatMoney($datosCalculo['indemnizacion_meses']); ?></td>
                            </tr>
                            <tr>
                                <th>Indemnización por Días (<?php echo $datosCalculo['dias_servicio']; ?> días):</th>
                                <td class="text-end"><?php echo formatMoney($datosCalculo['indemnizacion_dias']); ?></td>
                            </tr>
                            <tr class="table-primary">
                                <th>Total Indemnización:</th>
                                <td class="text-end fw-bold"><?php echo formatMoney($datosCalculo['total_indemnizacion']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button onclick="window.print()" class="btn btn-info btn-sm">
                            <i class="fas fa-print fa-fw"></i> Imprimir
                        </button>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalGenerarPago">
                            <i class="fas fa-check fa-fw"></i> Generar Pago
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para Generar Pago -->
<?php if ($empleadoSeleccionado && $datosCalculo): ?>
<div class="modal fade" id="modalGenerarPago" tabindex="-1" aria-labelledby="modalGenerarPagoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGenerarPagoLabel">Generar Pago de Indemnización</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea generar el pago de indemnización para <strong><?php echo $empleadoSeleccionado['nombres'] . ' ' . $empleadoSeleccionado['apellidos']; ?></strong>?</p>
                <p>El monto total a pagar es: <strong><?php echo formatMoney($datosCalculo['total_indemnizacion']); ?></strong></p>
                
                <form id="formGenerarPago" method="post" action="<?php echo BASE_URL; ?>?page=prestaciones/procesar_indemnizacion">
                    <input type="hidden" name="id_empleado" value="<?php echo $empleadoSeleccionado['id_empleado']; ?>">
                    <input type="hidden" name="fecha_retiro" value="<?php echo $datosCalculo['fecha_retiro']; ?>">
                    <input type="hidden" name="motivo_retiro" value="<?php echo $datosCalculo['motivo_retiro']; ?>">
                    <input type="hidden" name="monto_indemnizacion" value="<?php echo $datosCalculo['total_indemnizacion']; ?>">
                    
                    <div class="mb-3">
                        <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                        <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="forma_pago" class="form-label">Forma de Pago</label>
                        <select class="form-select" id="forma_pago" name="forma_pago" required>
                            <option value="Transferencia">Transferencia bancaria</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Efectivo">Efectivo</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="referencia_pago" class="form-label">Referencia de Pago</label>
                        <input type="text" class="form-control" id="referencia_pago" name="referencia_pago" placeholder="Número de cheque o transferencia">
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formGenerarPago" class="btn btn-success">Generar Pago</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Al seleccionar un empleado, mostrar su fecha de ingreso
    const selectEmpleado = document.getElementById('id_empleado');
    if (selectEmpleado) {
        selectEmpleado.addEventListener('change', function() {
            // Aquí podríamos cargar datos del empleado vía AJAX si fuera necesario
        });
    }
    
    // Preparar la página para impresión
    const btnImprimir = document.querySelector('.btn-info');
    if (btnImprimir) {
        btnImprimir.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    }
});
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .card, .card * {
        visibility: visible;
    }
    .card {
        position: absolute;
        left: 0;
        top: 0;
    }
    .card-footer, .btn, .modal {
        display: none !important;
    }
}
</style> 