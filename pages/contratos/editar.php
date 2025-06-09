<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Editar Contrato';
$activeMenu = 'empleados';

// Verificar si se proporciona un ID de contrato
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('ID de contrato no proporcionado', 'danger');
    header('Location: ' . BASE_URL . '?page=contratos/lista');
    exit;
}

$id_contrato = filter_var($_GET['id'], FILTER_VALIDATE_INT);

// Obtener datos del contrato
try {
    $db = getDB();
    $query = "SELECT * FROM contratos WHERE id_contrato = :id_contrato";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_contrato', $id_contrato, PDO::PARAM_INT);
    $stmt->execute();
    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contrato) {
        setFlashMessage('Contrato no encontrado', 'danger');
        header('Location: ' . BASE_URL . '?page=contratos/lista');
        exit;
    }
    
    // Verificar que el contrato esté activo
    if (($contrato['estado'] ?? 0) == 0) {
        setFlashMessage('No se puede editar un contrato finalizado. Si desea modificarlo, primero debe reactivarlo.', 'warning');
        header('Location: ' . BASE_URL . '?page=contratos/ver&id=' . $id_contrato);
        exit;
    }
    
} catch (Exception $e) {
    setFlashMessage('Error al obtener datos del contrato: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '?page=contratos/lista');
    exit;
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_puesto = isset($_POST['id_puesto']) ? intval($_POST['id_puesto']) : 0;
    $tipo_contrato = isset($_POST['tipo_contrato']) ? $_POST['tipo_contrato'] : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) && !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
    $salario = isset($_POST['salario']) ? floatval($_POST['salario']) : 0;
    $jornada = isset($_POST['jornada']) ? $_POST['jornada'] : '';
    $horas_semanales = isset($_POST['horas_semanales']) ? intval($_POST['horas_semanales']) : 0;
    $bonificacion_incentivo = isset($_POST['bonificacion_incentivo']) ? floatval($_POST['bonificacion_incentivo']) : 0;
    $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';
    
    // Validación básica
    $errores = [];
    
    if ($id_puesto <= 0) {
        $errores[] = 'Debe seleccionar un puesto válido.';
    }
    
    if (empty($tipo_contrato)) {
        $errores[] = 'Debe seleccionar un tipo de contrato.';
    }
    
    if (empty($fecha_inicio)) {
        $errores[] = 'Debe ingresar una fecha de inicio válida.';
    }
    
    if ($salario <= 0) {
        $errores[] = 'El salario debe ser mayor a cero.';
    }
    
    if (empty($jornada)) {
        $errores[] = 'Debe seleccionar una jornada laboral.';
    }
    
    if ($horas_semanales <= 0) {
        $errores[] = 'Las horas semanales deben ser mayores a cero.';
    }
    
    // Si no hay errores, actualizar el contrato
    if (empty($errores)) {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            // Verificar si el salario cambió para registrar el historial
            if ($contrato['salario'] != $salario) {
                // Guardar el historial de salarios
                $query = "INSERT INTO historial_salarios (id_empleado, fecha_cambio, salario_anterior, 
                          salario_nuevo, motivo, usuario_registro)
                          VALUES (:id_empleado, NOW(), :salario_anterior, :salario_nuevo, 
                          'Actualización de contrato', :usuario_registro)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_empleado', $contrato['id_empleado'], PDO::PARAM_INT);
                $stmt->bindParam(':salario_anterior', $contrato['salario'], PDO::PARAM_STR);
                $stmt->bindParam(':salario_nuevo', $salario, PDO::PARAM_STR);
                $stmt->bindParam(':usuario_registro', $_SESSION['user_id'], PDO::PARAM_STR);
                $stmt->execute();
            }
            
            // Actualizar el contrato
            $query = "UPDATE contratos SET 
                      id_puesto = :id_puesto, 
                      tipo_contrato = :tipo_contrato, 
                      fecha_inicio = :fecha_inicio, 
                      fecha_fin = :fecha_fin, 
                      salario = :salario, 
                      jornada = :jornada, 
                      horas_semanales = :horas_semanales, 
                      bonificacion_incentivo = :bonificacion_incentivo, 
                      observaciones = :observaciones
                      WHERE id_contrato = :id_contrato";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_puesto', $id_puesto, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_contrato', $tipo_contrato, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
            $stmt->bindParam(':salario', $salario, PDO::PARAM_STR);
            $stmt->bindParam(':jornada', $jornada, PDO::PARAM_STR);
            $stmt->bindParam(':horas_semanales', $horas_semanales, PDO::PARAM_INT);
            $stmt->bindParam(':bonificacion_incentivo', $bonificacion_incentivo, PDO::PARAM_STR);
            $stmt->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
            $stmt->bindParam(':id_contrato', $id_contrato, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Registro en bitácora (si existe una tabla para ello)
            $accion = "Actualización de contrato";
            $detalles = "Se actualizó el contrato #{$id_contrato} del empleado ID: {$contrato['id_empleado']}";
            
            if ($db->query("SHOW TABLES LIKE 'bitacora'")->rowCount() > 0) {
                $query = "INSERT INTO bitacora (id_usuario, accion, detalles, created_at)
                         VALUES (:id_usuario, :accion, :detalles, NOW())";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
                $stmt->bindParam(':accion', $accion);
                $stmt->bindParam(':detalles', $detalles);
                $stmt->execute();
            }
            
            $db->commit();
            
            setFlashMessage('Contrato actualizado exitosamente', 'success');
            header('Location: ' . BASE_URL . '?page=contratos/ver&id=' . $id_contrato);
            exit;
            
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            setFlashMessage('Error al actualizar el contrato: ' . $e->getMessage(), 'danger');
        }
    } else {
        // Mostrar errores
        foreach ($errores as $error) {
            setFlashMessage($error, 'danger');
        }
    }
}

