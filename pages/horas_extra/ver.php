<?php

// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_CONTABILIDAD, ROL_RRHH])) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Detalle de Horas Extra';
$activeMenu = 'horas_extra';

// Obtener el ID de la hora extra
$id_hora_extra = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_hora_extra <= 0) {
    setFlashMessage('Registro no especificado', 'danger');
    header('Location: ' . BASE_URL . '?page=horas_extra/lista');
    exit;
}

// Obtener datos de la hora extra
$db = getDB();
$hora_extra = null;

try {
    // Verificar si existe el registro
    $query = "SELECT he.*, 
             e.nombres, e.apellidos, e.codigo_empleado, e.dpi,
             d.nombre as departamento, p.nombre as puesto,
             u.nombres as nombre_registrador, u.apellidos as apellido_registrador,
             ua.nombres as nombre_aprobador, ua.apellidos as apellido_aprobador
             FROM horas_extra he
             JOIN empleados e ON he.id_empleado = e.id_empleado
             LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
             LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
             LEFT JOIN usuarios u ON he.registrado_por = u.id_usuario
             LEFT JOIN usuarios ua ON he.aprobado_por = ua.id_usuario
             WHERE he.id_hora_extra = :id_hora_extra";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_hora_extra', $id_hora_extra, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('El registro especificado no existe', 'danger');
        header('Location: ' . BASE_URL . '?page=horas_extra/lista');
        exit;
    }
    
    $hora_extra = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    setFlashMessage('Error al cargar los datos: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '?page=horas_extra/lista');
    exit;
}

// Obtener color del badge según el estado
$estadoBadge = 'secondary';
switch ($hora_extra['estado']) {
    case 'Pendiente':
        $estadoBadge = 'warning';
        break;
    case 'Aprobado':
        $estadoBadge = 'success';
        break;
    case 'Rechazado':
        $estadoBadge = 'danger';
        break;
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-clock fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Información detallada del registro de horas extra</p>
    
    <!-- Resumen de Hora Extra -->
    <div class="row">
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Registro #<?php echo $hora_extra['id_hora_extra']; ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $hora_extra['apellidos'] . ', ' . $hora_extra['nombres']; ?> - 
                                <?php echo $hora_extra['codigo_empleado']; ?>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-<?php echo $estadoBadge; ?>"><?php echo $hora_extra['estado']; ?></span>
                                <span class="ms-2">
                                    <i class="fas fa-calendar-alt fa-fw"></i> <?php echo date('d/m/Y', strtotime($hora_extra['fecha'])); ?>
                                </span>
                                <span class="ms-2">
                                    <i class="fas fa-clock fa-fw"></i> <?php echo $hora_extra['horas']; ?> horas
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>?page=horas_extra/lista" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-fw"></i> Volver a la Lista
        </a>
        <?php if ($hora_extra['estado'] == 'Pendiente'): ?>
            <a href="<?php echo BASE_URL; ?>?page=horas_extra/editar&id=<?php echo $id_hora_extra; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-edit fa-fw"></i> Editar
            </a>
            <a href="<?php echo BASE_URL; ?>?page=horas_extra/aprobar&id=<?php echo $id_hora_extra; ?>" class="btn btn-success btn-sm">
                <i class="fas fa-check-circle fa-fw"></i> Aprobar/Rechazar
            </a>
        <?php endif; ?>
        <a href="javascript:void(0);" onclick="window.print();" class="btn btn-info btn-sm">
            <i class="fas fa-print fa-fw"></i> Imprimir
        </a>
    </div>
    
    <!-- Detalles de la Hora Extra -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Datos del Empleado</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Nombre:</th>
                                <td><?php echo $hora_extra['apellidos'] . ', ' . $hora_extra['nombres']; ?></td>
                            </tr>
                            <tr>
                                <th>Código:</th>
                                <td><?php echo $hora_extra['codigo_empleado']; ?></td>
                            </tr>
                            <tr>
                                <th>DPI:</th>
                                <td><?php echo $hora_extra['dpi']; ?></td>
                            </tr>
                            <tr>
                                <th>Departamento:</th>
                                <td><?php echo $hora_extra['departamento']; ?></td>
                            </tr>
                            <tr>
                                <th>Puesto:</th>
                                <td><?php echo $hora_extra['puesto']; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Datos de Horas Extra</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Fecha:</th>
                                <td><?php echo date('d/m/Y', strtotime($hora_extra['fecha'])); ?></td>
                            </tr>
                            <tr>
                                <th>Horas Trabajadas:</th>
                                <td><?php echo $hora_extra['horas']; ?> horas</td>
                            </tr>
                            <tr>
                                <th>Valor por Hora:</th>
                                <td><?php echo formatMoney($hora_extra['valor_hora']); ?></td>
                            </tr>
                            <tr>
                                <th>Total a Pagar:</th>
                                <td class="fw-bold"><?php echo formatMoney($hora_extra['horas'] * $hora_extra['valor_hora']); ?></td>
                            </tr>
                            <tr>
                                <th>Estado:</th>
                                <td><span class="badge bg-<?php echo $estadoBadge; ?>"><?php echo $hora_extra['estado']; ?></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Descripción del trabajo -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Descripción del Trabajo Realizado</h6>
        </div>
        <div class="card-body">
            <?php echo nl2br(htmlspecialchars($hora_extra['descripcion'])); ?>
        </div>
    </div>
    
    <!-- Información de Registro y Aprobación -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Información Adicional</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Registrado por:</strong> 
                        <?php echo (!empty($hora_extra['nombre_registrador'])) ? 
                            $hora_extra['nombre_registrador'] . ' ' . $hora_extra['apellido_registrador'] : 'N/A'; ?>
                    </p>
                    <p><strong>Fecha de Registro:</strong> 
                        <?php echo (!empty($hora_extra['fecha_registro'])) ? 
                            date('d/m/Y H:i', strtotime($hora_extra['fecha_registro'])) : 'N/A'; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Aprobado/Rechazado por:</strong> 
                        <?php echo (!empty($hora_extra['nombre_aprobador'])) ? 
                            $hora_extra['nombre_aprobador'] . ' ' . $hora_extra['apellido_aprobador'] : 'Pendiente'; ?>
                    </p>
                    <p><strong>Fecha de Aprobación/Rechazo:</strong> 
                        <?php echo (!empty($hora_extra['fecha_aprobacion'])) ? 
                            date('d/m/Y H:i', strtotime($hora_extra['fecha_aprobacion'])) : 'Pendiente'; ?>
                    </p>
                </div>
            </div>
            
            <?php if (!empty($hora_extra['observaciones'])): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <p><strong>Observaciones:</strong></p>
                    <p><?php echo nl2br(htmlspecialchars($hora_extra['observaciones'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .navbar, .sidebar, footer {
        display: none !important;
    }
    
    .container-fluid {
        margin: 0;
        padding: 0;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
    
    body {
        font-size: 12pt;
    }
}
</style> 