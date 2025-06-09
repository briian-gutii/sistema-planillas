<?php

// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Editar Planilla';
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

try {
    // Verificar si existe la planilla y si está en estado borrador
    $query = "SELECT p.*, 
             DATE_FORMAT(p.fecha_generacion, '%d/%m/%Y') as fecha_formateada,
             CONCAT('ID Periodo: ', p.id_periodo) as nombre_periodo,
             'Todos' as nombre_departamento
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
    
    // Verificar si la planilla está en estado borrador
    if ($planilla['estado'] != 'Borrador') {
        setFlashMessage('Solo se pueden editar planillas en estado borrador', 'warning');
        header('Location: ' . BASE_URL . '?page=planillas/ver&id=' . $id_planilla);
        exit;
    }
    
    // Obtener los detalles de la planilla
    $queryDetalles = "SELECT pd.*, 
                e.*,
                d.nombre as departamento,
                p.nombre as puesto
                FROM Detalle_Planilla pd
                LEFT JOIN Empleados e ON pd.id_empleado = e.id_empleado
                LEFT JOIN Departamentos d ON e.id_departamento = d.id_departamento
                LEFT JOIN Puestos p ON e.id_puesto = p.id_puesto
                WHERE pd.id_planilla = :id_planilla
                ORDER BY e.primer_apellido, e.primer_nombre";
    
    $stmtDetalles = $db->prepare($queryDetalles);
    $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtDetalles->execute();
    $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    setFlashMessage('Error al cargar los datos: ' . $e->getMessage(), 'danger');
    // Para desarrollo: Mostrar error SQL directamente
    echo '<div class="alert alert-danger">Error SQL: ' . $e->getMessage() . '</div>';
}

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $observaciones = trim($_POST['observaciones'] ?? '');
    $errores = [];
    
    // Procesar cambios en los detalles
    $actualizados = 0;
    
    foreach ($detalles as $detalle) {
        $id_detalle = $detalle['id_detalle'];
        
        // Capturar los valores actualizados
        $salario_base = isset($_POST['salario_base'][$id_detalle]) ? floatval($_POST['salario_base'][$id_detalle]) : $detalle['salario_base'];
        $bonificaciones = isset($_POST['bonificaciones'][$id_detalle]) ? floatval($_POST['bonificaciones'][$id_detalle]) : $detalle['bonificacion_incentivo'];
        $horas_extra = isset($_POST['horas_extra'][$id_detalle]) ? floatval($_POST['horas_extra'][$id_detalle]) : $detalle['monto_horas_extra'];
        $otras_percepciones = isset($_POST['otras_percepciones'][$id_detalle]) ? floatval($_POST['otras_percepciones'][$id_detalle]) : $detalle['comisiones'];
        $otras_deducciones = isset($_POST['otras_deducciones'][$id_detalle]) ? floatval($_POST['otras_deducciones'][$id_detalle]) : $detalle['otras_deducciones'];
        
        // Calcular valores que dependen de otros
        $igss = $salario_base * 0.0483; // 4.83% de IGSS
        $total_percepciones = $salario_base + $bonificaciones + $horas_extra + $otras_percepciones;
        $total_deducciones = $igss + $otras_deducciones;
        $salario_liquido = $total_percepciones - $total_deducciones;
        
        try {
            // Actualizar el detalle
            $queryUpdate = "UPDATE Detalle_Planilla
                          SET salario_base = :salario_base,
                              bonificacion_incentivo = :bonificaciones,
                              monto_horas_extra = :horas_extra,
                              comisiones = :otras_percepciones,
                              igss_laboral = :igss,
                              otras_deducciones = :otras_deducciones,
                              liquido_recibir = :salario_liquido
                          WHERE id_detalle = :id_detalle";
            
            $stmtUpdate = $db->prepare($queryUpdate);
            $stmtUpdate->bindParam(':salario_base', $salario_base);
            $stmtUpdate->bindParam(':bonificaciones', $bonificaciones);
            $stmtUpdate->bindParam(':horas_extra', $horas_extra);
            $stmtUpdate->bindParam(':otras_percepciones', $otras_percepciones);
            $stmtUpdate->bindParam(':igss', $igss);
            $stmtUpdate->bindParam(':otras_deducciones', $otras_deducciones);
            $stmtUpdate->bindParam(':salario_liquido', $salario_liquido);
            $stmtUpdate->bindParam(':id_detalle', $id_detalle);
            
            if ($stmtUpdate->execute()) {
                $actualizados++;
            }
        } catch (Exception $e) {
            $errores[] = 'Error al actualizar el empleado ' . $detalle['nombres'] . ' ' . $detalle['apellidos'] . ': ' . $e->getMessage();
        }
    }
    
    // Actualizar observaciones en la planilla
    try {
        $queryUpdatePlanilla = "UPDATE Planillas SET observaciones = :observaciones WHERE id_planilla = :id_planilla";
        $stmtUpdatePlanilla = $db->prepare($queryUpdatePlanilla);
        $stmtUpdatePlanilla->bindParam(':observaciones', $observaciones);
        $stmtUpdatePlanilla->bindParam(':id_planilla', $id_planilla);
        $stmtUpdatePlanilla->execute();
    } catch (Exception $e) {
        $errores[] = 'Error al actualizar las observaciones: ' . $e->getMessage();
    }
    
    // Mostrar resultados
    if (empty($errores)) {
        setFlashMessage('Planilla actualizada correctamente. Se actualizaron ' . $actualizados . ' registro(s).', 'success');
        header('Location: ' . BASE_URL . '?page=planillas/ver&id=' . $id_planilla);
        exit;
    } else {
        foreach ($errores as $error) {
            setFlashMessage($error, 'danger');
        }
    }
    
    // Recargar datos actualizados
    try {
        $stmtDetalles->execute();
        $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        setFlashMessage('Error al recargar los datos: ' . $e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-edit fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Modifique los datos de la planilla</p>
    
    <!-- Información de la planilla -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Información General</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p><strong>ID Planilla:</strong> <?php echo htmlspecialchars($planilla['id_planilla'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Período:</strong> <?php echo htmlspecialchars($planilla['nombre_periodo'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Tipo:</strong> <?php 
                // Obtener nombre del tipo de planilla
                if (isset($planilla['id_tipo_planilla'])) {
                    try {
                        $tipoQuery = $db->prepare("SELECT nombre FROM tipo_planilla WHERE id_tipo_planilla = :id");
                        $tipoQuery->bindParam(':id', $planilla['id_tipo_planilla'], PDO::PARAM_INT);
                        $tipoQuery->execute();
                        $tipoData = $tipoQuery->fetch(PDO::FETCH_ASSOC);
                        echo htmlspecialchars($tipoData ? $tipoData['nombre'] : 'N/A');
                    } catch (Exception $e) {
                        echo htmlspecialchars('N/A');
                    }
                } else {
                    echo htmlspecialchars('N/A');
                }
            ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Departamento:</strong> <?php echo htmlspecialchars($planilla['nombre_departamento'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <form method="post" id="formEditarPlanilla">
        <!-- Tabla de Detalles Editables -->
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
                                <th>Otras Percepciones</th>
                                <th>IGSS (4.83%)</th>
                                <th>Otras Deducciones</th>
                                <th>Líquido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($detalles) > 0): ?>
                                <?php foreach ($detalles as $detalle): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($detalle['primer_apellido'] ?? '') . ', ' . htmlspecialchars($detalle['primer_nombre'] ?? ''); ?></strong><br>
                                            <small>DPI: <?php echo htmlspecialchars($detalle['DPI'] ?? ''); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($detalle['departamento'] ?? ''); ?><br>
                                            <small><?php echo htmlspecialchars($detalle['puesto'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm salario-base" 
                                                   name="salario_base[<?php echo $detalle['id_detalle']; ?>]" 
                                                   value="<?php echo $detalle['salario_base'] ?? 0; ?>" 
                                                   step="0.01" min="0" 
                                                   data-id="<?php echo $detalle['id_detalle']; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm bonificaciones" 
                                                   name="bonificaciones[<?php echo $detalle['id_detalle']; ?>]" 
                                                   value="<?php echo $detalle['bonificacion_incentivo'] ?? 0; ?>" 
                                                   step="0.01" min="0"
                                                   data-id="<?php echo $detalle['id_detalle']; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm horas-extra" 
                                                   name="horas_extra[<?php echo $detalle['id_detalle']; ?>]" 
                                                   value="<?php echo $detalle['monto_horas_extra'] ?? 0; ?>" 
                                                   step="0.01" min="0"
                                                   data-id="<?php echo $detalle['id_detalle']; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm otras-percepciones" 
                                                   name="otras_percepciones[<?php echo $detalle['id_detalle']; ?>]" 
                                                   value="<?php echo $detalle['comisiones'] ?? 0; ?>" 
                                                   step="0.01" min="0"
                                                   data-id="<?php echo $detalle['id_detalle']; ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm igss" 
                                                   value="<?php echo number_format($detalle['igss_laboral'] ?? 0, 2); ?>" 
                                                   readonly
                                                   data-id="<?php echo $detalle['id_detalle']; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm otras-deducciones" 
                                                   name="otras_deducciones[<?php echo $detalle['id_detalle']; ?>]" 
                                                   value="<?php echo $detalle['otras_deducciones'] ?? 0; ?>" 
                                                   step="0.01" min="0"
                                                   data-id="<?php echo $detalle['id_detalle']; ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm salario-liquido fw-bold" 
                                                   value="<?php echo number_format($detalle['liquido_recibir'] ?? 0, 2); ?>" 
                                                   readonly
                                                   data-id="<?php echo $detalle['id_detalle']; ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No hay detalles para esta planilla</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Observaciones -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Observaciones</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Ingrese observaciones sobre esta planilla"><?php echo htmlspecialchars($planilla['observaciones'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div class="mb-4">
            <a href="<?php echo BASE_URL; ?>?page=planillas/ver&id=<?php echo $id_planilla; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left fa-fw"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save fa-fw"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables
    /* Descomentar cuando se resuelva el problema de carga de datos y columnas
    $('#detallesTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        },
        responsive: true,
        ordering: true,
        paging: true,
        searching: true,
        info: true
    });
    */
    
    // Función para calcular el IGSS
    function calcularIGSS(salarioBase) {
        return salarioBase * 0.0483; // 4.83% de IGSS
    }
    
    // Función para calcular el salario líquido
    function calcularSalarioLiquido(id) {
        const salarioBase = parseFloat(document.querySelector(`.salario-base[data-id="${id}"]`).value) || 0;
        const bonificaciones = parseFloat(document.querySelector(`.bonificaciones[data-id="${id}"]`).value) || 0;
        const horasExtra = parseFloat(document.querySelector(`.horas-extra[data-id="${id}"]`).value) || 0;
        const otrasPercepciones = parseFloat(document.querySelector(`.otras-percepciones[data-id="${id}"]`).value) || 0;
        const otrasDeducciones = parseFloat(document.querySelector(`.otras-deducciones[data-id="${id}"]`).value) || 0;
        
        const igss = calcularIGSS(salarioBase);
        const totalPercepciones = salarioBase + bonificaciones + horasExtra + otrasPercepciones;
        const totalDeducciones = igss + otrasDeducciones;
        const salarioLiquido = totalPercepciones - totalDeducciones;
        
        // Actualizar campos
        document.querySelector(`.igss[data-id="${id}"]`).value = igss.toFixed(2);
        document.querySelector(`.salario-liquido[data-id="${id}"]`).value = salarioLiquido.toFixed(2);
    }
    
    // Asignar eventos a los inputs
    document.querySelectorAll('.salario-base, .bonificaciones, .horas-extra, .otras-percepciones, .otras-deducciones').forEach(input => {
        input.addEventListener('change', function() {
            const id = this.getAttribute('data-id');
            calcularSalarioLiquido(id);
        });
        
        input.addEventListener('keyup', function() {
            const id = this.getAttribute('data-id');
            calcularSalarioLiquido(id);
        });
    });
    
    // Validación del formulario
    const formEditarPlanilla = document.getElementById('formEditarPlanilla');
    if (formEditarPlanilla) {
        formEditarPlanilla.addEventListener('submit', function(event) {
            let isValid = true;
            const inputs = document.querySelectorAll('input[type="number"]');
            
            inputs.forEach(input => {
                if (parseFloat(input.value) < 0) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                alert('Por favor, corrija los valores negativos antes de continuar.');
            }
        });
    }
});
</script> 