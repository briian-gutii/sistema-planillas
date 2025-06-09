<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Ver Contrato';
$activeMenu = 'empleados';
$contractId = $_GET['id'] ?? null;

if (!$contractId) {
    setFlashMessage('ID de contrato no proporcionado.', 'danger');
    header('Location: ' . BASE_URL . '?page=contratos/lista');
    exit;
}

$db = getDB();
$query = "SELECT 
            c.*, 
            e.DPI AS codigo_empleado, 
            e.primer_nombre, 
            e.segundo_nombre, 
            e.primer_apellido, 
            e.segundo_apellido, 
            e.apellido_casada,
            CONCAT(e.primer_nombre, COALESCE(CONCAT(' ', e.segundo_nombre), '')) AS nombres_empleado,
            CONCAT(e.primer_apellido, COALESCE(CONCAT(' ', e.segundo_apellido), ''), COALESCE(CONCAT(' de ', e.apellido_casada), '')) AS apellidos_empleado,
            p.nombre AS puesto_nombre
          FROM contratos c
          JOIN empleados e ON c.id_empleado = e.id_empleado
          LEFT JOIN puestos p ON c.id_puesto = p.id_puesto
          WHERE c.id_contrato = :id_contrato";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_contrato', $contractId, PDO::PARAM_INT);
$stmt->execute();
$contrato = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contrato) {
    setFlashMessage('Contrato no encontrado.', 'danger');
    header('Location: ' . BASE_URL . '?page=contratos/lista');
    exit;
}

// Formatear nombre completo del empleado
$nombreCompletoEmpleado = trim($contrato['nombres_empleado'] . ' ' . $contrato['apellidos_empleado']);

?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-file-contract fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Detalles del contrato registrado.</p>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Información del Contrato #<?php echo htmlspecialchars($contrato['id_contrato']); ?></h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Empleado:</strong> <?php echo htmlspecialchars($nombreCompletoEmpleado); ?></p>
                    <p><strong>DPI:</strong> <?php echo htmlspecialchars($contrato['codigo_empleado']); ?></p>
                    <p><strong>Puesto:</strong> <?php echo htmlspecialchars($contrato['puesto_nombre'] ?? 'No especificado'); ?></p>
                    <p><strong>Tipo de Contrato:</strong> <?php echo htmlspecialchars($contrato['tipo_contrato'] ?? 'No especificado'); ?></p>
                    <p><strong>Salario:</strong> Q <?php echo number_format($contrato['salario'], 2); ?></p>
                    <p><strong>Bonificación Incentivo:</strong> Q <?php echo number_format($contrato['bonificacion_incentivo'] ?? 0, 2); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Fecha de Inicio:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($contrato['fecha_inicio']))); ?></p>
                    <p><strong>Fecha de Fin:</strong> 
                        <?php 
                        if ($contrato['fecha_fin']) {
                            echo htmlspecialchars(date('d/m/Y', strtotime($contrato['fecha_fin'])));
                        } else {
                            echo '<span class="badge bg-info">Indefinido</span>';
                        }
                        ?>
                    </p>
                    <p><strong>Jornada:</strong> <?php echo htmlspecialchars($contrato['jornada'] ?? 'No especificada'); ?></p>
                    <p><strong>Horas Semanales:</strong> <?php echo htmlspecialchars($contrato['horas_semanales'] ?? 'No especificadas'); ?></p>
                    <p><strong>Estado:</strong> 
                        <?php 
                        if (($contrato['estado'] ?? 0) == 1) {
                            echo '<span class="badge bg-success">Activo</span>';
                        } else {
                            echo '<span class="badge bg-danger">Finalizado</span>';
                        }
                        ?>
                    </p>
                    <?php if (($contrato['estado'] ?? 0) == 0 && $contrato['fecha_fin']): ?>
                        <p><strong>Motivo de Finalización:</strong> <?php echo htmlspecialchars($contrato['motivo_fin'] ?? 'No especificado'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <hr>
            <p><strong>Observaciones:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($contrato['observaciones'] ?? 'Ninguna')); ?></p>

            <?php if (!empty($contrato['condiciones_adicionales'])): ?>
            <hr>
            <p><strong>Condiciones Adicionales del Contrato:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($contrato['condiciones_adicionales'])); ?></p>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="<?php echo BASE_URL; ?>?page=contratos/lista" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left fa-fw"></i> Volver a la Lista
            </a>
            <?php if (($contrato['estado'] ?? 0) == 1): ?>
                <a href="<?php echo BASE_URL; ?>?page=contratos/editar&id=<?php echo $contrato['id_contrato']; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit fa-fw"></i> Editar Contrato
                </a>
                <button type="button" class="btn btn-danger btn-sm btn-finalizar-contrato" 
                        data-id="<?php echo $contrato['id_contrato']; ?>" 
                        data-empleado="<?php echo htmlspecialchars($nombreCompletoEmpleado); ?>" 
                        data-bs-toggle="modal" data-bs-target="#modalFinalizarContrato" 
                        title="Finalizar Contrato">
                    <i class="fas fa-times-circle fa-fw"></i> Finalizar Contrato
                </button>
            <?php else: ?>
                <!-- Podrías agregar un botón para reactivar o generar adenda si fuera necesario -->
            <?php endif; ?>
             <a href="<?php echo BASE_URL; ?>?page=contratos/imprimir&id=<?php echo $contrato['id_contrato']; ?>" class="btn btn-info btn-sm" target="_blank">
                <i class="fas fa-print fa-fw"></i> Imprimir Contrato
            </a>
        </div>
    </div>
