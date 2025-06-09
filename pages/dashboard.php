<?php
// Página de dashboard (panel de control)
$titulo = "Dashboard";

// Consultar número de empleados activos
$empleados_activos = 0;
if (tableExists('empleados')) {
    $sql_empleados = "SELECT COUNT(*) as total FROM empleados WHERE estado = 'Activo'";
    $empleados_activos = fetchRow($sql_empleados)['total'] ?? 0;
}

// Consultar planillas pendientes
$planillas_pendientes = 0;
if (tableExists('planillas')) {
    $sql_planillas = "SELECT COUNT(*) as total FROM planillas WHERE estado = 'Pendiente'";
    $planillas_pendientes = fetchRow($sql_planillas)['total'] ?? 0;
}

// Consultar solicitudes pendientes
$solicitudes_pendientes = 0;
if (tableExists('solicitudes')) {
    $sql_solicitudes = "SELECT COUNT(*) as total FROM solicitudes WHERE estado = 'Pendiente'";
    $solicitudes_pendientes = fetchRow($sql_solicitudes)['total'] ?? 0;
}

// Consultar períodos activos
$periodos_activos = 0;
if (tableExists('periodos')) {
    $sql_periodos = "SELECT COUNT(*) as total FROM periodos WHERE estado = 'Activo'";
    $periodos_activos = fetchRow($sql_periodos)['total'] ?? 0;
}

// Consultar última planilla generada
$ultima_planilla = null;
if (tableExists('planillas')) {
    $sql_ultima_planilla = "SELECT * FROM planillas ORDER BY fecha_creacion DESC LIMIT 1";
    $ultima_planilla = fetchRow($sql_ultima_planilla);
}

