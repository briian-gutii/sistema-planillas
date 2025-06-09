<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Registrar Nueva Ausencia';
$activeMenu = 'empleados';

// Lista de tipos de ausencia
$tiposAusencia = [
    'Enfermedad común',
    'Vacaciones',
    'Suspensión IGSS',
    'Licencia',
    'Permiso',
    'Falta injustificada'
];

// Obtener la lista de empleados activos para el select
$db = getDB();
$queryEmpleados = "SELECT id_empleado, DPI, 
                  CONCAT(primer_nombre, ' ', IFNULL(segundo_nombre, '')) as nombres, 
                  CONCAT(primer_apellido, ' ', IFNULL(segundo_apellido, '')) as apellidos
                  FROM empleados 
                  WHERE estado = 'Activo'
                  ORDER BY primer_apellido, primer_nombre";
$stmtEmpleados = $db->prepare($queryEmpleados);
$stmtEmpleados->execute();
$empleados = $stmtEmpleados->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar los datos del formulario
    $id_empleado = filter_input(INPUT_POST, 'id_empleado', FILTER_SANITIZE_NUMBER_INT);
    $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING);
    $fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_SANITIZE_STRING);
    $fecha_fin = filter_input(INPUT_POST, 'fecha_fin', FILTER_SANITIZE_STRING);
    $justificada = isset($_POST['justificada']) ? 1 : 0;
    $justificacion = filter_input(INPUT_POST, 'justificacion', FILTER_SANITIZE_STRING);
    $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING);
    
    // Validaciones
    $errores = [];
    
    // Validar que se haya seleccionado un empleado
    if (empty($id_empleado)) {
        $errores[] = 'Debe seleccionar un empleado';
    }
    
    // Validar que las fechas sean válidas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
        $errores[] = 'Las fechas deben tener el formato correcto (YYYY-MM-DD)';
    } 
    // Validar que la fecha de fin no sea anterior a la fecha de inicio
    else if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
        $errores[] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
    }
    
    // Validar que el tipo de ausencia sea válido
    if (!in_array($tipo, $tiposAusencia)) {
        $errores[] = 'El tipo de ausencia seleccionado no es válido';
    }
    
    // Procesar archivo adjunto si existe
    $archivo_nombre = '';
    $archivo_ruta = '';
    
    if (isset($_FILES['archivo_justificacion']) && $_FILES['archivo_justificacion']['error'] == 0) {
        $archivo = $_FILES['archivo_justificacion'];
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $tamano_maximo = 5 * 1024 * 1024; // 5MB
        
        // Validar tipo de archivo
        if (!in_array($archivo['type'], $tipos_permitidos)) {
            $errores[] = 'Tipo de archivo no permitido. Se permiten imágenes, PDF y documentos Word.';
        }
        
        // Validar tamaño
        if ($archivo['size'] > $tamano_maximo) {
            $errores[] = 'El archivo es demasiado grande. Tamaño máximo: 5MB.';
        }
        
        // Si no hay errores en el archivo, preparar para guardar
        if (empty($errores)) {
            // Crear directorio si no existe
            $directorio_uploads = 'uploads/justificaciones/';
            if (!file_exists($directorio_uploads)) {
                mkdir($directorio_uploads, 0777, true);
            }
            
            // Generar nombre único para el archivo
            $archivo_nombre = time() . '_' . $id_empleado . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $archivo['name']);
            $archivo_ruta = $directorio_uploads . $archivo_nombre;
        }
    }
    
    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        foreach ($errores as $error) {
            setFlashMessage($error, 'danger');
        }
    } else {
        try {
            // Mover archivo cargado si existe
            if (!empty($archivo_ruta)) {
                move_uploaded_file($archivo['tmp_name'], $archivo_ruta);
            }
            
            // Insertar la nueva ausencia en la base de datos
            $queryInsert = "INSERT INTO ausencias (id_empleado, tipo, fecha_inicio, fecha_fin, justificada, justificacion, observaciones, archivo_justificacion) 
                           VALUES (:id_empleado, :tipo, :fecha_inicio, :fecha_fin, :justificada, :justificacion, :observaciones, :archivo_justificacion)";
            $stmtInsert = $db->prepare($queryInsert);
            $stmtInsert->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
            $stmtInsert->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmtInsert->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
            $stmtInsert->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
            $stmtInsert->bindParam(':justificada', $justificada, PDO::PARAM_INT);
            $stmtInsert->bindParam(':justificacion', $justificacion, PDO::PARAM_STR);
            $stmtInsert->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
            $stmtInsert->bindParam(':archivo_justificacion', $archivo_nombre, PDO::PARAM_STR);
            
            if ($stmtInsert->execute()) {
                $nuevo_id = $db->lastInsertId();
                setFlashMessage('Ausencia registrada correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=ausencias/ver&id=' . $nuevo_id);
                exit;
            } else {
                setFlashMessage('Error al registrar la ausencia', 'danger');
            }
        } catch (PDOException $e) {
            setFlashMessage('Error en la base de datos: ' . $e->getMessage(), 'danger');
        }
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-calendar-times fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Complete el formulario para registrar una nueva ausencia</p>

    <!-- Botones de Acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=ausencias/lista" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-fw"></i> Volver a la Lista
        </a>
    </div>

    <!-- Formulario de Registro -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Nueva Ausencia</h6>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo BASE_URL; ?>?page=ausencias/nuevo" enctype="multipart/form-data">
                <!-- Selección de Empleado -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="id_empleado" class="form-label">Empleado <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_empleado" name="id_empleado" required>
                                <option value="">Seleccione un empleado</option>
                                <?php foreach ($empleados as $empleado): ?>
                                    <option value="<?php echo $empleado['id_empleado']; ?>">
                                        <?php echo htmlspecialchars($empleado['DPI'] . ' - ' . $empleado['apellidos'] . ', ' . $empleado['nombres']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tipo de Ausencia -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Ausencia <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Seleccione un tipo</option>
                                <?php foreach ($tiposAusencia as $tipo): ?>
                                    <option value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Fechas -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_fin" class="form-label">Fecha de Fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                        </div>
                    </div>
                </div>

                <!-- Justificada -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="justificada" name="justificada" value="1" checked>
                            <label class="form-check-label" for="justificada">Ausencia Justificada</label>
                        </div>
                    </div>
                </div>

                <!-- Justificación -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="justificacion" class="form-label">Justificación</label>
                            <textarea class="form-control" id="justificacion" name="justificacion" rows="3"></textarea>
                            <div class="form-text">Ingrese la razón o documentación que justifica esta ausencia, si aplica.</div>
                        </div>
                    </div>
                </div>

                <!-- Archivo de Justificación -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="archivo_justificacion" class="form-label">Documento de Respaldo</label>
                            <input type="file" class="form-control" id="archivo_justificacion" name="archivo_justificacion">
                            <div class="form-text">Puede subir imágenes, PDFs o documentos Word (Máx. 5MB)</div>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                            <div class="form-text">Notas adicionales sobre esta ausencia.</div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Registrar Ausencia</button>
                        <a href="<?php echo BASE_URL; ?>?page=ausencias/lista" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Añadir validación para fechas
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    
    // Establecer fecha de hoy como valor por defecto para fecha de inicio
    const hoy = new Date().toISOString().split('T')[0];
    fechaInicio.value = hoy;
    fechaFin.value = hoy;
    
    // Validar que la fecha de fin no sea anterior a la de inicio
    fechaFin.addEventListener('change', function() {
        if (fechaInicio.value && fechaFin.value && fechaFin.value < fechaInicio.value) {
            alert('La fecha de fin no puede ser anterior a la fecha de inicio');
            fechaFin.value = fechaInicio.value;
        }
    });
    
    // Cambiar el checkbox de justificada según el tipo de ausencia
    const tipoSelect = document.getElementById('tipo');
    const justificadaCheck = document.getElementById('justificada');
    
    tipoSelect.addEventListener('change', function() {
        if (this.value === 'Falta injustificada') {
            justificadaCheck.checked = false;
        }
    });
});
</script> 