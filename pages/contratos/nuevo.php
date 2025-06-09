<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Nuevo Contrato';
$activeMenu = 'empleados';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_empleado = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : 0;
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
    
    if ($id_empleado <= 0) {
        $errores[] = 'Debe seleccionar un empleado válido.';
    }
    
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
    
    // Si no hay errores, guardar el contrato
    if (empty($errores)) {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            // Insertar el contrato
            $query = "INSERT INTO contratos (id_empleado, id_puesto, tipo_contrato, fecha_inicio, fecha_fin, 
                      salario, jornada, horas_semanales, bonificacion_incentivo, observaciones, estado) 
                      VALUES (:id_empleado, :id_puesto, :tipo_contrato, :fecha_inicio, :fecha_fin, 
                      :salario, :jornada, :horas_semanales, :bonificacion_incentivo, :observaciones, 1)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
            $stmt->bindParam(':id_puesto', $id_puesto, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_contrato', $tipo_contrato, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
            $stmt->bindParam(':salario', $salario, PDO::PARAM_STR);
            $stmt->bindParam(':jornada', $jornada, PDO::PARAM_STR);
            $stmt->bindParam(':horas_semanales', $horas_semanales, PDO::PARAM_INT);
            $stmt->bindParam(':bonificacion_incentivo', $bonificacion_incentivo, PDO::PARAM_STR);
            $stmt->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
            
            $stmt->execute();
            $id_contrato = $db->lastInsertId();
            
            $db->commit();
            
            setFlashMessage('Contrato creado exitosamente', 'success');
            header('Location: ' . BASE_URL . '?page=contratos/lista');
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            setFlashMessage('Error al crear el contrato: ' . $e->getMessage(), 'danger');
        }
    } else {
        // Mostrar errores
        foreach ($errores as $error) {
            setFlashMessage($error, 'danger');
        }
    }
}

// Obtener la lista de empleados activos
$db = getDB();
$query = "SELECT id_empleado, DPI, CONCAT(primer_apellido, ' ', segundo_apellido, ', ', primer_nombre, ' ', segundo_nombre) AS nombre_completo 
          FROM empleados 
          ORDER BY primer_apellido, primer_nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <p class="mb-4">Complete el formulario para registrar un nuevo contrato</p>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Datos del Contrato</h6>
        </div>
        <div class="card-body">
            <form method="post" id="formNuevoContrato">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="id_empleado" class="form-label">Empleado *</label>
                        <select class="form-select" id="id_empleado" name="id_empleado" required>
                            <option value="">Seleccione un empleado</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?php echo $empleado['id_empleado']; ?>">
                                    <?php echo $empleado['DPI'] . ' - ' . $empleado['nombre_completo']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="id_puesto" class="form-label">Puesto *</label>
                        <select class="form-select" id="id_puesto" name="id_puesto" required>
                            <option value="">Seleccione un puesto</option>
                            <?php foreach ($puestos as $puesto): ?>
                                <option value="<?php echo $puesto['id_puesto']; ?>">
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
                            <option value="Indefinido">Indefinido</option>
                            <option value="Plazo fijo">Plazo fijo</option>
                            <option value="Por obra">Por obra</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                    </div>
                    <div class="col-md-4">
                        <label for="fecha_fin" class="form-label">Fecha de Finalización</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                        <small class="form-text text-muted">Solo para contratos a plazo fijo</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="salario" class="form-label">Salario Base (Q) *</label>
                        <input type="number" class="form-control" id="salario" name="salario" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label for="bonificacion_incentivo" class="form-label">Bonificación Incentivo (Q)</label>
                        <input type="number" class="form-control" id="bonificacion_incentivo" name="bonificacion_incentivo" step="0.01" min="0" value="250.00">
                    </div>
                    <div class="col-md-4">
                        <label for="jornada" class="form-label">Jornada Laboral *</label>
                        <select class="form-select" id="jornada" name="jornada" required>
                            <option value="">Seleccione una jornada</option>
                            <option value="Diurna">Diurna</option>
                            <option value="Mixta">Mixta</option>
                            <option value="Nocturna">Nocturna</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="horas_semanales" class="form-label">Horas Semanales *</label>
                        <input type="number" class="form-control" id="horas_semanales" name="horas_semanales" min="1" max="48" value="44" required>
                    </div>
                    <div class="col-md-8">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <a href="<?php echo BASE_URL; ?>?page=contratos/lista" class="btn btn-secondary">
                            <i class="fas fa-arrow-left fa-fw"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save fa-fw"></i> Guardar Contrato
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
    
    // Establecer fecha actual como fecha por defecto para inicio
    const hoy = new Date();
    const fechaHoy = hoy.getFullYear() + '-' + 
                    String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(hoy.getDate()).padStart(2, '0');
    document.getElementById('fecha_inicio').value = fechaHoy;
    
    // Validación del formulario
    const form = document.getElementById('formNuevoContrato');
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validar empleado
        const idEmpleado = document.getElementById('id_empleado').value;
        if (!idEmpleado) {
            isValid = false;
            alert('Debe seleccionar un empleado');
        }
        
        // Validar tipo de contrato y fecha fin
        const tipoContrato = document.getElementById('tipo_contrato').value;
        if (tipoContrato === 'Plazo fijo' && !fechaFinInput.value) {
            isValid = false;
            alert('Debe ingresar una fecha de finalización para contratos a plazo fijo');
        }
        
        if (!isValid) {
            event.preventDefault();
        }
    });
});
</script> 