// Obtener datos del empleado
$query = "SELECT id_empleado, DPI, CONCAT(primer_apellido, ' ', segundo_apellido, ', ', primer_nombre, ' ', segundo_nombre) AS nombre_completo 
          FROM empleados WHERE id_empleado = :id_empleado";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_empleado', $contrato['id_empleado'], PDO::PARAM_INT);
$stmt->execute();
$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener la lista de puestos
$query = "SELECT id_puesto, nombre, id_departamento 
          FROM puestos 
          ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$puestos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-file-contract fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Actualice los datos del contrato #<?php echo $id_contrato; ?></p>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Datos del Contrato</h6>
            <div>
                <a href="<?php echo BASE_URL; ?>?page=contratos/ver&id=<?php echo $id_contrato; ?>" class="btn btn-info btn-sm">
                    <i class="fas fa-eye fa-fw"></i> Ver Contrato
                </a>
                <a href="<?php echo BASE_URL; ?>?page=contratos/lista" class="btn btn-secondary btn-sm">
                    <i class="fas fa-list fa-fw"></i> Volver a la Lista
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="post" id="formEditarContrato">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="empleado" class="form-label">Empleado</label>
                        <input type="text" class="form-control" id="empleado" value="<?php echo $empleado['DPI'] . ' - ' . $empleado['nombre_completo']; ?>" readonly>
                        <small class="form-text text-muted">No se puede cambiar el empleado de un contrato existente</small>
                    </div>
                    <div class="col-md-6">
                        <label for="id_puesto" class="form-label">Puesto *</label>
                        <select class="form-select" id="id_puesto" name="id_puesto" required>
                            <option value="">Seleccione un puesto</option>
                            <?php foreach ($puestos as $puesto): ?>
                                <option value="<?php echo $puesto['id_puesto']; ?>" <?php echo ($puesto['id_puesto'] == $contrato['id_puesto']) ? 'selected' : ''; ?>>
                                    <?php echo $puesto['nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tipo_contrato" class="form-label">Tipo de Contrato *</label>
                        <select class="form-select" id="tipo_contrato" name="tipo_contrato" required>
                            <option value="">Seleccione un tipo</option>
                            <option value="Indefinido" <?php echo ($contrato['tipo_contrato'] == 'Indefinido') ? 'selected' : ''; ?>>Indefinido</option>
                            <option value="Plazo fijo" <?php echo ($contrato['tipo_contrato'] == 'Plazo fijo') ? 'selected' : ''; ?>>Plazo fijo</option>
                            <option value="Por obra" <?php echo ($contrato['tipo_contrato'] == 'Por obra') ? 'selected' : ''; ?>>Por obra</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo $contrato['fecha_inicio']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="fecha_fin" class="form-label">Fecha de Finalización</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo $contrato['fecha_fin'] ?? ''; ?>" <?php echo ($contrato['tipo_contrato'] != 'Plazo fijo') ? 'disabled' : ''; ?>>
                        <small class="form-text text-muted">Solo para contratos a plazo fijo</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="salario" class="form-label">Salario Base (Q) *</label>
                        <input type="number" class="form-control" id="salario" name="salario" step="0.01" min="0" value="<?php echo $contrato['salario']; ?>" required>
                        <?php if ($contrato['salario'] > 0): ?>
                            <small class="form-text text-info">Salario actual: Q <?php echo number_format($contrato['salario'], 2); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label for="bonificacion_incentivo" class="form-label">Bonificación Incentivo (Q)</label>
                        <input type="number" class="form-control" id="bonificacion_incentivo" name="bonificacion_incentivo" step="0.01" min="0" value="<?php echo $contrato['bonificacion_incentivo']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="jornada" class="form-label">Jornada Laboral *</label>
                        <select class="form-select" id="jornada" name="jornada" required>
                            <option value="">Seleccione una jornada</option>
                            <option value="Diurna" <?php echo ($contrato['jornada'] == 'Diurna') ? 'selected' : ''; ?>>Diurna</option>
                            <option value="Mixta" <?php echo ($contrato['jornada'] == 'Mixta') ? 'selected' : ''; ?>>Mixta</option>
                            <option value="Nocturna" <?php echo ($contrato['jornada'] == 'Nocturna') ? 'selected' : ''; ?>>Nocturna</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="horas_semanales" class="form-label">Horas Semanales *</label>
                        <input type="number" class="form-control" id="horas_semanales" name="horas_semanales" min="1" max="48" value="<?php echo $contrato['horas_semanales']; ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo $contrato['observaciones']; ?></textarea>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle fa-fw"></i> Los cambios en el salario quedarán registrados en el historial de salarios del empleado.
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <a href="<?php echo BASE_URL; ?>?page=contratos/ver&id=<?php echo $id_contrato; ?>" class="btn btn-secondary">
                            <i class="fas fa-times fa-fw"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save fa-fw"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoContratoSelect = document.getElementById('tipo_contrato');
    const fechaFinInput = document.getElementById('fecha_fin');
    
    // Mostrar/ocultar fecha de finalización basado en tipo de contrato
    tipoContratoSelect.addEventListener('change', function() {
        if (this.value === 'Plazo fijo') {
            fechaFinInput.removeAttribute('disabled');
            fechaFinInput.setAttribute('required', 'required');
        } else {
            fechaFinInput.value = '';
            fechaFinInput.setAttribute('disabled', 'disabled');
            fechaFinInput.removeAttribute('required');
        }
    });
    
    // Validación del formulario
    const form = document.getElementById('formEditarContrato');
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validar puesto
        const puestoSelect = document.getElementById('id_puesto');
        if (puestoSelect.value === '') {
            alert('Debe seleccionar un puesto válido.');
            puestoSelect.focus();
            isValid = false;
        }
        
        // Validar tipo de contrato
        if (tipoContratoSelect.value === '') {
            alert('Debe seleccionar un tipo de contrato.');
            tipoContratoSelect.focus();
            isValid = false;
        }
        
        // Validar fecha fin para contratos a plazo fijo
        if (tipoContratoSelect.value === 'Plazo fijo' && (!fechaFinInput.value || fechaFinInput.value === '')) {
            alert('Debe ingresar una fecha de finalización para contratos a plazo fijo.');
            fechaFinInput.focus();
            isValid = false;
        }
        
        // Validar salario
        const salarioInput = document.getElementById('salario');
        if (salarioInput.value <= 0) {
            alert('El salario debe ser mayor a cero.');
            salarioInput.focus();
            isValid = false;
        }
        
        if (!isValid) {
            event.preventDefault();
        }
    });
});
</script> 