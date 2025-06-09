<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || !hasPermission(ROL_ADMIN)) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Bitácora del Sistema';
$activeMenu = 'configuracion';

// Parámetros de filtrado y paginación
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d', strtotime('-7 days'));
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

// Obtener la lista de usuarios para el filtro
$db = getDB();
$query = "SELECT id, nombre, apellidos, email FROM usuarios WHERE estado = 1 ORDER BY nombre, apellidos";
$stmt = $db->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construir la consulta base
$queryBase = "FROM bitacora b
             LEFT JOIN usuarios u ON b.id_usuario = u.id
             WHERE DATE(b.created_at) BETWEEN :fecha_inicio AND :fecha_fin";
$params = [
    ':fecha_inicio' => $fechaInicio,
    ':fecha_fin' => $fechaFin
];

// Agregar filtros adicionales si están definidos
if (!empty($usuario)) {
    $queryBase .= " AND b.id_usuario = :usuario";
    $params[':usuario'] = $usuario;
}

if (!empty($accion)) {
    $queryBase .= " AND b.accion LIKE :accion";
    $params[':accion'] = '%' . $accion . '%';
}

// Obtener el total de registros para la paginación
$query = "SELECT COUNT(*) as total " . $queryBase;
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$totalRegistros = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPaginas = ceil($totalRegistros / $perPage);

// Obtener los registros de la bitácora
$query = "SELECT b.*, u.nombre, u.apellidos, u.email " . $queryBase . " ORDER BY b.created_at DESC LIMIT :offset, :limit";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->execute();
$bitacora = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-history fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Registro de actividades y eventos del sistema</p>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros de Búsqueda</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <input type="hidden" name="page" value="configuracion/bitacora">
                
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fechaInicio; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo $fechaFin; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <select class="form-select" id="usuario" name="usuario">
                        <option value="">-- Todos los usuarios --</option>
                        <?php foreach ($usuarios as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($usuario == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo $user['nombre'] . ' ' . $user['apellidos']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="accion" class="form-label">Acción</label>
                    <input type="text" class="form-control" id="accion" name="accion" value="<?php echo $accion; ?>" placeholder="Búsqueda por acción...">
                </div>
                
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search fa-fw"></i> Filtrar
                    </button>
                    <a href="<?php echo BASE_URL; ?>?page=configuracion/bitacora" class="btn btn-secondary">
                        <i class="fas fa-times fa-fw"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Bitácora -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Registro de Actividades</h6>
            <div>
                <button id="btnExportarCSV" class="btn btn-sm btn-success">
                    <i class="fas fa-file-csv fa-fw"></i> Exportar CSV
                </button>
                <button id="btnExportarPDF" class="btn btn-sm btn-danger">
                    <i class="fas fa-file-pdf fa-fw"></i> Exportar PDF
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>ID</th>
                            <th>Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Detalles</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($bitacora) > 0): ?>
                            <?php foreach ($bitacora as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($log['id_usuario']) {
                                            echo $log['nombre'] . ' ' . $log['apellidos'];
                                            echo '<br><small class="text-muted">' . $log['email'] . '</small>';
                                        } else {
                                            echo '<span class="text-muted">Sistema</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $log['accion']; ?></td>
                                    <td>
                                        <?php if (!empty($log['detalles'])): ?>
                                            <button class="btn btn-sm btn-info btn-ver-detalles" data-detalles='<?php echo htmlspecialchars($log['detalles']); ?>'>
                                                <i class="fas fa-eye"></i> Ver
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">Sin detalles</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $log['ip_address'] ?? '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No hay registros en la bitácora para los filtros seleccionados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <?php if ($totalPaginas > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>?page=configuracion/bitacora&page_num=<?php echo $page - 1; ?>&fecha_inicio=<?php echo $fechaInicio; ?>&fecha_fin=<?php echo $fechaFin; ?>&usuario=<?php echo $usuario; ?>&accion=<?php echo urlencode($accion); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($startPage + 4, $totalPaginas);
                            
                            if ($endPage - $startPage < 4 && $startPage > 1) {
                                $startPage = max(1, $endPage - 4);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>?page=configuracion/bitacora&page_num=<?php echo $i; ?>&fecha_inicio=<?php echo $fechaInicio; ?>&fecha_fin=<?php echo $fechaFin; ?>&usuario=<?php echo $usuario; ?>&accion=<?php echo urlencode($accion); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPaginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>?page=configuracion/bitacora&page_num=<?php echo $page + 1; ?>&fecha_inicio=<?php echo $fechaInicio; ?>&fecha_fin=<?php echo $fechaFin; ?>&usuario=<?php echo $usuario; ?>&accion=<?php echo urlencode($accion); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar detalles -->
<div class="modal fade" id="modalDetalles" tabindex="-1" aria-labelledby="modalDetallesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetallesLabel">Detalles del Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="detallesContenido" class="bg-light p-3 rounded"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable sin paginación (ya tenemos paginación server-side)
    $('#dataTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });
    
    // Manejar el modal de detalles
    const botonesDetalles = document.querySelectorAll('.btn-ver-detalles');
    const modalDetalles = new bootstrap.Modal(document.getElementById('modalDetalles'));
    const detallesContenido = document.getElementById('detallesContenido');
    
    botonesDetalles.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const detalles = this.getAttribute('data-detalles');
            try {
                const detallesJSON = JSON.parse(detalles);
                detallesContenido.textContent = JSON.stringify(detallesJSON, null, 2);
            } catch (e) {
                detallesContenido.textContent = detalles;
            }
            modalDetalles.show();
        });
    });
    
    // Validación del formulario de filtro
    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        const fechaInicio = new Date(document.getElementById('fecha_inicio').value);
        const fechaFin = new Date(document.getElementById('fecha_fin').value);
        
        if (fechaFin < fechaInicio) {
            event.preventDefault();
            alert('La fecha de fin debe ser igual o posterior a la fecha de inicio.');
        }
    });
    
    // Exportar a CSV
    document.getElementById('btnExportarCSV').addEventListener('click', function() {
        window.location.href = `${BASE_URL}?page=configuracion/bitacora_export&format=csv&fecha_inicio=${document.getElementById('fecha_inicio').value}&fecha_fin=${document.getElementById('fecha_fin').value}&usuario=${document.getElementById('usuario').value}&accion=${document.getElementById('accion').value}`;
    });
    
    // Exportar a PDF
    document.getElementById('btnExportarPDF').addEventListener('click', function() {
        window.location.href = `${BASE_URL}?page=configuracion/bitacora_export&format=pdf&fecha_inicio=${document.getElementById('fecha_inicio').value}&fecha_fin=${document.getElementById('fecha_fin').value}&usuario=${document.getElementById('usuario').value}&accion=${document.getElementById('accion').value}`;
    });
});
</script> 