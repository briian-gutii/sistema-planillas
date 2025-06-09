<?php

// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Listado de Planillas';
$activeMenu = 'planillas';

// Obtener parámetros de filtro
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '';
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Obtener la lista de planillas
$db = getDB();

// Verificar si la tabla existe antes de consultar
$tablaExiste = false;
try {
    $checkTable = $db->query("SHOW TABLES LIKE 'planillas'");
    $tablaExiste = ($checkTable->rowCount() > 0);
} catch (Exception $e) {
    // Si hay un error, asumimos que la tabla no existe
    $tablaExiste = false;
}

$planillas = [];
$periodosData = []; // Inicializar variable antes de usarla

if ($tablaExiste) {
    // Construir la consulta con filtros opcionales
    try {
        // Verificar si existe la columna tipo_planilla en la tabla
        $tieneTipoPlanilla = false;
        try {
            $checkColumns = $db->query("SHOW COLUMNS FROM planillas LIKE 'id_tipo_planilla'");
            $tieneTipoPlanilla = ($checkColumns->rowCount() > 0);
        } catch (Exception $e) {
            // Si hay error, asumimos que no existe
            $tieneTipoPlanilla = false;
        }
        
        $query = "SELECT p.id_planilla, ";
        
        // Si existe la columna tipo_planilla, la incluimos, si no, usamos valor por defecto
        if ($tieneTipoPlanilla) {
            $query .= "p.tipo_planilla, ";
        } else {
            $query .= "'No disponible' as tipo_planilla, ";
        }
        
        $query .= "p.fecha_generacion, p.estado, 
                 IFNULL(p.id_periodo, 0) as id_periodo, 
                 IFNULL(COUNT(pd.id_empleado), 0) as total_empleados,
                 IFNULL(SUM(pd.salario_base), 0) as total_sueldos, 
                 IFNULL(SUM(pd.total_deducciones), 0) as total_deducciones,
                 IFNULL(SUM(pd.liquido_recibir), 0) as total_liquido
                 FROM planillas p
                 LEFT JOIN Detalle_Planilla pd ON p.id_planilla = pd.id_planilla
                 WHERE 1=1 ";

        $params = [];

        if (!empty($periodo)) {
            $query .= " AND p.id_periodo = :periodo";
            $params[':periodo'] = $periodo;
        }

        // Aplicar filtro de tipo solo si la columna existe
        if (!empty($tipo) && $tieneTipoPlanilla) {
            $query .= " AND p.tipo_planilla = :tipo";
            $params[':tipo'] = $tipo;
        }

        if (!empty($estado)) {
            $query .= " AND p.estado = :estado";
            $params[':estado'] = $estado;
        }

        $query .= " GROUP BY p.id_planilla ORDER BY p.fecha_generacion DESC";
        $stmt = $db->prepare($query);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->execute();
        $planillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Asegurarse que todas las planillas tengan un nombre_periodo predeterminado
        foreach ($planillas as &$planilla) {
            // Inicializar con un valor predeterminado
            $planilla['nombre_periodo'] = 'Periodo ID ' . $planilla['id_periodo'];
        }
        
        // Verificar si existe la tabla periodos_pago
        try {
            $checkPeriodos = $db->query("SHOW TABLES LIKE 'periodos_pago'");
            if ($checkPeriodos->rowCount() > 0) {
                // Obtener periodos para filtro
                $queryPeriodos = "SELECT id_periodo_pago as id_periodo, anio, mes, CONCAT(LPAD(mes, 2, '0'), '/', anio) as nombre_periodo_generado FROM periodos_pago ORDER BY anio DESC, mes DESC";
                $stmtPeriodos = $db->prepare($queryPeriodos);
                $stmtPeriodos->execute();
                $periodosData = $stmtPeriodos->fetchAll(PDO::FETCH_ASSOC); // Renamed to avoid conflict with $periodo filter variable
                
                // Actualizar nombres de períodos en las planillas
                foreach ($planillas as &$planilla) {
                    // Buscar el período correspondiente
                    $encontrado = false;
                    foreach ($periodosData as $pData) { // Use renamed variable
                        if ($pData['id_periodo'] == $planilla['id_periodo']) {
                            $planilla['nombre_periodo'] = $pData['nombre_periodo_generado'];
                            $encontrado = true;
                            break;
                        }
                    }
                    
                    // Ya no es necesario el else aquí porque ya inicializamos arriba
                }
            } else {
                // Si no existe la tabla, crear un arreglo vacío
                $periodosData = []; // Use renamed variable
            }
        } catch (Exception $e) {
            // Si hay error, usar un arreglo vacío
            $periodosData = []; // Use renamed variable
            setFlashMessage('Error al cargar los periodos: ' . $e->getMessage(), 'warning'); // Optionally log or show a warning
        }
    } catch (Exception $e) {
        setFlashMessage('Error al cargar las planillas: ' . $e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-file-invoice-dollar fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Administración de planillas de pago</p>

    <?php if (!$tablaExiste): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> La tabla de planillas no existe todavía. Debe crearla antes de usar esta funcionalidad.
    </div>
    <?php else: ?>
    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
        </div>
        <div class="card-body">
            <form method="get" action="">
                <input type="hidden" name="page" value="planillas/lista">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="periodo" class="form-label">Periodo</label>
                            <select class="form-select" id="periodo" name="periodo">
                                <option value="">Todos los periodos</option>
                                <?php foreach($periodosData as $p): // Use renamed variable ?>
                                    <option value="<?php echo $p['id_periodo']; ?>" <?php if(isset($_GET['periodo']) && $_GET['periodo'] == $p['id_periodo']) echo 'selected'; ?>>
                                        <?php echo $p['nombre_periodo_generado']; // Use generated name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo" <?php if (!isset($tieneTipoPlanilla) || !$tieneTipoPlanilla) echo 'disabled'; ?>>
                                <option value="">Todos los tipos</option>
                                <option value="Ordinaria" <?php if($tipo == 'Ordinaria') echo 'selected'; ?>>Ordinaria</option>
                                <option value="Extraordinaria" <?php if($tipo == 'Extraordinaria') echo 'selected'; ?>>Extraordinaria</option>
                                <option value="Aguinaldo" <?php if($tipo == 'Aguinaldo') echo 'selected'; ?>>Aguinaldo</option>
                                <option value="Bono14" <?php if($tipo == 'Bono14') echo 'selected'; ?>>Bono 14</option>
                            </select>
                            <?php if (!isset($tieneTipoPlanilla) || !$tieneTipoPlanilla): ?>
                            <div class="form-text text-warning">
                                <small><i class="fas fa-exclamation-circle"></i> La columna id_tipo_planilla no existe en la base de datos</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="Borrador" <?php if($estado == 'Borrador') echo 'selected'; ?>>Borrador</option>
                                <option value="Aprobada" <?php if($estado == 'Aprobada') echo 'selected'; ?>>Aprobada</option>
                                <option value="Pagada" <?php if($estado == 'Pagada') echo 'selected'; ?>>Pagada</option>
                                <option value="Anulada" <?php if($estado == 'Anulada') echo 'selected'; ?>>Anulada</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter fa-fw"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=planillas/generar" class="btn btn-success btn-sm">
            <i class="fas fa-plus fa-fw"></i> Generar Nueva Planilla
        </a>
        <a href="<?php echo BASE_URL; ?>?page=planillas/importar" class="btn btn-secondary btn-sm">
            <i class="fas fa-file-import fa-fw"></i> Importar Datos
        </a>
    </div>

    <!-- Tabla de Planillas -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Planillas Registradas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <!-- Eliminar la clase datatable para evitar que se inicialice con DataTables -->
                <table class="table table-bordered table-striped" id="planillasTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Período</th>
                            <th>Tipo</th>
                            <th>Fecha Generación</th>
                            <th>Empleados</th>
                            <th>Total Sueldos</th>
                            <th>Total Deducciones</th>
                            <th>Total Líquido</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($planillas) > 0): ?>
                            <?php foreach ($planillas as $planilla): ?>
                                <?php 
                                // Determinar el color del badge según el estado
                                $estadoBadge = 'secondary';
                                switch ($planilla['estado']) {
                                    case 'Borrador':
                                        $estadoBadge = 'warning';
                                        break;
                                    case 'Aprobada':
                                        $estadoBadge = 'success';
                                        break;
                                    case 'Pagada':
                                        $estadoBadge = 'primary';
                                        break;
                                    case 'Anulada':
                                        $estadoBadge = 'danger';
                                        break;
                                }
                                ?>
                                <tr>
                                    <td><?php echo $planilla['id_planilla']; ?></td>
                                    <td><?php echo isset($planilla['nombre_periodo']) ? $planilla['nombre_periodo'] : 'Periodo ID ' . $planilla['id_periodo']; ?></td>
                                    <td><?php echo $planilla['tipo_planilla']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($planilla['fecha_generacion'])); ?></td>
                                    <td><?php echo $planilla['total_empleados']; ?></td>
                                    <td><?php echo formatMoney($planilla['total_sueldos']); ?></td>
                                    <td><?php echo formatMoney($planilla['total_deducciones']); ?></td>
                                    <td><?php echo formatMoney($planilla['total_liquido']); ?></td>
                                    <td><span class="badge bg-<?php echo $estadoBadge; ?>"><?php echo $planilla['estado']; ?></span></td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>?page=planillas/ver&id=<?php echo $planilla['id_planilla']; ?>" class="btn btn-info btn-sm" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($planilla['estado'] == 'Borrador'): ?>
                                            <a href="<?php echo BASE_URL; ?>?page=planillas/editar&id=<?php echo $planilla['id_planilla']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-success btn-sm btn-aprobar-planilla" data-id="<?php echo $planilla['id_planilla']; ?>" data-bs-toggle="modal" data-bs-target="#modalAprobarPlanilla" title="Aprobar">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm btn-anular-planilla" data-id="<?php echo $planilla['id_planilla']; ?>" data-bs-toggle="modal" data-bs-target="#modalAnularPlanilla" title="Anular">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php elseif ($planilla['estado'] == 'Aprobada'): ?>
                                            <button type="button" class="btn btn-primary btn-sm btn-pagar-planilla" data-id="<?php echo $planilla['id_planilla']; ?>" data-bs-toggle="modal" data-bs-target="#modalPagarPlanilla" title="Marcar como Pagada">
                                                <i class="fas fa-dollar-sign"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="<?php echo BASE_URL; ?>?page=planillas/imprimir&id=<?php echo $planilla['id_planilla']; ?>" class="btn btn-secondary btn-sm" target="_blank" title="Imprimir">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No hay planillas registradas con los filtros seleccionados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modales -->
    <!-- Modal Aprobar Planilla -->
    <div class="modal fade" id="modalAprobarPlanilla" tabindex="-1" aria-labelledby="modalAprobarPlanillaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAprobarPlanillaLabel">Confirmar Aprobación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro que desea aprobar esta planilla? Una vez aprobada, no podrá editar los detalles.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="<?php echo BASE_URL; ?>?page=planillas/aprobar">
                        <input type="hidden" name="id_planilla" id="idPlanillaAprobar" value="">
                        <button type="submit" class="btn btn-success">Aprobar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Anular Planilla -->
    <div class="modal fade" id="modalAnularPlanilla" tabindex="-1" aria-labelledby="modalAnularPlanillaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAnularPlanillaLabel">Confirmar Anulación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro que desea anular esta planilla? Esta acción no se puede deshacer.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="<?php echo BASE_URL; ?>?page=planillas/anular">
                        <input type="hidden" name="id_planilla" id="idPlanillaAnular" value="">
                        <button type="submit" class="btn btn-danger">Anular</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pagar Planilla -->
    <div class="modal fade" id="modalPagarPlanilla" tabindex="-1" aria-labelledby="modalPagarPlanillaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPagarPlanillaLabel">Confirmar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea marcar esta planilla como pagada?</p>
                    <form id="formPagarPlanilla">
                        <div class="mb-3">
                            <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                            <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" required>
                        </div>
                        <div class="mb-3">
                            <label for="referencia_pago" class="form-label">Referencia de Pago</label>
                            <input type="text" class="form-control" id="referencia_pago" name="referencia_pago" placeholder="Ej: Transferencia #123456">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="<?php echo BASE_URL; ?>?page=planillas/pagar">
                        <input type="hidden" name="id_planilla" id="idPlanillaPagar" value="">
                        <input type="hidden" name="fecha_pago" id="hiddenFechaPago" value="">
                        <input type="hidden" name="referencia_pago" id="hiddenReferenciaPago" value="">
                        <button type="submit" class="btn btn-primary">Registrar Pago</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Estilos para tabla normal sin DataTables */
    #planillasTable {
        width: 100% !important;
        margin-bottom: 1rem;
        border-collapse: collapse;
    }

    #planillasTable th, 
    #planillasTable td {
        padding: 0.75rem;
        vertical-align: middle;
    }

    #planillasTable thead th {
        background-color: #f8f9fc;
        border-bottom: 2px solid #e3e6f0;
        font-weight: bold;
        text-align: left;
    }

    #planillasTable tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    /* Ocultar filas filtradas */
    tr.filtered-out {
        display: none !important;
    }

    /* Estilos para la paginación básica (opcional) */
    .table-pagination {
        display: flex;
        justify-content: flex-end;
        margin-top: 1rem;
    }

    /* Estilos para buscador */
    input[type="text"].form-control {
        width: 300px;
        margin-bottom: 15px;
        float: right;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Obtener la tabla
        const tabla = document.getElementById('planillasTable');
        if (tabla) {
            // Agregar contenedor de paginación
            const paginationContainer = document.createElement('div');
            paginationContainer.className = 'table-pagination';
            tabla.parentNode.appendChild(paginationContainer);

            // Variables para la paginación
            const filasPorPagina = 10;
            let paginaActual = 1;
            const filas = tabla.querySelectorAll('tbody tr');
            const totalFilas = filas.length;
            const totalPaginas = Math.ceil(totalFilas / filasPorPagina);

            // Función para actualizar la tabla según la página actual
            function mostrarPagina(pagina) {
                // Validar página
                if (pagina < 1) pagina = 1;
                if (pagina > totalPaginas) pagina = totalPaginas;
                paginaActual = pagina;
                
                // Calcular rango de filas a mostrar
                const inicio = (pagina - 1) * filasPorPagina;
                const fin = inicio + filasPorPagina;
                
                // Mostrar u ocultar filas según la página
                filas.forEach((fila, index) => {
                    if (index >= inicio && index < fin) {
                        fila.style.display = '';
                    } else {
                        fila.style.display = 'none';
                    }
                });
                
                // Actualizar botones de paginación
                actualizarPaginacion();
            }
            
            // Función para crear los controles de paginación
            function crearPaginacion() {
                // Limpiar contenedor
                paginationContainer.innerHTML = '';
                
                // No mostrar paginación si hay menos filas que el tamaño de página o no hay datos
                if (totalFilas <= filasPorPagina || totalFilas === 0) return;
                
                // Crear controles de paginación
                const nav = document.createElement('nav');
                nav.setAttribute('aria-label', 'Paginación de planillas');
                
                const ul = document.createElement('ul');
                ul.className = 'pagination pagination-sm';
                
                // Botón anterior
                const liPrev = document.createElement('li');
                liPrev.className = 'page-item';
                const aPrev = document.createElement('a');
                aPrev.className = 'page-link';
                aPrev.href = '#';
                aPrev.textContent = 'Anterior';
                aPrev.addEventListener('click', function(e) {
                    e.preventDefault();
                    mostrarPagina(paginaActual - 1);
                });
                liPrev.appendChild(aPrev);
                ul.appendChild(liPrev);
                
                // Páginas
                for (let i = 1; i <= totalPaginas; i++) {
                    const li = document.createElement('li');
                    li.className = 'page-item';
                    const a = document.createElement('a');
                    a.className = 'page-link';
                    a.href = '#';
                    a.textContent = i;
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        mostrarPagina(i);
                    });
                    li.appendChild(a);
                    ul.appendChild(li);
                }
                
                // Botón siguiente
                const liNext = document.createElement('li');
                liNext.className = 'page-item';
                const aNext = document.createElement('a');
                aNext.className = 'page-link';
                aNext.href = '#';
                aNext.textContent = 'Siguiente';
                aNext.addEventListener('click', function(e) {
                    e.preventDefault();
                    mostrarPagina(paginaActual + 1);
                });
                liNext.appendChild(aNext);
                ul.appendChild(liNext);
                
                nav.appendChild(ul);
                paginationContainer.appendChild(nav);
            }
            
            // Función para actualizar el estado de los botones de paginación
            function actualizarPaginacion() {
                // Obtener elementos de paginación
                const botones = paginationContainer.querySelectorAll('.page-item');
                if (botones.length === 0) return;
                
                // Actualizar estados
                botones.forEach((li, index) => {
                    if (index === 0) {
                        // Botón anterior
                        li.classList.toggle('disabled', paginaActual === 1);
                    } else if (index === botones.length - 1) {
                        // Botón siguiente
                        li.classList.toggle('disabled', paginaActual === totalPaginas);
                    } else {
                        // Botones de página
                        const pagina = index;
                        li.classList.toggle('active', pagina === paginaActual);
                    }
                });
            }
            
            // Implementar filtrado básico
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Buscar planillas...';
            searchInput.className = 'form-control form-control-sm mb-3';
            
            // Insertar el campo de búsqueda antes de la tabla
            tabla.parentNode.insertBefore(searchInput, tabla);
            
            // Añadir evento de búsqueda
            searchInput.addEventListener('keyup', function() {
                const texto = this.value.toLowerCase();
                let filasVisibles = 0;
                
                filas.forEach(function(fila) {
                    const contenido = fila.textContent.toLowerCase();
                    // Mostrar u ocultar según el contenido
                    if (contenido.indexOf(texto) > -1) {
                        fila.classList.remove('filtered-out');
                        filasVisibles++;
                    } else {
                        fila.classList.add('filtered-out');
                        fila.style.display = 'none'; // Ocultar inmediatamente las filas filtradas
                    }
                });
                
                // Actualizar paginación después de filtrar
                paginaActual = 1; // Volver a primera página después de filtrar
                crearPaginacion();
                mostrarPagina(1);
            });
            
            // Inicializar paginación
            crearPaginacion();
            mostrarPagina(1);
        }

        // Manejar modal de aprobar planilla
        const botonesAprobarPlanilla = document.querySelectorAll('.btn-aprobar-planilla');
        botonesAprobarPlanilla.forEach(function(boton) {
            boton.addEventListener('click', function() {
                const idPlanilla = this.getAttribute('data-id');
                document.getElementById('idPlanillaAprobar').value = idPlanilla;
            });
        });
        
        // Manejar modal de anular planilla
        const botonesAnularPlanilla = document.querySelectorAll('.btn-anular-planilla');
        botonesAnularPlanilla.forEach(function(boton) {
            boton.addEventListener('click', function() {
                const idPlanilla = this.getAttribute('data-id');
                document.getElementById('idPlanillaAnular').value = idPlanilla;
            });
        });
        
        // Manejar modal de pagar planilla
        const botonesPagarPlanilla = document.querySelectorAll('.btn-pagar-planilla');
        botonesPagarPlanilla.forEach(function(boton) {
            boton.addEventListener('click', function() {
                const idPlanilla = this.getAttribute('data-id');
                document.getElementById('idPlanillaPagar').value = idPlanilla;
                
                // Establecer fecha actual por defecto
                const hoy = new Date();
                const fechaHoy = hoy.getFullYear() + '-' + 
                               String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                               String(hoy.getDate()).padStart(2, '0');
                document.getElementById('fecha_pago').value = fechaHoy;
            });
        });
        
        // Manejar envío del formulario de pago
        const formPagarPlanilla = document.getElementById('formPagarPlanilla');
        const formSubmitPagar = document.querySelector('#modalPagarPlanilla form');
        
        formSubmitPagar.addEventListener('submit', function(event) {
            // Obtener y asignar valores del formulario de pago
            const fechaPago = document.getElementById('fecha_pago').value;
            const referenciaPago = document.getElementById('referencia_pago').value;
            
            if (!fechaPago) {
                event.preventDefault();
                alert('Por favor, ingrese la fecha de pago');
                return;
            }
            
            document.getElementById('hiddenFechaPago').value = fechaPago;
            document.getElementById('hiddenReferenciaPago').value = referenciaPago;
        });
    });
    </script>
    <?php endif; ?> <!-- Fin del if tablaExiste -->
</div> 