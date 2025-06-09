<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Lista de Ausencias';
$activeMenu = 'empleados';

// Obtener la lista de ausencias de la base de datos
$db = getDB();
$query = "SELECT a.*, e.DPI as codigo, 
          CONCAT(e.primer_nombre, ' ', IFNULL(e.segundo_nombre, '')) as nombres, 
          CONCAT(e.primer_apellido, ' ', IFNULL(e.segundo_apellido, '')) as apellidos
          FROM ausencias a
          JOIN empleados e ON a.id_empleado = e.id_empleado
          ORDER BY a.fecha_inicio DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$ausencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-calendar-times fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Gestión de ausencias de los empleados</p>

    <!-- Botones de Acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=ausencias/nuevo" class="btn btn-success btn-sm">
            <i class="fas fa-plus fa-fw"></i> Registrar Ausencia
        </a>
    </div>

    <!-- Tabla de Ausencias -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Ausencias Registradas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="ausenciasTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empleado</th>
                            <th>Tipo</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Días</th>
                            <th>Justificada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ausencias) > 0): ?>
                            <?php foreach ($ausencias as $ausencia): ?>
                                <?php 
                                // Calcular días de ausencia
                                $inicio = new DateTime($ausencia['fecha_inicio']);
                                $fin = new DateTime($ausencia['fecha_fin']);
                                $diff = $inicio->diff($fin);
                                $dias = $diff->days + 1; // Incluir el día de inicio
                                
                                // Determinar el tipo de ausencia
                                switch ($ausencia['tipo']) {
                                    case 'Enfermedad común':
                                        $tipoLabel = 'Enfermedad común';
                                        $tipoBadge = 'primary';
                                        break;
                                    case 'Vacaciones':
                                        $tipoLabel = 'Vacaciones';
                                        $tipoBadge = 'success';
                                        break;
                                    case 'Suspensión IGSS':
                                        $tipoLabel = 'Suspensión IGSS';
                                        $tipoBadge = 'warning';
                                        break;
                                    case 'Licencia':
                                        $tipoLabel = 'Licencia';
                                        $tipoBadge = 'info';
                                        break;
                                    case 'Permiso':
                                        $tipoLabel = 'Permiso';
                                        $tipoBadge = 'secondary';
                                        break;
                                    case 'Falta injustificada':
                                        $tipoLabel = 'Falta injustificada';
                                        $tipoBadge = 'danger';
                                        break;
                                    default:
                                        $tipoLabel = $ausencia['tipo'];
                                        $tipoBadge = 'secondary';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $ausencia['id_ausencia']; ?></td>
                                    <td><?php echo $ausencia['codigo'] . ' - ' . $ausencia['apellidos'] . ', ' . $ausencia['nombres']; ?></td>
                                    <td><span class="badge bg-<?php echo $tipoBadge; ?>"><?php echo $tipoLabel; ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($ausencia['fecha_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($ausencia['fecha_fin'])); ?></td>
                                    <td><?php echo $dias; ?></td>
                                    <td><?php if (!empty($ausencia['justificacion'])): ?><span class="badge bg-success">Sí</span><?php else: ?><span class="badge bg-danger">No</span><?php endif; ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>?page=ausencias/ver&id=<?php echo $ausencia['id_ausencia']; ?>" class="btn btn-info btn-sm" title="Ver Detalles"><i class="fas fa-eye"></i></a>
                                        <a href="<?php echo BASE_URL; ?>?page=ausencias/editar&id=<?php echo $ausencia['id_ausencia']; ?>" class="btn btn-primary btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                                        <button type="button" class="btn btn-danger btn-sm btn-eliminar-ausencia" data-id="<?php echo $ausencia['id_ausencia']; ?>" data-bs-toggle="modal" data-bs-target="#modalEliminarAusencia" title="Eliminar"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No hay ausencias registradas</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar Ausencia -->
<div class="modal fade" id="modalEliminarAusencia" tabindex="-1" aria-labelledby="modalEliminarAusenciaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarAusenciaLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar esta ausencia? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminarAusencia" method="post" action="<?php echo BASE_URL; ?>?page=ausencias/eliminar">
                    <input type="hidden" name="id_ausencia" id="idAusencia" value="">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar el modal de eliminar ausencia
    const botonesEliminarAusencia = document.querySelectorAll('.btn-eliminar-ausencia');
    botonesEliminarAusencia.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idAusencia = this.getAttribute('data-id');
            document.getElementById('idAusencia').value = idAusencia;
        });
    });
});
</script> 