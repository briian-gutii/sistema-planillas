<?php

// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Detalles de Vacaciones';
$activeMenu = 'empleados';

// Obtener el ID de las vacaciones
$id_vacaciones = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_vacaciones <= 0) {
    setFlashMessage('ID de vacaciones no válido', 'danger');
    header('Location: ' . BASE_URL . '?page=vacaciones/lista');
    exit;
}

// Obtener datos de las vacaciones
$db = getDB();
$query = "SELECT v.*, e.DPI as codigo, 
          CONCAT(e.primer_nombre, ' ', e.segundo_nombre) as nombres, 
          CONCAT(e.primer_apellido, ' ', e.segundo_apellido) as apellidos
          FROM vacaciones v
          JOIN empleados e ON v.id_empleado = e.id_empleado
          WHERE v.id_vacaciones = :id_vacaciones";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_vacaciones', $id_vacaciones, PDO::PARAM_INT);
$stmt->execute();
$vacacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vacacion) {
    setFlashMessage('Registro de vacaciones no encontrado', 'danger');
    header('Location: ' . BASE_URL . '?page=vacaciones/lista');
    exit;
}

// Determinar el estado de las vacaciones
$hoy = new DateTime();
$periodo_inicio = new DateTime($vacacion['periodo_inicio']);
$periodo_fin = new DateTime($vacacion['periodo_fin']);

if ($hoy < $periodo_inicio) {
    $estado = 'Pendiente';
    $estadoBadge = 'warning';
} elseif ($hoy > $periodo_fin) {
    $estado = 'Completado';
    $estadoBadge = 'success';
} else {
    $estado = 'En curso';
    $estadoBadge = 'primary';
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-umbrella-beach fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Información detallada sobre el período de vacaciones</p>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Datos de Vacaciones</h6>
                    <div>
                        <span class="badge bg-<?php echo $estadoBadge; ?>"><?php echo $estado; ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 30%">ID Vacaciones</th>
                                <td><?php echo $vacacion['id_vacaciones']; ?></td>
                            </tr>
                            <tr>
                                <th>Empleado</th>
                                <td><?php echo $vacacion['codigo'] . ' - ' . $vacacion['apellidos'] . ', ' . $vacacion['nombres']; ?></td>
                            </tr>
                            <tr>
                                <th>Período</th>
                                <td>
                                    Del <?php echo date('d/m/Y', strtotime($vacacion['periodo_inicio'])); ?> 
                                    al <?php echo date('d/m/Y', strtotime($vacacion['periodo_fin'])); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Días Correspondientes</th>
                                <td><?php echo $vacacion['dias_correspondientes']; ?></td>
                            </tr>
                            <tr>
                                <th>Días Gozados</th>
                                <td><?php echo $vacacion['dias_gozados']; ?></td>
                            </tr>
                            <tr>
                                <th>Días Pendientes</th>
                                <td><?php echo $vacacion['dias_pendientes']; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Acciones</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>?page=vacaciones/lista" class="btn btn-secondary">
                            <i class="fas fa-arrow-left fa-fw"></i> Volver a la Lista
                        </a>
                        
                        <?php if ($estado == 'Pendiente'): ?>
                            <a href="<?php echo BASE_URL; ?>?page=vacaciones/editar&id=<?php echo $vacacion['id_vacaciones']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit fa-fw"></i> Editar
                            </a>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarVacacion">
                                <i class="fas fa-trash fa-fw"></i> Eliminar
                            </button>
                        <?php endif; ?>
                        
                        <a href="<?php echo BASE_URL; ?>?page=vacaciones/imprimir&id=<?php echo $vacacion['id_vacaciones']; ?>" class="btn btn-info" target="_blank">
                            <i class="fas fa-print fa-fw"></i> Imprimir Constancia
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar Vacación -->
<div class="modal fade" id="modalEliminarVacacion" tabindex="-1" aria-labelledby="modalEliminarVacacionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarVacacionLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar este registro de vacaciones? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" action="<?php echo BASE_URL; ?>?page=vacaciones/eliminar">
                    <input type="hidden" name="id_vacacion" value="<?php echo $vacacion['id_vacaciones']; ?>">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div> 