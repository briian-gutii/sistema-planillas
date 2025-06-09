<?php

// Habilitar visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Crear archivo de log para depuración
file_put_contents('debug_vacaciones.log', "Iniciando script\n", FILE_APPEND);

// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Registrar Vacaciones';
$activeMenu = 'empleados';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents('debug_vacaciones.log', "Formulario enviado (POST)\n", FILE_APPEND);
    
    $id_empleado = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : 0;
    $periodo_inicio = isset($_POST['periodo_inicio']) ? $_POST['periodo_inicio'] : '';
    $periodo_fin = isset($_POST['periodo_fin']) ? $_POST['periodo_fin'] : '';
    $dias_correspondientes = isset($_POST['dias_correspondientes']) ? intval($_POST['dias_correspondientes']) : 0;
    $dias_gozados = isset($_POST['dias_gozados']) ? intval($_POST['dias_gozados']) : 0;
    $dias_pendientes = isset($_POST['dias_pendientes']) ? intval($_POST['dias_pendientes']) : 0;
    
    // Log de datos recibidos
    $log_data = "Datos recibidos: id_empleado=$id_empleado, periodo_inicio=$periodo_inicio, periodo_fin=$periodo_fin, ";
    $log_data .= "dias_correspondientes=$dias_correspondientes, dias_gozados=$dias_gozados, dias_pendientes=$dias_pendientes\n";
    file_put_contents('debug_vacaciones.log', $log_data, FILE_APPEND);
    
    // Validación básica
    $errores = [];
    
    if ($id_empleado <= 0) {
        $errores[] = 'Debe seleccionar un empleado válido.';
    }
    
    if (empty($periodo_inicio)) {
        $errores[] = 'Debe ingresar una fecha de inicio válida.';
    }
    
    if (empty($periodo_fin)) {
        $errores[] = 'Debe ingresar una fecha de fin válida.';
    }
    
    if ($dias_correspondientes <= 0) {
        $errores[] = 'Los días correspondientes deben ser mayores a cero.';
    }
    
    // Verificar que la fecha de fin no sea anterior a la fecha de inicio
    if (!empty($periodo_inicio) && !empty($periodo_fin)) {
        $inicio = new DateTime($periodo_inicio);
        $fin = new DateTime($periodo_fin);
        
        if ($fin < $inicio) {
            $errores[] = 'La fecha de finalización no puede ser anterior a la fecha de inicio.';
        }
    }
    
    // Log de validación
    if (count($errores) > 0) {
        file_put_contents('debug_vacaciones.log', "Errores de validación: " . implode(", ", $errores) . "\n", FILE_APPEND);
    } else {
        file_put_contents('debug_vacaciones.log', "Validación exitosa, procediendo a guardar\n", FILE_APPEND);
    }
    
    // Si no hay errores, guardar las vacaciones
    if (empty($errores)) {
        try {
            $db = getDB();
            file_put_contents('debug_vacaciones.log', "Conexión a BD exitosa\n", FILE_APPEND);
            
            $db->beginTransaction();
            file_put_contents('debug_vacaciones.log', "Transacción iniciada\n", FILE_APPEND);
            
            // Insertar las vacaciones
            $query = "INSERT INTO vacaciones (id_empleado, periodo_inicio, periodo_fin, 
                      dias_correspondientes, dias_gozados, dias_pendientes) 
                      VALUES (:id_empleado, :periodo_inicio, :periodo_fin, 
                      :dias_correspondientes, :dias_gozados, :dias_pendientes)";
            
            $stmt = $db->prepare($query);
            file_put_contents('debug_vacaciones.log', "Query preparada: $query\n", FILE_APPEND);
            
            $stmt->bindParam(':id_empleado', $id_empleado, PDO::PARAM_INT);
            $stmt->bindParam(':periodo_inicio', $periodo_inicio, PDO::PARAM_STR);
            $stmt->bindParam(':periodo_fin', $periodo_fin, PDO::PARAM_STR);
            $stmt->bindParam(':dias_correspondientes', $dias_correspondientes, PDO::PARAM_INT);
            $stmt->bindParam(':dias_gozados', $dias_gozados, PDO::PARAM_INT);
            $stmt->bindParam(':dias_pendientes', $dias_pendientes, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            file_put_contents('debug_vacaciones.log', "Ejecución de query: " . ($result ? "exitosa" : "fallida") . "\n", FILE_APPEND);
            
            if (!$result) {
                $error_info = print_r($stmt->errorInfo(), true);
                file_put_contents('debug_vacaciones.log', "Error en query: $error_info\n", FILE_APPEND);
                throw new Exception("Error al ejecutar la consulta: " . $stmt->errorInfo()[2]);
            }
            
            $id_vacaciones = $db->lastInsertId();
            file_put_contents('debug_vacaciones.log', "ID insertado: $id_vacaciones\n", FILE_APPEND);
            
            $db->commit();
            file_put_contents('debug_vacaciones.log', "Transacción confirmada (commit)\n", FILE_APPEND);
            
            setFlashMessage('Vacaciones registradas exitosamente', 'success');
            file_put_contents('debug_vacaciones.log', "Redirigiendo a lista\n", FILE_APPEND);
            
            // Redireccionar a la lista
            header('Location: ' . BASE_URL . '?page=vacaciones/lista');
            exit;
            
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
                file_put_contents('debug_vacaciones.log', "Transacción revertida (rollback)\n", FILE_APPEND);
            }
            
            $error_msg = 'Error al registrar las vacaciones: ' . $e->getMessage();
            file_put_contents('debug_vacaciones.log', "$error_msg\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            
            setFlashMessage($error_msg, 'danger');
        }
    } else {
        // Mostrar errores
        foreach ($errores as $error) {
            setFlashMessage($error, 'danger');
        }
    }
}

