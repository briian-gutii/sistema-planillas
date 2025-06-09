<?php

// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Listado de Períodos';
$activeMenu = 'configuracion';

// Verificar si la tabla existe
$db = getDB();
$tablaExiste = false;
try {
    $checkTable = $db->query("SHOW TABLES LIKE 'periodos'");
    $tablaExiste = ($checkTable->rowCount() > 0);
} catch (Exception $e) {
    // Si hay un error, asumimos que la tabla no existe
    $tablaExiste = false;
}

$periodos = [];

if ($tablaExiste) {
    try {
        // Obtener la lista de períodos
        $query = "SELECT * FROM periodos ORDER BY fecha_inicio DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        setFlashMessage('Error al cargar los períodos: ' . $e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-calendar-alt fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Administración de períodos de nómina</p>

    <?php if (!$tablaExiste): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> La tabla de períodos no existe todavía. Debe crearla antes de usar esta funcionalidad.
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Crear tabla de períodos</h6>
        </div>
        <div class="card-body">
            <p>Para crear la tabla de períodos, puede ejecutar el siguiente SQL en su base de datos:</p>
            <pre class="bg-light p-3">
CREATE TABLE `periodos` (
  `id_periodo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `tipo` enum('Mensual','Quincenal','Semanal') NOT NULL DEFAULT 'Quincenal',
  `descripcion` text,
  `estado` enum('Activo','Cerrado') NOT NULL DEFAULT 'Activo',
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            </pre>
            <p>Una vez creada la tabla, podrá administrar los períodos de nómina.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Botones de Acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=periodos/nuevo" class="btn btn-success btn-sm">
            <i class="fas fa-plus fa-fw"></i> Nuevo Período
        </a>
    </div>

    <!-- Tabla de Períodos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Períodos Registrados</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="periodosTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($periodos) > 0): ?>
                            <?php foreach ($periodos as $periodo): ?>
                                <tr>
                                    <td><?php echo $periodo['id_periodo']; ?></td>
                                    <td><?php echo $periodo['nombre']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($periodo['fecha_fin'])); ?></td>
                                    <td><?php echo $periodo['tipo']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $periodo['estado'] == 'Activo' ? 'success' : 'secondary'; ?>">
                                            <?php echo $periodo['estado']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>?page=periodos/editar&id=<?php echo $periodo['id_periodo']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($periodo['estado'] == 'Activo'): ?>
                                            <button type="button" class="btn btn-warning btn-sm btn-cerrar-periodo" data-id="<?php echo $periodo['id_periodo']; ?>" data-bs-toggle="modal" data-bs-target="#modalCerrarPeriodo" title="Cerrar Período">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-danger btn-sm btn-eliminar-periodo" data-id="<?php echo $periodo['id_periodo']; ?>" data-bs-toggle="modal" data-bs-target="#modalEliminarPeriodo" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay períodos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Cerrar Período -->
    <div class="modal fade" id="modalCerrarPeriodo" tabindex="-1" aria-labelledby="modalCerrarPeriodoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCerrarPeriodoLabel">Confirmar Cierre de Período</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro que desea cerrar este período? Una vez cerrado, no podrá generar planillas para este período.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="<?php echo BASE_URL; ?>?page=periodos/cerrar">
                        <input type="hidden" name="id_periodo" id="idPeriodoCerrar" value="">
                        <button type="submit" class="btn btn-warning">Cerrar Período</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Período -->
    <div class="modal fade" id="modalEliminarPeriodo" tabindex="-1" aria-labelledby="modalEliminarPeriodoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEliminarPeriodoLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar este período? Esta acción no se puede deshacer.</p>
                    <div class="alert alert-danger">
                        <strong>Advertencia:</strong> Eliminar un período podría afectar a las planillas asociadas a este período.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="<?php echo BASE_URL; ?>?page=periodos/eliminar">
                        <input type="hidden" name="id_periodo" id="idPeriodoEliminar" value="">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar DataTables
        $('#periodosTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
            }
        });
        
        // Manejar modal de cerrar período
        const botonesCerrarPeriodo = document.querySelectorAll('.btn-cerrar-periodo');
        botonesCerrarPeriodo.forEach(function(boton) {
            boton.addEventListener('click', function() {
                const idPeriodo = this.getAttribute('data-id');
                document.getElementById('idPeriodoCerrar').value = idPeriodo;
            });
        });
        
        // Manejar modal de eliminar período
        const botonesEliminarPeriodo = document.querySelectorAll('.btn-eliminar-periodo');
        botonesEliminarPeriodo.forEach(function(boton) {
            boton.addEventListener('click', function() {
                const idPeriodo = this.getAttribute('data-id');
                document.getElementById('idPeriodoEliminar').value = idPeriodo;
            });
        });
    });
    </script>
    <?php endif; ?> <!-- Fin del if tablaExiste -->
</div> 