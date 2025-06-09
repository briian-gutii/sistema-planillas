<?php
// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_CONTABILIDAD, ROL_RRHH])) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Registrar Horas Extra';
$activeMenu = 'horas_extra';

// Obtener lista de empleados activos
$db = getDB();
$empleados = [];

try {
    $query = "SELECT e.id_empleado, e.codigo_empleado, e.nombres, e.apellidos,
             c.salario_base, 
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

// Variables para el formulario
$errores = [];
$datos = [
    'id_empleado' => '',
    'fecha' => date('Y-m-d'),
    'horas' => '',
    'descripcion' => '',
    'valor_hora' => '',
    'autocalcular_valor' => 'S'
];

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['id_empleado'] = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : 0;
    $datos['fecha'] = isset($_POST['fecha']) ? $_POST['fecha'] : '';
    $datos['horas'] = isset($_POST['horas']) ? floatval($_POST['horas']) : 0;
    $datos['descripcion'] = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $datos['valor_hora'] = isset($_POST['valor_hora']) ? floatval($_POST['valor_hora']) : 0;
    $datos['autocalcular_valor'] = isset($_POST['autocalcular_valor']) ? $_POST['autocalcular_valor'] : 'N';
    
    // Validar datos
    if ($datos['id_empleado'] <= 0) {
        $errores[] = 'Debe seleccionar un empleado';
    }
    
    if (empty($datos['fecha'])) {
        $errores[] = 'La fecha es obligatoria';
    } elseif (strtotime($datos['fecha']) > time()) {
        $errores[] = 'La fecha no puede ser futura';
    }
    
    if ($datos['horas'] <= 0) {
        $errores[] = 'El número de horas debe ser mayor a cero';
    } elseif ($datos['horas'] > 24) {
        $errores[] = 'El número de horas no puede ser mayor a 24';
    }
    
    if (empty($datos['descripcion'])) {
        $errores[] = 'La descripción es obligatoria';
    }
    
    if ($datos['autocalcular_valor'] == 'N' && $datos['valor_hora'] <= 0) {
        $errores[] = 'El valor por hora debe ser mayor a cero';
    }
    
    // Obtener valor por hora si es automático
    if ($datos['autocalcular_valor'] == 'S' && $datos['id_empleado'] > 0) {
        try {
            // Buscar salario base del empleado
            $queryEmpleado = "SELECT c.salario_base FROM contratos c
                            WHERE c.id_empleado = :id_empleado
                            AND c.estado = 'Activo'";
            
            $stmtEmpleado = $db->prepare($queryEmpleado);
            $stmtEmpleado->bindParam(':id_empleado', $datos['id_empleado'], PDO::PARAM_INT);
            $stmtEmpleado->execute();
            
            if ($stmtEmpleado->rowCount() > 0) {
                $contrato = $stmtEmpleado->fetch(PDO::FETCH_ASSOC);
                $salarioBase = $contrato['salario_base'];
                
                // Calcular valor de hora extra (1.5 veces el salario base / 30 días / 8 horas)
                $datos['valor_hora'] = ($salarioBase / 30 / 8) * 1.5;
            } else {
                $errores[] = 'No se pudo calcular el valor por hora automáticamente';
            }
        } catch (Exception $e) {
            $errores[] = 'Error al calcular el valor por hora: ' . $e->getMessage();
        }
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errores)) {
        try {
            $queryInsert = "INSERT INTO horas_extra 
                          (id_empleado, fecha, horas, valor_hora, descripcion, estado, fecha_registro, registrado_por)
                          VALUES 
                          (:id_empleado, :fecha, :horas, :valor_hora, :descripcion, 'Pendiente', NOW(), :registrado_por)";
            
            $stmt = $db->prepare($queryInsert);
            $stmt->bindParam(':id_empleado', $datos['id_empleado'], PDO::PARAM_INT);
            $stmt->bindParam(':fecha', $datos['fecha']);
            $stmt->bindParam(':horas', $datos['horas']);
            $stmt->bindParam(':valor_hora', $datos['valor_hora']);
            $stmt->bindParam(':descripcion', $datos['descripcion']);
            $stmt->bindParam(':registrado_por', $_SESSION['user_id'], PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $id_hora_extra = $db->lastInsertId();
                setFlashMessage('Horas extra registradas correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=horas_extra/ver&id=' . $id_hora_extra);
                exit;
            } else {
                $errores[] = 'Error al guardar los datos';
            }
        } catch (Exception $e) {
            $errores[] = 'Error al guardar los datos: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-clock fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Complete el formulario para registrar horas extra</p>
    
    <!-- Mostrar errores -->
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Formulario de registro -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulario de Registro</h6>
        </div>
        <div class="card-body">
            <form method="post" id="formHorasExtra">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="id_empleado" class="form-label">Empleado *</label>
                        <select class="form-select" id="id_empleado" name="id_empleado" required>
                            <option value="">Seleccione un empleado</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?php echo $empleado['id_empleado']; ?>" 
                                        <?php echo $datos['id_empleado'] == $empleado['id_empleado'] ? 'selected' : ''; ?>
                                        data-salario="<?php echo $empleado['salario_base']; ?>">
                                    <?php echo $empleado['apellidos'] . ', ' . $empleado['nombres'] . ' (' . $empleado['codigo_empleado'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="fecha" class="form-label">Fecha *</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" 
                               value="<?php echo $datos['fecha']; ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="horas" class="form-label">Horas Trabajadas *</label>
                        <input type="number" class="form-control" id="horas" name="horas" 
                               value="<?php echo $datos['horas']; ?>" step="0.5" min="0.5" max="24" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="descripcion" class="form-label">Descripción del Trabajo Realizado *</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php echo $datos['descripcion']; ?></textarea>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="S" id="autocalcular_valor" name="autocalcular_valor"
                                   <?php echo $datos['autocalcular_valor'] == 'S' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="autocalcular_valor">
                                Calcular automáticamente el valor de la hora extra
                            </label>
                        </div>
                        <div class="form-text">
                            El valor se calculará como 1.5 veces el salario base / 30 días / 8 horas
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="valor_hora" class="form-label">Valor por Hora</label>
                        <div class="input-group">
                            <span class="input-group-text">Q</span>
                            <input type="number" class="form-control" id="valor_hora" name="valor_hora" 
                                   value="<?php echo $datos['valor_hora']; ?>" step="0.01" min="0"
                                   <?php echo $datos['autocalcular_valor'] == 'S' ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                Información de Cálculo
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <label class="col-sm-6 col-form-label">Total a Pagar:</label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control-plaintext fw-bold" id="totalMostrar" readonly value="Q 0.00">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo BASE_URL; ?>?page=horas_extra/lista" class="btn btn-secondary me-md-2">
                        <i class="fas fa-arrow-left fa-fw"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save fa-fw"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const idEmpleadoSelect = document.getElementById('id_empleado');
    const horasInput = document.getElementById('horas');
    const valorHoraInput = document.getElementById('valor_hora');
    const totalMostrarSpan = document.getElementById('totalMostrar');
    const autocalcularCheck = document.getElementById('autocalcular_valor');
    
    // Función para calcular el valor de la hora extra
    function calcularValorHora() {
        if (autocalcularCheck.checked && idEmpleadoSelect.value) {
            const selectedOption = idEmpleadoSelect.options[idEmpleadoSelect.selectedIndex];
            const salarioBase = parseFloat(selectedOption.getAttribute('data-salario')) || 0;
            
            // 1.5 veces el salario base dividido por 30 días y 8 horas
            const valorHora = (salarioBase / 30 / 8) * 1.5;
            valorHoraInput.value = valorHora.toFixed(2);
        }
    }
    
    // Función para calcular el total
    function calcularTotal() {
        const horas = parseFloat(horasInput.value) || 0;
        const valorHora = parseFloat(valorHoraInput.value) || 0;
        const total = horas * valorHora;
        
        totalMostrarSpan.value = 'Q ' + total.toFixed(2);
    }
    
    // Evento cuando cambia el empleado seleccionado
    idEmpleadoSelect.addEventListener('change', function() {
        calcularValorHora();
        calcularTotal();
    });
    
    // Evento cuando cambian las horas
    horasInput.addEventListener('input', calcularTotal);
    
    // Evento cuando cambia el valor por hora
    valorHoraInput.addEventListener('input', calcularTotal);
    
    // Evento cuando cambia el checkbox de autocalcular
    autocalcularCheck.addEventListener('change', function() {
        valorHoraInput.readOnly = this.checked;
        calcularValorHora();
        calcularTotal();
    });
    
    // Calcular al cargar la página
    calcularValorHora();
    calcularTotal();
});
</script> 