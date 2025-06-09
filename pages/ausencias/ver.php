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
$pageTitle = 'Detalles de Ausencia';
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

// Calcular días de ausencia
$inicio = new DateTime($ausencia['fecha_inicio']);
$fin = new DateTime($ausencia['fecha_fin']);
$diff = $inicio->diff($fin);
$dias = $diff->days + 1; // Incluir el día de inicio

// Determinar el tipo de ausencia
switch ($ausencia['tipo']) {
    case 'Enfermedad común':
        $tipoBadge = 'primary';
        break;
    case 'Vacaciones':
        $tipoBadge = 'success';
        break;
    case 'Suspensión IGSS':
        $tipoBadge = 'warning';
        break;
    case 'Licencia':
        $tipoBadge = 'info';
        break;
    case 'Permiso':
        $tipoBadge = 'secondary';
        break;
    case 'Falta injustificada':
        $tipoBadge = 'danger';
        break;
    default:
        $tipoBadge = 'secondary';
}

// Obtener información del archivo si existe
$tiene_archivo = false;
$archivo_tipo = '';
if (!empty($ausencia['archivo_justificacion'])) {
    $tiene_archivo = true;
    $ruta_archivo = 'uploads/justificaciones/' . $ausencia['archivo_justificacion'];
    $extension = strtolower(pathinfo($ausencia['archivo_justificacion'], PATHINFO_EXTENSION));
    
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        $archivo_tipo = 'imagen';
    } elseif ($extension == 'pdf') {
        $archivo_tipo = 'pdf';
    } else {
        $archivo_tipo = 'documento';
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-calendar-times fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Información detallada de la ausencia</p>

    <!-- Botones de Acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=ausencias/lista" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-fw"></i> Volver a la Lista
        </a>
        <a href="<?php echo BASE_URL; ?>?page=ausencias/editar&id=<?php echo $ausencia['id_ausencia']; ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-edit fa-fw"></i> Editar Ausencia
        </a>
    </div>

    <!-- Detalles de la Ausencia -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información de Ausencia #<?php echo $ausencia['id_ausencia']; ?></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Empleado</h5>
                            <p><strong>Código:</strong> <?php echo htmlspecialchars($ausencia['codigo']); ?></p>
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($ausencia['apellidos'] . ', ' . $ausencia['nombres']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Información de Ausencia</h5>
                            <p>
                                <strong>Tipo:</strong> 
                                <span class="badge bg-<?php echo $tipoBadge; ?>"><?php echo htmlspecialchars($ausencia['tipo']); ?></span>
                            </p>
                            <p><strong>Fecha Inicio:</strong> <?php echo date('d/m/Y', strtotime($ausencia['fecha_inicio'])); ?></p>
                            <p><strong>Fecha Fin:</strong> <?php echo date('d/m/Y', strtotime($ausencia['fecha_fin'])); ?></p>
                            <p><strong>Total Días:</strong> <?php echo $dias; ?></p>
                            <p>
                                <strong>Justificada:</strong> 
                                <?php if (isset($ausencia['justificada']) && $ausencia['justificada'] == 1): ?>
                                    <span class="badge bg-success">Sí</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($ausencia['justificacion'])): ?>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Justificación</h5>
                            <div class="p-3 border rounded">
                                <?php echo nl2br(htmlspecialchars($ausencia['justificacion'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($tiene_archivo): ?>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Documento de Respaldo</h5>
                            <div class="p-3 border rounded">
                                <p><strong>Archivo:</strong> <?php echo htmlspecialchars($ausencia['archivo_justificacion']); ?></p>
                                
                                <div class="mt-2">
                                    <a href="<?php echo BASE_URL . 'uploads/justificaciones/' . $ausencia['archivo_justificacion']; ?>" 
                                       class="btn btn-info btn-sm" target="_blank">
                                        <i class="fas fa-download fa-fw"></i> Descargar Archivo
                                    </a>
                                    
                                    <?php if ($archivo_tipo == 'imagen'): ?>
                                    <div class="mt-3">
                                        <img src="<?php echo BASE_URL . 'uploads/justificaciones/' . $ausencia['archivo_justificacion']; ?>" 
                                             class="img-fluid img-thumbnail" style="max-height: 300px;" alt="Justificación">
                                    </div>
                                    <?php elseif ($archivo_tipo == 'pdf'): ?>
                                    <div class="mt-3">
                                        <iframe src="<?php echo BASE_URL . 'uploads/justificaciones/' . $ausencia['archivo_justificacion']; ?>" 
                                                style="width: 100%; height: 500px;" frameborder="0"></iframe>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($ausencia['observaciones'])): ?>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Observaciones</h5>
                            <div class="p-3 border rounded">
                                <?php echo nl2br(htmlspecialchars($ausencia['observaciones'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 