// Obtener la lista de empleados
$db = getDB();
$query = "SELECT id_empleado, DPI, CONCAT(primer_apellido, ' ', segundo_apellido, ', ', primer_nombre, ' ', segundo_nombre) AS nombre_completo 
          FROM empleados 
          ORDER BY primer_apellido, primer_nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-umbrella-beach fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Complete el formulario para registrar un nuevo período de vacaciones</p>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Datos de Vacaciones</h6>
        </div>
        <div class="card-body">
            <form method="post" id="formNuevasVacaciones">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="id_empleado" class="form-label">Empleado *</label>
                        <select class="form-select" id="id_empleado" name="id_empleado" required>
                            <option value="">Seleccione un empleado</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?php echo $empleado['id_empleado']; ?>">
                                    <?php echo $empleado['DPI'] . ' - ' . $empleado['nombre_completo']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="periodo_inicio" class="form-label">Fecha de Inicio *</label>
                        <input type="date" class="form-control" id="periodo_inicio" name="periodo_inicio" required>
                    </div>
                    <div class="col-md-4">
                        <label for="periodo_fin" class="form-label">Fecha de Finalización *</label>
                        <input type="date" class="form-control" id="periodo_fin" name="periodo_fin" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="dias_correspondientes" class="form-label">Días Correspondientes *</label>
                        <input type="number" class="form-control" id="dias_correspondientes" name="dias_correspondientes" min="1" value="15" required>
                    </div>
                    <div class="col-md-4">
                        <label for="dias_gozados" class="form-label">Días Gozados *</label>
                        <input type="number" class="form-control" id="dias_gozados" name="dias_gozados" min="0" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label for="dias_pendientes" class="form-label">Días Pendientes *</label>
                        <input type="number" class="form-control" id="dias_pendientes" name="dias_pendientes" min="0" value="0" required>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <a href="<?php echo BASE_URL; ?>?page=vacaciones/lista" class="btn btn-secondary">
                            <i class="fas fa-arrow-left fa-fw"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save fa-fw"></i> Guardar Vacaciones
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Establecer fecha actual como fecha por defecto para inicio
    const hoy = new Date();
    const fechaHoy = hoy.getFullYear() + '-' + 
                    String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(hoy.getDate()).padStart(2, '0');
    document.getElementById('periodo_inicio').value = fechaHoy;
    
    // Calcular fecha fin por defecto (15 días después)
    const fechaFin = new Date(hoy);
    fechaFin.setDate(fechaFin.getDate() + 14); // 15 días contando el día actual
    const fechaFinStr = fechaFin.getFullYear() + '-' + 
                       String(fechaFin.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(fechaFin.getDate()).padStart(2, '0');
    document.getElementById('periodo_fin').value = fechaFinStr;
    
    // Calcular días pendientes automáticamente
    const diasCorrespondientesInput = document.getElementById('dias_correspondientes');
    const diasGozadosInput = document.getElementById('dias_gozados');
    const diasPendientesInput = document.getElementById('dias_pendientes');
    
    function calcularDiasPendientes() {
        const totalDias = parseInt(diasCorrespondientesInput.value) || 0;
        const diasGozados = parseInt(diasGozadosInput.value) || 0;
        diasPendientesInput.value = totalDias - diasGozados;
    }
    
    diasCorrespondientesInput.addEventListener('change', calcularDiasPendientes);
    diasGozadosInput.addEventListener('change', calcularDiasPendientes);
    
    // Calcular días automáticamente basado en las fechas
    const periodoInicioInput = document.getElementById('periodo_inicio');
    const periodoFinInput = document.getElementById('periodo_fin');
    
    function calcularDiasVacaciones() {
        if (periodoInicioInput.value && periodoFinInput.value) {
            const inicio = new Date(periodoInicioInput.value);
            const fin = new Date(periodoFinInput.value);
            
            // Si la fecha fin es anterior a la fecha inicio, no hacer nada
            if (fin < inicio) return;
            
            // Calcular diferencia en días
            const diffTime = Math.abs(fin - inicio);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 para incluir el día inicial
            
            diasCorrespondientesInput.value = diffDays;
            diasGozadosInput.value = diffDays;
            calcularDiasPendientes();
        }
    }
    
    periodoInicioInput.addEventListener('change', calcularDiasVacaciones);
    periodoFinInput.addEventListener('change', calcularDiasVacaciones);
    
    // Inicializar valores
    calcularDiasVacaciones();
    
    // Validación del formulario
    const form = document.getElementById('formNuevasVacaciones');
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validar fechas
        const fechaInicio = new Date(periodoInicioInput.value);
        const fechaFin = new Date(periodoFinInput.value);
        
        if (fechaFin < fechaInicio) {
            isValid = false;
            alert('La fecha de finalización no puede ser anterior a la fecha de inicio');
        }
        
        // Validar días
        const totalDias = parseInt(diasCorrespondientesInput.value) || 0;
        const diasGozados = parseInt(diasGozadosInput.value) || 0;
        const diasPendientes = parseInt(diasPendientesInput.value) || 0;
        
        if (totalDias <= 0) {
            isValid = false;
            alert('Los días correspondientes deben ser mayores a cero');
        }
        
        if (diasGozados < 0) {
            isValid = false;
            alert('Los días gozados no pueden ser negativos');
        }
        
        if (diasPendientes < 0) {
            isValid = false;
            alert('Los días pendientes no pueden ser negativos');
        }
        
        if (diasGozados + diasPendientes !== totalDias) {
            isValid = false;
            alert('La suma de días gozados y pendientes debe ser igual a los días correspondientes');
        }
        
        if (!isValid) {
            event.preventDefault();
        }
    });
});
</script> 