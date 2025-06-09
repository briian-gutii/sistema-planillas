<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Lista de Préstamos';
$activeMenu = 'finanzas';

// Obtener la lista de préstamos de la base de datos
$db = getDB();
$query = "SELECT p.*, e.codigo, e.nombres, e.apellidos 
          FROM prestamos p
          JOIN empleados e ON p.id_empleado = e.id
          ORDER BY p.fecha_solicitud DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-hand-holding-usd fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Gestión de préstamos de los empleados</p>

    <!-- Botones de Acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=prestamos/nuevo" class="btn btn-success btn-sm">
            <i class="fas fa-plus fa-fw"></i> Nuevo Préstamo
        </a>
        <a href="<?php echo BASE_URL; ?>?page=prestamos/reporte" class="btn btn-info btn-sm">
            <i class="fas fa-file-pdf fa-fw"></i> Generar Reporte
        </a>
    </div>

    <!-- Tabla de Préstamos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Préstamos Registrados</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>ID</th>
                            <th>Empleado</th>
                            <th>Monto</th>
                            <th>Cuotas</th>
                            <th>Cuota Mensual</th>
                            <th>Fecha Solicitud</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($prestamos) > 0): ?>
                            <?php foreach ($prestamos as $prestamo): ?>
                                <?php 
                                // Calcular la cuota mensual
                                $cuotaMensual = $prestamo['monto'] / $prestamo['plazo_meses'];
                                
                                // Determinar el estado del préstamo
                                switch ($prestamo['estado']) {
                                    case 'pendiente':
                                        $estadoLabel = 'Pendiente';
                                        $estadoBadge = 'warning';
                                        break;
                                    case 'aprobado':
                                        $estadoLabel = 'Aprobado';
                                        $estadoBadge = 'success';
                                        break;
                                    case 'rechazado':
                                        $estadoLabel = 'Rechazado';
                                        $estadoBadge = 'danger';
                                        break;
                                    case 'pagando':
                                        $estadoLabel = 'Pagando';
                                        $estadoBadge = 'info';
                                        break;
                                    case 'pagado':
                                        $estadoLabel = 'Pagado';
                                        $estadoBadge = 'secondary';
                                        break;
                                    default:
                                        $estadoLabel = 'Desconocido';
                                        $estadoBadge = 'dark';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $prestamo['id']; ?></td>
                                    <td>
                                        <?php echo $prestamo['codigo'] . ' - ' . $prestamo['apellidos'] . ', ' . $prestamo['nombres']; ?>
                                    </td>
                                    <td>Q <?php echo number_format($prestamo['monto'], 2); ?></td>
                                    <td><?php echo $prestamo['plazo_meses']; ?> meses</td>
                                    <td>Q <?php echo number_format($cuotaMensual, 2); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_solicitud'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $estadoBadge; ?>"><?php echo $estadoLabel; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>?page=prestamos/ver&id=<?php echo $prestamo['id']; ?>" 
                                           class="btn btn-info btn-sm" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($prestamo['estado'] == 'pendiente'): ?>
                                            <a href="<?php echo BASE_URL; ?>?page=prestamos/editar&id=<?php echo $prestamo['id']; ?>" 
                                               class="btn btn-primary btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-success btn-sm btn-aprobar-prestamo" 
                                                    data-id="<?php echo $prestamo['id']; ?>" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalAprobarPrestamo" 
                                                    title="Aprobar">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm btn-rechazar-prestamo" 
                                                    data-id="<?php echo $prestamo['id']; ?>" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalRechazarPrestamo" 
                                                    title="Rechazar">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php elseif ($prestamo['estado'] == 'aprobado' || $prestamo['estado'] == 'pagando'): ?>
                                            <a href="<?php echo BASE_URL; ?>?page=prestamos/pagos&id=<?php echo $prestamo['id']; ?>" 
                                               class="btn btn-success btn-sm" title="Ver Pagos">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No hay préstamos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aprobar Préstamo -->
<div class="modal fade" id="modalAprobarPrestamo" tabindex="-1" aria-labelledby="modalAprobarPrestamoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAprobarPrestamoLabel">Aprobar Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAprobarPrestamo" method="post" action="<?php echo BASE_URL; ?>?page=prestamos/aprobar">
                <div class="modal-body">
                    <p>¿Está seguro que desea aprobar este préstamo?</p>
                    <div class="mb-3">
                        <label for="fecha_aprobacion" class="form-label">Fecha de Aprobación *</label>
                        <input type="date" class="form-control" id="fecha_aprobacion" name="fecha_aprobacion" required>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_primer_pago" class="form-label">Fecha Primer Pago *</label>
                        <input type="date" class="form-control" id="fecha_primer_pago" name="fecha_primer_pago" required>
                    </div>
                    <div class="mb-3">
                        <label for="observaciones_aprobacion" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_aprobacion" name="observaciones" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id_prestamo" id="idPrestamoAprobar" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Aprobar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Rechazar Préstamo -->
<div class="modal fade" id="modalRechazarPrestamo" tabindex="-1" aria-labelledby="modalRechazarPrestamoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRechazarPrestamoLabel">Rechazar Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRechazarPrestamo" method="post" action="<?php echo BASE_URL; ?>?page=prestamos/rechazar">
                <div class="modal-body">
                    <p>¿Está seguro que desea rechazar este préstamo?</p>
                    <div class="mb-3">
                        <label for="motivo_rechazo" class="form-label">Motivo de Rechazo *</label>
                        <textarea class="form-control" id="motivo_rechazo" name="motivo_rechazo" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id_prestamo" id="idPrestamoRechazar" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rechazar</button>
                </div>
            </form>
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
    
    // Manejar el modal de aprobar préstamo
    const botonesAprobarPrestamo = document.querySelectorAll('.btn-aprobar-prestamo');
    botonesAprobarPrestamo.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idPrestamo = this.getAttribute('data-id');
            document.getElementById('idPrestamoAprobar').value = idPrestamo;
            
            // Establecer fecha actual como fecha de aprobación
            const hoy = new Date();
            const fechaHoy = hoy.getFullYear() + '-' + 
                            String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(hoy.getDate()).padStart(2, '0');
            document.getElementById('fecha_aprobacion').value = fechaHoy;
            
            // Establecer fecha de primer pago (mes siguiente)
            const fechaPrimerPago = new Date(hoy);
            fechaPrimerPago.setMonth(fechaPrimerPago.getMonth() + 1);
            const fechaPrimerPagoStr = fechaPrimerPago.getFullYear() + '-' + 
                                      String(fechaPrimerPago.getMonth() + 1).padStart(2, '0') + '-' + 
                                      String(fechaPrimerPago.getDate()).padStart(2, '0');
            document.getElementById('fecha_primer_pago').value = fechaPrimerPagoStr;
        });
    });
    
    // Manejar el modal de rechazar préstamo
    const botonesRechazarPrestamo = document.querySelectorAll('.btn-rechazar-prestamo');
    botonesRechazarPrestamo.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idPrestamo = this.getAttribute('data-id');
            document.getElementById('idPrestamoRechazar').value = idPrestamo;
        });
    });
});
</script> 