// Consultar solicitudes recientes
$solicitudes_recientes = [];
if (tableExists('solicitudes') && tableExists('empleados')) {
    try {
        $sql_solicitudes_recientes = "SELECT s.*, e.primer_nombre as nombre, e.primer_apellido as apellido 
                                     FROM solicitudes s 
                                     JOIN empleados e ON s.id_empleado = e.id_empleado 
                                     ORDER BY s.fecha_solicitud DESC LIMIT 5";
        $solicitudes_recientes = fetchAll($sql_solicitudes_recientes);
    } catch (Exception $e) {
        // Silenciar errores, ya que podría haber problemas con la estructura de columnas
    }
}
?>

    <!-- Main Content -->
    <main class="container-fluid px-4 py-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="mb-1 fw-bold">¡Bienvenido, <?php echo $_SESSION['username'] ?? 'Usuario'; ?>!</h2>
                <p class="text-slate-500 mb-0">Panel de control del Sistema de Planillas Guatemala</p>
            </div>
            
            <div class="col-md-4 d-flex justify-content-md-end align-items-center mt-3 mt-md-0">
                <div class="text-end me-3">
                    <div class="text-slate-500 small"><?php echo date('d/m/Y'); ?></div>
                    <div class="fw-medium" id="reloj-tiempo-real"><?php echo date('H:i:s'); ?></div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-primary d-flex align-items-center dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-plus-lg me-2"></i>
                        Nueva acción
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item" href="insertar_periodos_pago.php" target="_blank">Insertar Periodos de Pago</a></li>
                        <li><a class="dropdown-item" href="periodos_pago_diagnostico.php" target="_blank">Diagnosticar Periodos de Pago</a></li>
                        <li><a class="dropdown-item" href="?page=planillas/generar">Generar Planilla</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
    <div class="row mb-4">
        <!-- Empleados activos -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card h-100">
                <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="icon-wrapper bg-primary-light">
                                <i class="bi bi-people fs-4 text-primary"></i>
                            </div>
                            <span class="stat-badge bg-primary-badge">Activos</span>
                        </div>
                        <div class="stat-value"><?php echo $empleados_activos; ?></div>
                        <div class="stat-label">Empleados registrados</div>
                </div>
            </div>
        </div>
        
        <!-- Planillas pendientes -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card h-100">
                <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="icon-wrapper bg-blue-light">
                                <i class="bi bi-file-earmark-text fs-4 text-blue"></i>
                            </div>
                            <span class="stat-badge bg-blue-badge">Pendientes</span>
                        </div>
                        <div class="stat-value"><?php echo $planillas_pendientes; ?></div>
                        <div class="stat-label">Planillas por procesar</div>
                </div>
            </div>
        </div>
        
        <!-- Solicitudes pendientes -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card h-100">
                <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="icon-wrapper bg-pink-light">
                                <i class="bi bi-bell fs-4 text-pink"></i>
                            </div>
                            <span class="stat-badge bg-pink-badge">Pendientes</span>
                        </div>
                        <div class="stat-value"><?php echo $solicitudes_pendientes; ?></div>
                        <div class="stat-label">Solicitudes sin revisar</div>
                </div>
            </div>
        </div>
        
        <!-- Períodos activos -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card h-100">
                <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="icon-wrapper bg-teal-light">
                                <i class="bi bi-calendar-check fs-4 text-teal"></i>
                            </div>
                            <span class="stat-badge bg-teal-badge">Activos</span>
                        </div>
                        <div class="stat-value"><?php echo $periodos_activos; ?></div>
                        <div class="stat-label">Periodos en curso</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <!-- Última planilla generada -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                            <i class="bi bi-file-earmark-text text-blue me-2"></i>
                            <span>Última Planilla Generada</span>
                    </div>
                        <a href="?page=planillas/lista" class="text-decoration-none text-primary small">Ver todas</a>
                </div>
                <div class="card-body">
                    <?php if(isset($ultima_planilla) && $ultima_planilla): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Descripción</th>
                                    <th>Período</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo $ultima_planilla['descripcion'] ?? 'N/A'; ?></td>
                                    <td><?php echo isset($ultima_planilla['fecha_inicio']) ? date('d/m/Y', strtotime($ultima_planilla['fecha_inicio'])) . ' - ' . date('d/m/Y', strtotime($ultima_planilla['fecha_fin'])) : 'N/A'; ?></td>
                                    <td>Q<?php echo isset($ultima_planilla['monto_total']) ? number_format($ultima_planilla['monto_total'], 2) : '0.00'; ?></td>
                                        <td>
                                            <?php 
                                                $estado = $ultima_planilla['estado'] ?? 'Pendiente';
                                                $badgeClass = 'bg-warning';
                                                
                                                if ($estado == 'Aprobado') {
                                                    $badgeClass = 'bg-success';
                                                } elseif ($estado != 'Pendiente') {
                                                    $badgeClass = 'bg-secondary';
                                                }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $estado; ?></span>
                                        </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="empty-state">
                        <i class="bi bi-info-circle me-2"></i>
                            <span>No hay planillas generadas aún.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Solicitudes recientes -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                            <i class="bi bi-bell text-pink me-2"></i>
                            <span>Solicitudes Recientes</span>
                    </div>
                        <a href="?page=solicitudes/lista" class="text-decoration-none text-primary small">Ver todas</a>
                </div>
                <div class="card-body">
                    <?php if(isset($solicitudes_recientes) && count($solicitudes_recientes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($solicitudes_recientes as $solicitud): ?>
                                <tr>
                                    <td><?php echo $solicitud['nombre'] . ' ' . $solicitud['apellido']; ?></td>
                                    <td><?php echo $solicitud['tipo'] ?? 'N/A'; ?></td>
                                    <td><?php echo isset($solicitud['fecha_solicitud']) ? date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) : 'N/A'; ?></td>
                                        <td>
                                            <?php 
                                                $estado = $solicitud['estado'] ?? 'Pendiente';
                                                $badgeClass = 'bg-warning';
                                                
                                                if ($estado == 'Aprobado') {
                                                    $badgeClass = 'bg-success';
                                                } elseif ($estado != 'Pendiente') {
                                                    $badgeClass = 'bg-secondary';
                                                }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $estado; ?></span>
                                        </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="empty-state">
                        <i class="bi bi-info-circle me-2"></i>
                            <span>No hay solicitudes recientes.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Accesos rápidos -->
        <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <span>Accesos Rápidos</span>
                </div>
                <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                        <!-- Nuevo Empleado -->
                            <a href="?page=empleados/nuevo" class="quick-access-link">
                                <div class="d-flex align-items-center">
                                    <div class="quick-access-icon bg-primary-light me-3">
                                        <i class="bi bi-person-plus text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Nuevo Empleado</h6>
                                        <small class="text-slate-500">Registrar empleado en el sistema</small>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Generar Planilla -->
                            <a href="?page=planillas/generar" class="quick-access-link">
                                <div class="d-flex align-items-center">
                                    <div class="quick-access-icon bg-blue-light me-3">
                                        <i class="bi bi-file-earmark-plus text-blue"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Generar Planilla</h6>
                                        <small class="text-slate-500">Crear nueva planilla de pago</small>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Libro de Salarios -->
                            <a href="?page=reportes/libro_salarios" class="quick-access-link">
                                <div class="d-flex align-items-center">
                                    <div class="quick-access-icon bg-pink-light me-3">
                                        <i class="bi bi-journal-text text-pink"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Libro de Salarios</h6>
                                        <small class="text-slate-500">Generar libro anual de salarios</small>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Horas Extra -->
                            <a href="?page=horas_extra/lista" class="quick-access-link">
                                <div class="d-flex align-items-center">
                                    <div class="quick-access-icon bg-teal-light me-3">
                                        <i class="bi bi-clock-history text-teal"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Horas Extra</h6>
                                        <small class="text-slate-500">Gestionar horas extra de empleados</small>
                                    </div>
                                </div>
                            </a>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<script>
    // Función para actualizar el reloj en tiempo real
    function actualizarReloj() {
        var ahora = new Date();
        var horas = ahora.getHours();
        var minutos = ahora.getMinutes();
        var segundos = ahora.getSeconds();
        
        // Formatear para mostrar siempre dos dígitos
        if (horas < 10) horas = "0" + horas;
        if (minutos < 10) minutos = "0" + minutos;
        if (segundos < 10) segundos = "0" + segundos;
        
        document.getElementById("reloj-tiempo-real").textContent = horas + ":" + minutos + ":" + segundos;
        
        // Actualizar cada segundo
        setTimeout(actualizarReloj, 1000);
    }
    
    // Iniciar el reloj cuando se cargue la página
    document.addEventListener("DOMContentLoaded", actualizarReloj);
</script>