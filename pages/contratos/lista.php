<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Lista de Contratos';
$activeMenu = 'empleados';

// Obtener la lista de contratos de la base de datos
$db = getDB();

// Filtros
$filtroEstado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';
$where = "";

if ($filtroEstado === 'activos') {
    $where = "WHERE (c.estado = 1 OR c.estado IS NULL)"; // Los contratos sin estado se consideran activos
} elseif ($filtroEstado === 'finalizados') {
    $where = "WHERE c.estado = 0";
}

$query = "SELECT c.*, e.DPI as codigo, e.primer_nombre, e.segundo_nombre, e.primer_apellido, e.segundo_apellido, 
          CONCAT(e.primer_apellido, ' ', e.segundo_apellido) as apellidos,
          CONCAT(e.primer_nombre, ' ', e.segundo_nombre) as nombres,
          p.nombre AS puesto
          FROM contratos c
          JOIN empleados e ON c.id_empleado = e.id_empleado
          LEFT JOIN puestos p ON c.id_puesto = p.id_puesto
          $where
          ORDER BY c.fecha_inicio DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Ver la estructura del primer contrato
if (!empty($contratos)) {
    echo "<!-- DEBUG: Estructura del primer contrato -->";
    echo "<!-- ";
    print_r($contratos[0]);
    echo " -->";
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-file-contract fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Gestión de contratos de los empleados</p>

    <!-- Filtros y Botones de Acción -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="btn-group" role="group" aria-label="Filtros de contratos">
                <a href="<?php echo BASE_URL; ?>?page=contratos/lista" class="btn btn-outline-primary <?php if ($filtroEstado === 'todos') echo 'active'; ?>">
                    Todos los contratos
                </a>
                <a href="<?php echo BASE_URL; ?>?page=contratos/lista&estado=activos" class="btn btn-outline-success <?php if ($filtroEstado === 'activos') echo 'active'; ?>">
                    Contratos activos
                </a>
                <a href="<?php echo BASE_URL; ?>?page=contratos/lista&estado=finalizados" class="btn btn-outline-danger <?php if ($filtroEstado === 'finalizados') echo 'active'; ?>">
                    Contratos finalizados
                </a>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?php echo BASE_URL; ?>?page=contratos/nuevo" class="btn btn-success">
                <i class="fas fa-plus fa-fw"></i> Nuevo Contrato
            </a>
        </div>
    </div>

    <!-- Tabla de Contratos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Contratos Registrados</h6>
            <span class="badge bg-info"><?php echo count($contratos); ?> contratos encontrados</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="contratosTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Empleado</th>
                            <th>Puesto</th>
                            <th>Tipo</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Salario</th>
                            <th>Estado</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($contratos) > 0): ?>
                            <?php foreach ($contratos as $contrato): ?>
                                <tr>
                                    <td><?php echo $contrato['id_contrato']; ?></td>
                                    <td><?php echo $contrato['codigo'] . ' - ' . $contrato['apellidos'] . ', ' . $contrato['nombres']; ?></td>
                                    <td><?php echo $contrato['puesto']; ?></td>
                                    <td><?php echo $contrato['tipo_contrato'] ?? 'Indefinido'; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($contrato['fecha_inicio'])); ?></td>
                                    <td>
                                        <?php if ($contrato['fecha_fin']): ?>
                                            <span class="text-danger"><?php echo date('d/m/Y', strtotime($contrato['fecha_fin'])); ?></span>
                                        <?php else: ?>
                                            <span class="text-success">Indefinido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Q <?php echo number_format($contrato['salario'], 2); ?></td>
                                    <td class="text-center">
                                        <?php if (($contrato['estado'] ?? 0) == 1): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Finalizado</span>
                                            <?php if (!empty($contrato['motivo_fin'])): ?>
                                                <br><small class="text-muted"><?php echo $contrato['motivo_fin']; ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo BASE_URL; ?>?page=contratos/ver&id=<?php echo $contrato['id_contrato']; ?>" class="btn btn-info btn-sm" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (($contrato['estado'] ?? 0) == 1): ?>
                                                <a href="<?php echo BASE_URL; ?>?page=contratos/editar&id=<?php echo $contrato['id_contrato']; ?>" class="btn btn-primary btn-sm" title="Editar Contrato">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm btn-finalizar-contrato" 
                                                    data-id="<?php echo $contrato['id_contrato']; ?>" 
                                                    data-empleado="<?php echo $contrato['apellidos'] . ', ' . $contrato['nombres']; ?>" 
                                                    data-bs-toggle="modal" data-bs-target="#modalFinalizarContrato" 
                                                    title="Finalizar Contrato">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-secondary btn-sm" title="Reactivar Contrato" 
                                                    data-id="<?php echo $contrato['id_contrato']; ?>" 
                                                    data-empleado="<?php echo $contrato['apellidos'] . ', ' . $contrato['nombres']; ?>" 
                                                    data-bs-toggle="modal" data-bs-target="#modalReactivarContrato">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No hay contratos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Finalizar Contrato -->
