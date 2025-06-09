<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Lista de Empleados';
$activeMenu = 'empleados';

// Obtener la lista de empleados de la base de datos
$db = getDB();
$query = "SELECT * FROM empleados ORDER BY estado, primer_apellido, primer_nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-users fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Gestión de empleados del sistema de planillas</p>

    <!-- Botones de Acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=empleados/nuevo" class="btn btn-success btn-sm">
            <i class="fas fa-user-plus fa-fw"></i> Nuevo Empleado
        </a>
        <a href="<?php echo BASE_URL; ?>?page=empleados/reporte" class="btn btn-info btn-sm">
            <i class="fas fa-file-pdf fa-fw"></i> Generar Reporte
        </a>
    </div>

    <!-- Tabla de Empleados -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Empleados Registrados</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>DPI</th>
                            <th>Nombre Completo</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Fecha Ingreso</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($empleados) > 0): ?>
                            <?php foreach ($empleados as $empleado): ?>
                                <tr>
                                    <td><?php echo $empleado['DPI']; ?></td>
                                    <td>
                                        <?php echo $empleado['primer_apellido'] . ' ' . 
                                                  $empleado['segundo_apellido'] . ', ' . 
                                                  $empleado['primer_nombre'] . ' ' . 
                                                  $empleado['segundo_nombre']; ?>
                                    </td>
                                    <td><?php echo $empleado['telefono']; ?></td>
                                    <td><?php echo $empleado['email']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($empleado['fecha_ingreso'])); ?></td>
                                    <td>
                                        <?php if ($empleado['estado'] == 'Activo'): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php elseif ($empleado['estado'] == 'Inactivo'): ?>
                                            <span class="badge bg-danger">Inactivo</span>
                                        <?php elseif ($empleado['estado'] == 'Suspendido'): ?>
                                            <span class="badge bg-warning">Suspendido</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo $empleado['estado']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>?page=empleados/ver&id=<?php echo $empleado['id_empleado']; ?>" 
                                           class="btn btn-info btn-sm" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>?page=empleados/editar&id=<?php echo $empleado['id_empleado']; ?>" 
                                           class="btn btn-primary btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($empleado['estado'] == 'Activo'): ?>
                                            <button type="button" class="btn btn-danger btn-sm btn-cambiar-estado" 
                                                    data-id="<?php echo $empleado['id_empleado']; ?>" 
                                                    data-estado="Inactivo" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalCambiarEstado" 
                                                    title="Desactivar">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                        <?php elseif ($empleado['estado'] != 'Activo'): ?>
                                            <button type="button" class="btn btn-success btn-sm btn-cambiar-estado" 
                                                    data-id="<?php echo $empleado['id_empleado']; ?>" 
                                                    data-estado="Activo" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalCambiarEstado" 
                                                    title="Activar">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay empleados registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cambiar Estado -->
<div class="modal fade" id="modalCambiarEstado" tabindex="-1" aria-labelledby="modalCambiarEstadoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCambiarEstadoLabel">Confirmar Cambio de Estado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea cambiar el estado de este empleado?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formCambiarEstado" method="post" action="<?php echo BASE_URL; ?>?page=empleados/cambiar_estado">
                    <input type="hidden" name="id_empleado" id="idEmpleado" value="">
                    <input type="hidden" name="nuevo_estado" id="nuevoEstado" value="">
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#dataTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });
    
    // Manejar el modal de cambiar estado
    const botonesEstado = document.querySelectorAll('.btn-cambiar-estado');
    botonesEstado.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idEmpleado = this.getAttribute('data-id');
            const nuevoEstado = this.getAttribute('data-estado');
            
            document.getElementById('idEmpleado').value = idEmpleado;
            document.getElementById('nuevoEstado').value = nuevoEstado;
        });
    });
});
</script> 