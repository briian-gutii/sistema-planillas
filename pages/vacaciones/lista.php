<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Lista de Vacaciones';
$activeMenu = 'empleados';

// Obtener la lista de vacaciones de la base de datos
$db = getDB();
$query = "SELECT v.*, e.DPI as codigo, 
          CONCAT(e.primer_nombre, ' ', e.segundo_nombre) as nombres, 
          CONCAT(e.primer_apellido, ' ', e.segundo_apellido) as apellidos
          FROM vacaciones v
          JOIN empleados e ON v.id_empleado = e.id_empleado
          ORDER BY v.id_vacaciones DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$vacaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-umbrella-beach fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Gestión de periodos de vacaciones de los empleados</p>

    <!-- Botones de Acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=vacaciones/nuevo" class="btn btn-success btn-sm">
            <i class="fas fa-plus fa-fw"></i> Registrar Vacaciones
        </a>
        <a href="<?php echo BASE_URL; ?>?page=vacaciones/reporte" class="btn btn-info btn-sm">
            <i class="fas fa-file-pdf fa-fw"></i> Generar Reporte
        </a>
    </div>

    <!-- Tabla de Vacaciones -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Vacaciones Registradas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="vacacionesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empleado</th>
                            <th>Periodo Inicio</th>
                            <th>Periodo Fin</th>
                            <th>Días</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($vacaciones) > 0): ?>
                            <?php foreach ($vacaciones as $vacacion): ?>
                                <?php 
                                // Calcular días de vacaciones
                                $dias_correspondientes = $vacacion['dias_correspondientes'];
                                $dias_gozados = $vacacion['dias_gozados'];
                                $dias_pendientes = $vacacion['dias_pendientes'];
                                
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
                                <tr>
                                    <td><?php echo $vacacion['id_vacaciones']; ?></td>
                                    <td><?php echo $vacacion['codigo'] . ' - ' . $vacacion['apellidos'] . ', ' . $vacacion['nombres']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($vacacion['periodo_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($vacacion['periodo_fin'])); ?></td>
                                    <td><?php echo $dias_correspondientes; ?> (<?php echo $dias_gozados; ?> gozados, <?php echo $dias_pendientes; ?> pendientes)</td>
                                    <td><span class="badge bg-<?php echo $estadoBadge; ?>"><?php echo $estado; ?></span></td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>?page=vacaciones/ver&id=<?php echo $vacacion['id_vacaciones']; ?>" class="btn btn-info btn-sm" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($estado == 'Pendiente'): ?>
                                            <a href="<?php echo BASE_URL; ?>?page=vacaciones/editar&id=<?php echo $vacacion['id_vacaciones']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm btn-eliminar-vacacion" data-id="<?php echo $vacacion['id_vacaciones']; ?>" data-bs-toggle="modal" data-bs-target="#modalEliminarVacacion" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay vacaciones registradas</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                <form id="formEliminarVacacion" method="post" action="<?php echo BASE_URL; ?>?page=vacaciones/eliminar">
                    <input type="hidden" name="id_vacacion" id="idVacacion" value="">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Solo manejar el modal, eliminar la inicialización de DataTables
    const botonesEliminarVacacion = document.querySelectorAll('.btn-eliminar-vacacion');
    botonesEliminarVacacion.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idVacacion = this.getAttribute('data-id');
            document.getElementById('idVacacion').value = idVacacion;
        });
    });
});
</script> 