<?php

// Verificar permisos
if (!hasPermission([ROL_ADMIN, ROL_CONTABILIDAD])) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Aprobar/Rechazar Horas Extra';
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
    // Verificar si existe el registro y está pendiente
    $query = "SELECT he.*, 
             e.nombres, e.apellidos, e.codigo_empleado, e.dpi,
             d.nombre as departamento, p.nombre as puesto
             FROM horas_extra he
             JOIN empleados e ON he.id_empleado = e.id_empleado
             LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
             LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
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
    
    // Verificar que el registro esté pendiente
    if ($hora_extra['estado'] != 'Pendiente') {
        setFlashMessage('Solo se pueden aprobar/rechazar registros en estado Pendiente', 'warning');
        header('Location: ' . BASE_URL . '?page=horas_extra/ver&id=' . $id_hora_extra);
        exit;
    }
    
} catch (Exception $e) {
    setFlashMessage('Error al cargar los datos: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '?page=horas_extra/lista');
    exit;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    
    if ($accion != 'aprobar' && $accion != 'rechazar') {
        setFlashMessage('Acción no válida', 'danger');
    } else {
        try {
            // Actualizar estado de la hora extra
            $estado = ($accion == 'aprobar') ? 'Aprobado' : 'Rechazado';
            
            $queryUpdate = "UPDATE horas_extra SET 
                          estado = :estado, 
                          observaciones = :observaciones,
                          fecha_aprobacion = NOW(),
                          aprobado_por = :aprobado_por
                          WHERE id_hora_extra = :id_hora_extra";
            
            $stmt = $db->prepare($queryUpdate);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':observaciones', $observaciones);
            $stmt->bindParam(':aprobado_por', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':id_hora_extra', $id_hora_extra, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Registrar la acción en el historial
                try {
                    $accion_historial = ($accion == 'aprobar') ? 'Aprobación' : 'Rechazo';
                    $descripcion = $accion_historial . ' de horas extra para el empleado ' . 
                                  $hora_extra['apellidos'] . ', ' . $hora_extra['nombres'] . 
                                  ' (' . $hora_extra['codigo_empleado'] . ')';
                    
                    $queryHistorial = "INSERT INTO historial (accion, descripcion, tipo_entidad, id_entidad, usuario_id, fecha)
                                     VALUES (:accion, :descripcion, 'horas_extra', :id_hora_extra, :usuario_id, NOW())";
                    
                    $stmtHistorial = $db->prepare($queryHistorial);
                    $stmtHistorial->bindParam(':accion', $accion_historial);
                    $stmtHistorial->bindParam(':descripcion', $descripcion);
                    $stmtHistorial->bindParam(':id_hora_extra', $id_hora_extra, PDO::PARAM_INT);
                    $stmtHistorial->bindParam(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
                    $stmtHistorial->execute();
                } catch (Exception $e) {
                    // Si hay error en el registro del historial, ignoramos para no interrumpir flujo
                }
                
                $mensaje = ($accion == 'aprobar') ? 
                          'Horas extra aprobadas correctamente' : 
                          'Horas extra rechazadas correctamente';
                
                setFlashMessage($mensaje, 'success');
                header('Location: ' . BASE_URL . '?page=horas_extra/ver&id=' . $id_hora_extra);
                exit;
            } else {
                setFlashMessage('Error al procesar la solicitud', 'danger');
            }
        } catch (Exception $e) {
            setFlashMessage('Error: ' . $e->getMessage(), 'danger');
        }
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-check-circle fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Apruebe o rechace la solicitud de horas extra</p>
    
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
                                <span class="badge bg-warning">Pendiente</span>
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
    
    <!-- Formulario de Aprobación/Rechazo -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Decisión</h6>
        </div>
        <div class="card-body">
            <form method="post" id="formAprobacion">
                <div class="mb-3">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Ingrese observaciones sobre esta solicitud"></textarea>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo BASE_URL; ?>?page=horas_extra/ver&id=<?php echo $id_hora_extra; ?>" class="btn btn-secondary me-md-2">
                        <i class="fas fa-arrow-left fa-fw"></i> Cancelar
                    </a>
                    <button type="submit" name="accion" value="rechazar" class="btn btn-danger me-md-2" onclick="return confirm('¿Está seguro que desea rechazar estas horas extra?')">
                        <i class="fas fa-times-circle fa-fw"></i> Rechazar
                    </button>
                    <button type="submit" name="accion" value="aprobar" class="btn btn-success" onclick="return confirm('¿Está seguro que desea aprobar estas horas extra?')">
                        <i class="fas fa-check-circle fa-fw"></i> Aprobar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> 