<div class="modal fade" id="modalFinalizarContrato" tabindex="-1" aria-labelledby="modalFinalizarContratoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalFinalizarContratoLabel">Finalizar Contrato</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formFinalizarContrato" method="post" action="<?php echo BASE_URL; ?>?page=contratos/finalizar">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> ¿Está seguro que desea finalizar el contrato de <strong id="empleadoNombre"></strong>?
                    </div>
                    <div class="mb-3">
                        <label for="fecha_fin" class="form-label">Fecha de Finalización *</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                    </div>
                    <div class="mb-3">
                        <label for="motivo_fin" class="form-label">Motivo de Finalización *</label>
                        <select class="form-select" id="motivo_fin" name="motivo_fin" required>
                            <option value="">Seleccione un motivo</option>
                            <option value="Renuncia">Renuncia</option>
                            <option value="Despido">Despido con justa causa</option>
                            <option value="Despido sin justa causa">Despido sin justa causa</option>
                            <option value="Fin de contrato">Fin de contrato</option>
                            <option value="Jubilación">Jubilación</option>
                            <option value="Fallecimiento">Fallecimiento</option>
                            <option value="Mutuo acuerdo">Mutuo acuerdo</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Detalles adicionales sobre la finalización del contrato..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id_contrato" id="idContrato" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times-circle fa-fw"></i> Finalizar Contrato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reactivar Contrato -->
<div class="modal fade" id="modalReactivarContrato" tabindex="-1" aria-labelledby="modalReactivarContratoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalReactivarContratoLabel">Reactivar Contrato</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formReactivarContrato" method="post" action="<?php echo BASE_URL; ?>?page=contratos/reactivar">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Va a reactivar el contrato de <strong id="empleadoNombreReactivar"></strong>
                    </div>
                    <div class="mb-3">
                        <label for="observaciones_reactivar" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_reactivar" name="observaciones" rows="3" placeholder="Razón para reactivar el contrato..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id_contrato" id="idContratoReactivar" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle fa-fw"></i> Reactivar Contrato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable para búsqueda y paginación
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#contratosTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            order: [[0, 'desc']], // Ordenar por ID de contrato descendente
            pageLength: 10
        });
    }
    
    // Manejar modal de finalizar contrato
    const botonesFinalizarContrato = document.querySelectorAll('.btn-finalizar-contrato');
    botonesFinalizarContrato.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idContrato = this.getAttribute('data-id');
            const empleadoNombre = this.getAttribute('data-empleado');
            
            document.getElementById('idContrato').value = idContrato;
            document.getElementById('empleadoNombre').textContent = empleadoNombre;
            
            // Establecer fecha actual como fecha por defecto
            const hoy = new Date();
            const fechaHoy = hoy.getFullYear() + '-' + 
                            String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(hoy.getDate()).padStart(2, '0');
            document.getElementById('fecha_fin').value = fechaHoy;
        });
    });
    
    // Manejar modal de reactivar contrato
    const botonesReactivar = document.querySelectorAll('[data-bs-target="#modalReactivarContrato"]');
    botonesReactivar.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idContrato = this.getAttribute('data-id');
            const empleadoNombre = this.getAttribute('data-empleado');
            
            document.getElementById('idContratoReactivar').value = idContrato;
            document.getElementById('empleadoNombreReactivar').textContent = empleadoNombre;
        });
    });
});
</script> 