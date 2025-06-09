<?php

// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_CONTABILIDAD, ROL_RRHH])) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Editar Horas Extra';
$activeMenu = 'horas_extra';

// Obtener el ID de la hora extra
$id_hora_extra = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_hora_extra <= 0) {
    setFlashMessage('Registro no especificado', 'danger');
    header('Location: ' . BASE_URL . '?page=horas_extra/lista');
    exit;
}

// Obtener datos de la hora extra
$db = getDB();
$hora_extra = null;
$empleado = null;
$errores = [];

try {
    // Verificar si existe el registro y está pendiente
    $query = "SELECT he.*, 
             e.nombres, e.apellidos, e.codigo_empleado,
             d.nombre as departamento, p.nombre as puesto,
             c.salario_base
             FROM horas_extra he
             JOIN empleados e ON he.id_empleado = e.id_empleado
             LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
             LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
             LEFT JOIN contratos c ON e.id_empleado = c.id_empleado AND c.estado = 'Activo'
             WHERE he.id_hora_extra = :id_hora_extra";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_hora_extra', $id_hora_extra, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('El registro especificado no existe', 'danger');
        header('Location: ' . BASE_URL . '?page=horas_extra/lista');
        exit;
    }
    
    $hora_extra = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar que el registro esté pendiente
    if ($hora_extra['estado'] != 'Pendiente') {
        setFlashMessage('Solo se pueden editar registros en estado Pendiente', 'warning');
        header('Location: ' . BASE_URL . '?page=horas_extra/ver&id=' . $id_hora_extra);
        exit;
    }
    
    // Preparar datos del empleado para mostrar
    $empleado = [
        'id_empleado' => $hora_extra['id_empleado'],
        'nombre_completo' => $hora_extra['apellidos'] . ', ' . $hora_extra['nombres'],
        'codigo_empleado' => $hora_extra['codigo_empleado'],
        'departamento' => $hora_extra['departamento'],
        'puesto' => $hora_extra['puesto'],
        'salario_base' => $hora_extra['salario_base']
    ];
    
} catch (Exception $e) {
    setFlashMessage('Error al cargar los datos: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '?page=horas_extra/lista');
    exit;
}

// Variables para el formulario
$datos = [
    'fecha' => $hora_extra['fecha'],
    'horas' => $hora_extra['horas'],
    'descripcion' => $hora_extra['descripcion'],
    'valor_hora' => $hora_extra['valor_hora'],
    'autocalcular_valor' => 'N' // Por defecto no se recalcula
];

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['fecha'] = isset($_POST['fecha']) ? $_POST['fecha'] : '';
    $datos['horas'] = isset($_POST['horas']) ? floatval($_POST['horas']) : 0;
    $datos['descripcion'] = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $datos['valor_hora'] = isset($_POST['valor_hora']) ? floatval($_POST['valor_hora']) : 0;
    $datos['autocalcular_valor'] = isset($_POST['autocalcular_valor']) ? $_POST['autocalcular_valor'] : 'N';
    
    // Validar datos
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
    if ($datos['autocalcular_valor'] == 'S' && $empleado['salario_base'] > 0) {
        // Calcular valor de hora extra (1.5 veces el salario base / 30 días / 8 horas)
        $datos['valor_hora'] = ($empleado['salario_base'] / 30 / 8) * 1.5;
    }
    
    // Si no hay errores, actualizar en la base de datos
    if (empty($errores)) {
        try {
            $queryUpdate = "UPDATE horas_extra SET 
                          fecha = :fecha, 
                          horas = :horas, 
                          valor_hora = :valor_hora,
                          descripcion = :descripcion
                          WHERE id_hora_extra = :id_hora_extra";
            
            $stmt = $db->prepare($queryUpdate);
            $stmt->bindParam(':fecha', $datos['fecha']);
            $stmt->bindParam(':horas', $datos['horas']);
            $stmt->bindParam(':valor_hora', $datos['valor_hora']);
            $stmt->bindParam(':descripcion', $datos['descripcion']);
            $stmt->bindParam(':id_hora_extra', $id_hora_extra, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Registrar en historial
                try {
                    $descripcion = "Edición de horas extra para el empleado " . 
                                   $empleado['nombre_completo'] . " (" . $empleado['codigo_empleado'] . ")";
                    
                    $queryHistorial = "INSERT INTO historial (accion, descripcion, tipo_entidad, id_entidad, usuario_id, fecha)
                                      VALUES ('Edición de horas extra', :descripcion, 'horas_extra', :id_hora_extra, :usuario_id, NOW())";
                    
                    $stmtHistorial = $db->prepare($queryHistorial);
                    $stmtHistorial->bindParam(':descripcion', $descripcion);
                    $stmtHistorial->bindParam(':id_hora_extra', $id_hora_extra, PDO::PARAM_INT);
                    $stmtHistorial->bindParam(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
                    $stmtHistorial->execute();
                } catch (Exception $e) {
                    // Si hay error en el registro del historial, ignoramos para no interrumpir flujo
                }
                
                setFlashMessage('Registro de horas extra actualizado correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=horas_extra/ver&id=' . $id_hora_extra);
                exit;
            } else {
                $errores[] = 'Error al actualizar los datos';
            }
        } catch (Exception $e) {
            $errores[] = 'Error al actualizar los datos: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-edit fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Modifique los datos del registro de horas extra</p>
    
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
    
    <!-- Información del Empleado -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Datos del Empleado</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nombre:</strong> <?php echo $empleado['nombre_completo']; ?></p>
                    <p><strong>Código:</strong> <?php echo $empleado['codigo_empleado']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Departamento:</strong> <?php echo $empleado['departamento']; ?></p>
                    <p><strong>Puesto:</strong> <?php echo $empleado['puesto']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulario de edición -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulario de Edición</h6>
        </div>
        <div class="card-body">
            <form method="post" id="formEditarHorasExtra">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="fecha" class="form-label">Fecha *</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" 
                               value="<?php echo $datos['fecha']; ?>" required>
                    </div>
                    
                    <div class="col-md-6">
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
                                Recalcular automáticamente el valor de la hora extra
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
                    <a href="<?php echo BASE_URL; ?>?page=horas_extra/ver&id=<?php echo $id_hora_extra; ?>" class="btn btn-secondary me-md-2">
                        <i class="fas fa-arrow-left fa-fw"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save fa-fw"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const horasInput = document.getElementById('horas');
    const valorHoraInput = document.getElementById('valor_hora');
    const totalMostrarSpan = document.getElementById('totalMostrar');
    const autocalcularCheck = document.getElementById('autocalcular_valor');
    
    // Datos del empleado para cálculo automático
    const salarioBase = <?php echo $empleado['salario_base']; ?>;
    
    // Función para calcular el valor de la hora extra
    function calcularValorHora() {
        if (autocalcularCheck.checked && salarioBase > 0) {
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
    
    // Evento cuando cambian las horas
    horasInput.addEventListener('input', calcularTotal);
    
    // Evento cuando cambia el valor por hora
    valorHoraInput.addEventListener('input', calcularTotal);
    
    // Evento cuando cambia el checkbox de autocalcular
    autocalcularCheck.addEventListener('change', function() {
        valorHoraInput.readOnly = this.checked;
        if (this.checked) {
            calcularValorHora();
        }
        calcularTotal();
    });
    
    // Calcular al cargar la página
    calcularTotal();
});
</script> 