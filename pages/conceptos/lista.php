<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Conceptos de Nómina';
$activeMenu = 'finanzas';

// Obtener la lista de conceptos de la base de datos
$db = getDB();
$query = "SELECT * FROM conceptos_nomina ORDER BY tipo, nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$conceptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-list-alt fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Gestión de conceptos para cálculo de planillas</p>

    <!-- Botones de Acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=conceptos/nuevo" class="btn btn-success btn-sm">
            <i class="fas fa-plus fa-fw"></i> Nuevo Concepto
        </a>
    </div>

    <!-- Tabla de Conceptos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Conceptos de Nómina Registrados</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Fórmula/Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($conceptos) > 0): ?>
                            <?php foreach ($conceptos as $concepto): ?>
                                <?php 
                                // Determinar el tipo de concepto
                                switch ($concepto['tipo']) {
                                    case 'ingreso':
                                        $tipoLabel = 'Ingreso';
                                        $tipoBadge = 'success';
                                        break;
                                    case 'descuento':
                                        $tipoLabel = 'Descuento';
                                        $tipoBadge = 'danger';
                                        break;
                                    case 'provision':
                                        $tipoLabel = 'Provisión';
                                        $tipoBadge = 'warning';
                                        break;
                                    default:
                                        $tipoLabel = 'Otro';
                                        $tipoBadge = 'secondary';
                                }
                                
                                // Formatear valor si es fijo
                                $valorMostrar = $concepto['es_porcentaje'] ? 
                                    $concepto['valor'] . '%' : 
                                    'Q ' . number_format($concepto['valor'], 2);
                                ?>
                                <tr>
                                    <td><?php echo $concepto['id']; ?></td>
                                    <td><?php echo $concepto['codigo']; ?></td>
                                    <td><?php echo $concepto['nombre']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $tipoBadge; ?>"><?php echo $tipoLabel; ?></span>
                                    </td>
                                    <td><?php echo $valorMostrar; ?></td>
                                    <td><?php echo $concepto['formula'] ?: $concepto['descripcion']; ?></td>
                                    <td>
                                        <?php if ($concepto['estado'] == 1): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>?page=conceptos/editar&id=<?php echo $concepto['id']; ?>" 
                                           class="btn btn-primary btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-<?php echo $concepto['estado'] == 1 ? 'danger' : 'success'; ?> btn-sm btn-cambiar-estado" 
                                                data-id="<?php echo $concepto['id']; ?>" 
                                                data-nombre="<?php echo $concepto['nombre']; ?>" 
                                                data-estado="<?php echo $concepto['estado'] == 1 ? 0 : 1; ?>" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalCambiarEstado" 
                                                title="<?php echo $concepto['estado'] == 1 ? 'Desactivar' : 'Activar'; ?>">
                                            <i class="fas fa-<?php echo $concepto['estado'] == 1 ? 'times' : 'check'; ?>"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No hay conceptos registrados</td>
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
                <h5 class="modal-title" id="modalCambiarEstadoLabel">Cambiar Estado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea <span id="accionEstado">activar/desactivar</span> el concepto <strong id="nombreConcepto"></strong>?
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Cambiar el estado de un concepto afectará los cálculos de las próximas planillas.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formCambiarEstado" method="post" action="<?php echo BASE_URL; ?>?page=conceptos/cambiar_estado">
                    <input type="hidden" name="id_concepto" id="idConcepto" value="">
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
    const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');
    botonesCambiarEstado.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idConcepto = this.getAttribute('data-id');
            const nombreConcepto = this.getAttribute('data-nombre');
            const nuevoEstado = this.getAttribute('data-estado');
            const accion = nuevoEstado == 1 ? 'activar' : 'desactivar';
            
            document.getElementById('idConcepto').value = idConcepto;
            document.getElementById('nombreConcepto').textContent = nombreConcepto;
            document.getElementById('nuevoEstado').value = nuevoEstado;
            document.getElementById('accionEstado').textContent = accion;
        });
    });
});
</script> 