</div>

<!-- Modal Finalizar Contrato (si se permite finalizar desde esta vista) -->
<?php if (($contrato['estado'] ?? 0) == 1): ?>
<div class="modal fade" id="modalFinalizarContrato" tabindex="-1" aria-labelledby="modalFinalizarContratoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFinalizarContratoLabel">Finalizar Contrato</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formFinalizarContrato" method="post" action="<?php echo BASE_URL; ?>?page=contratos/finalizar">
                <div class="modal-body">
                    <p>¿Está seguro que desea finalizar el contrato de <strong id="empleadoNombreModal"></strong>?</p>
                    <div class="mb-3">
                        <label for="fecha_fin_modal" class="form-label">Fecha de Finalización *</label>
                        <input type="date" class="form-control" id="fecha_fin_modal" name="fecha_fin" required>
                    </div>
                    <div class="mb-3">
                        <label for="motivo_fin_modal" class="form-label">Motivo de Finalización *</label>
                        <select class="form-select" id="motivo_fin_modal" name="motivo_fin" required>
                            <option value="">Seleccione un motivo</option>
                            <option value="Renuncia">Renuncia</option>
                            <option value="Despido">Despido</option>
                            <option value="Fin de contrato">Fin de contrato</option>
                            <option value="Jubilación">Jubilación</option>
                            <option value="Fallecimiento">Fallecimiento</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="observaciones_modal" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_modal" name="observaciones" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id_contrato" id="idContratoModal" value="">
                    <input type="hidden" name="redirect_url" value="<?php echo BASE_URL; ?>?page=contratos/ver&id=<?php echo $contrato['id_contrato']; ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Finalizar Contrato</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const botonesFinalizarContrato = document.querySelectorAll('.btn-finalizar-contrato');
    botonesFinalizarContrato.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idContrato = this.getAttribute('data-id');
            const empleadoNombre = this.getAttribute('data-empleado');
            
            document.getElementById('idContratoModal').value = idContrato;
            document.getElementById('empleadoNombreModal').textContent = empleadoNombre;
            
            const hoy = new Date();
            const fechaHoy = hoy.getFullYear() + '-' + 
                            String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(hoy.getDate()).padStart(2, '0');
            document.getElementById('fecha_fin_modal').value = fechaHoy;
            document.getElementById('fecha_fin_modal').min = '<?php echo $contrato['fecha_inicio']; ?>';


            // Si hay una fecha de fin ya establecida para el contrato (aunque esté activo),
            // no permitir que la nueva fecha de finalización sea anterior a la fecha de inicio.
            // O si el contrato tiene una fecha de fin pactada, usar esa como referencia.
            const fechaInicioContrato = '<?php echo $contrato['fecha_inicio']; ?>';
            document.getElementById('fecha_fin_modal').min = fechaInicioContrato;

        });
    });
});
</script>
<?php endif; ?> 