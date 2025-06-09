<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar si se recibió el ID de la ausencia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('ID de ausencia no especificado', 'danger');
    header('Location: ' . BASE_URL . '?page=ausencias/lista');
    exit;
}

$id_ausencia = intval($_GET['id']);
$pageTitle = 'Editar Ausencia';
$activeMenu = 'empleados';

// Obtener los datos de la ausencia de la base de datos
$db = getDB();
$query = "SELECT a.*, e.DPI as codigo, 
          CONCAT(e.primer_nombre, ' ', IFNULL(e.segundo_nombre, '')) as nombres, 
          CONCAT(e.primer_apellido, ' ', IFNULL(e.segundo_apellido, '')) as apellidos
          FROM ausencias a
          JOIN empleados e ON a.id_empleado = e.id_empleado
          WHERE a.id_ausencia = :id_ausencia";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_ausencia', $id_ausencia, PDO::PARAM_INT);
$stmt->execute();
$ausencia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ausencia) {
    setFlashMessage('Ausencia no encontrada', 'danger');
    header('Location: ' . BASE_URL . '?page=ausencias/lista');
    exit;
}

// Lista de tipos de ausencia
$tiposAusencia = [
    'Enfermedad común',
    'Vacaciones',
    'Suspensión IGSS',
    'Licencia',
    'Permiso',
    'Falta injustificada'
];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar los datos del formulario
    $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING);
    $fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_SANITIZE_STRING);
    $fecha_fin = filter_input(INPUT_POST, 'fecha_fin', FILTER_SANITIZE_STRING);
    $justificada = isset($_POST['justificada']) ? 1 : 0;
    $justificacion = filter_input(INPUT_POST, 'justificacion', FILTER_SANITIZE_STRING);
    $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING);
    $eliminar_archivo = isset($_POST['eliminar_archivo']) ? 1 : 0;
    
    // Validar que las fechas sean válidas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
        setFlashMessage('Las fechas deben tener el formato correcto (YYYY-MM-DD)', 'danger');
    } 
    // Validar que la fecha de fin no sea anterior a la fecha de inicio
    else if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
        setFlashMessage('La fecha de fin no puede ser anterior a la fecha de inicio', 'danger');
    }
    // Validar que el tipo de ausencia sea válido
    else if (!in_array($tipo, $tiposAusencia)) {
        setFlashMessage('El tipo de ausencia seleccionado no es válido', 'danger');
    }
    else {
        try {
            // Procesar archivo adjunto si existe
            $archivo_nombre = $ausencia['archivo_justificacion'];
            
            // Si se marca la opción de eliminar archivo
            if ($eliminar_archivo && !empty($archivo_nombre)) {
                $ruta_archivo = 'uploads/justificaciones/' . $archivo_nombre;
                if (file_exists($ruta_archivo)) {
                    unlink($ruta_archivo);
                }
                $archivo_nombre = '';
            }
            
            // Si se sube un nuevo archivo
            if (isset($_FILES['archivo_justificacion']) && $_FILES['archivo_justificacion']['error'] == 0) {
                $archivo = $_FILES['archivo_justificacion'];
                $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
                $tamano_maximo = 5 * 1024 * 1024; // 5MB
                
                // Validar tipo de archivo
                if (!in_array($archivo['type'], $tipos_permitidos)) {
                    setFlashMessage('Tipo de archivo no permitido. Se permiten imágenes, PDF y documentos Word.', 'danger');
                    // No continuar con la actualización
                    header('Location: ' . BASE_URL . '?page=ausencias/editar&id=' . $id_ausencia);
                    exit;
                }
                
                // Validar tamaño
                if ($archivo['size'] > $tamano_maximo) {
                    setFlashMessage('El archivo es demasiado grande. Tamaño máximo: 5MB.', 'danger');
                    // No continuar con la actualización
                    header('Location: ' . BASE_URL . '?page=ausencias/editar&id=' . $id_ausencia);
                    exit;
                }
                
                // Eliminar archivo anterior si existe
                if (!empty($ausencia['archivo_justificacion'])) {
                    $ruta_archivo_anterior = 'uploads/justificaciones/' . $ausencia['archivo_justificacion'];
                    if (file_exists($ruta_archivo_anterior)) {
                        unlink($ruta_archivo_anterior);
                    }
                }
                
                // Crear directorio si no existe
                $directorio_uploads = 'uploads/justificaciones/';
                if (!file_exists($directorio_uploads)) {
                    mkdir($directorio_uploads, 0777, true);
                }
                
                // Generar nombre único para el archivo
                $archivo_nombre = time() . '_' . $ausencia['id_empleado'] . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $archivo['name']);
                $archivo_ruta = $directorio_uploads . $archivo_nombre;
                
                // Mover el archivo
                move_uploaded_file($archivo['tmp_name'], $archivo_ruta);
            }
            
            // Actualizar la ausencia en la base de datos
            $queryUpdate = "UPDATE ausencias SET 
                            tipo = :tipo, 
                            fecha_inicio = :fecha_inicio, 
                            fecha_fin = :fecha_fin, 
                            justificada = :justificada,
                            justificacion = :justificacion, 
                            observaciones = :observaciones,
                            archivo_justificacion = :archivo_justificacion
                            WHERE id_ausencia = :id_ausencia";
            $stmtUpdate = $db->prepare($queryUpdate);
            $stmtUpdate->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':justificada', $justificada, PDO::PARAM_INT);
            $stmtUpdate->bindParam(':justificacion', $justificacion, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':archivo_justificacion', $archivo_nombre, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':id_ausencia', $id_ausencia, PDO::PARAM_INT);
            
            if ($stmtUpdate->execute()) {
                setFlashMessage('Ausencia actualizada correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=ausencias/ver&id=' . $id_ausencia);
                exit;
            } else {
                setFlashMessage('Error al actualizar la ausencia', 'danger');
            }
        } catch (PDOException $e) {
            setFlashMessage('Error en la base de datos: ' . $e->getMessage(), 'danger');
        }
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-calendar-times fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Modifique la información de la ausencia</p>

    <!-- Botones de Acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=ausencias/lista" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-fw"></i> Volver a la Lista
        </a>
        <a href="<?php echo BASE_URL; ?>?page=ausencias/ver&id=<?php echo $ausencia['id_ausencia']; ?>" class="btn btn-info btn-sm">
            <i class="fas fa-eye fa-fw"></i> Ver Detalles
        </a>
    </div>

    <!-- Formulario de Edición -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Editar Ausencia #<?php echo $ausencia['id_ausencia']; ?></h6>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo BASE_URL; ?>?page=ausencias/editar&id=<?php echo $id_ausencia; ?>" enctype="multipart/form-data">
                <!-- Información del empleado (no editable) -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5>Empleado</h5>
                        <p>
                            <strong>Código:</strong> <?php echo htmlspecialchars($ausencia['codigo']); ?><br>
                            <strong>Nombre:</strong> <?php echo htmlspecialchars($ausencia['apellidos'] . ', ' . $ausencia['nombres']); ?>
                        </p>
                    </div>
                </div>

                <!-- Tipo de Ausencia -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Ausencia <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <?php foreach ($tiposAusencia as $tipo): ?>
                                    <option value="<?php echo htmlspecialchars($tipo); ?>" <?php if ($ausencia['tipo'] === $tipo) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($tipo); ?>
                                    </option>
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
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($ausencia['fecha_inicio']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_fin" class="form-label">Fecha de Fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($ausencia['fecha_fin']); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Justificada -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="justificada" name="justificada" value="1" <?php if (isset($ausencia['justificada']) && $ausencia['justificada'] == 1) echo 'checked'; ?>>
                            <label class="form-check-label" for="justificada">Ausencia Justificada</label>
                        </div>
                    </div>
                </div>

                <!-- Justificación -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="justificacion" class="form-label">Justificación</label>
                            <textarea class="form-control" id="justificacion" name="justificacion" rows="3"><?php echo htmlspecialchars($ausencia['justificacion']); ?></textarea>
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
                            
                            <?php if (!empty($ausencia['archivo_justificacion'])): ?>
                                <div class="mt-2 p-2 border rounded">
                                    <p class="mb-1"><strong>Archivo actual:</strong> <?php echo htmlspecialchars($ausencia['archivo_justificacion']); ?></p>
                                    <div class="d-flex align-items-center mt-2">
                                        <a href="<?php echo BASE_URL; ?>uploads/justificaciones/<?php echo $ausencia['archivo_justificacion']; ?>" class="btn btn-sm btn-info me-2" target="_blank">
                                            <i class="fas fa-eye fa-fw"></i> Ver archivo
                                        </a>
                                        <div class="form-check ms-3">
                                            <input class="form-check-input" type="checkbox" id="eliminar_archivo" name="eliminar_archivo" value="1">
                                            <label class="form-check-label" for="eliminar_archivo">Eliminar archivo actual</label>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($ausencia['observaciones']); ?></textarea>
                            <div class="form-text">Notas adicionales sobre esta ausencia.</div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        <a href="<?php echo BASE_URL; ?>?page=ausencias/ver&id=<?php echo $id_ausencia; ?>" class="btn btn-secondary">Cancelar</a>
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
    
    // Gestionar la interacción entre subir nuevo archivo y eliminar el existente
    const archivoInput = document.getElementById('archivo_justificacion');
    const eliminarArchivoCheck = document.getElementById('eliminar_archivo');
    
    if (eliminarArchivoCheck) {
        archivoInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                eliminarArchivoCheck.checked = true;
                eliminarArchivoCheck.disabled = true;
            } else {
                eliminarArchivoCheck.disabled = false;
            }
        });
    }
});
</script> 