<?php

// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar que se hayan enviado los parámetros necesarios
if (!isset($_GET['id_periodo'])) {
    setFlashMessage('Parámetros insuficientes para generar la planilla', 'danger');
    header('Location: ' . BASE_URL . '?page=planillas/generar');
    exit;
}

$pageTitle = 'Procesando Planilla';
$activeMenu = 'planillas';

// Obtener parámetros
$id_periodo = intval($_GET['id_periodo']);
$id_departamento = isset($_GET['id_departamento']) ? intval($_GET['id_departamento']) : 0;
$descripcion = isset($_GET['descripcion']) ? trim($_GET['descripcion']) : '';
$incluir_bonos = isset($_GET['incluir_bonos']) ? intval($_GET['incluir_bonos']) : 0;
$incluir_horas_extra = isset($_GET['incluir_horas_extra']) ? intval($_GET['incluir_horas_extra']) : 0;

// Validar período
if ($id_periodo <= 0) {
    setFlashMessage('Debe seleccionar un período válido', 'danger');
    header('Location: ' . BASE_URL . '?page=planillas/generar');
    exit;
}

$db = getDB();
$periodo = null;
$empleados = [];
$errores = [];
$resultado = [
    'status' => 'error',
    'mensaje' => 'No se pudo generar la planilla',
    'id_planilla' => 0,
    'empleados_procesados' => 0,
    'total_planilla' => 0
];

// Al principio del archivo, después de obtener parámetros
$debug_info = "URL: " . $_SERVER['REQUEST_URI'] . "<br>";
$debug_info .= "Periodo: $id_periodo<br>";
$debug_info .= "Departamento: $id_departamento<br>";
$debug_info .= "Descripción: $descripcion<br>";
$debug_info .= "Incluir bonos: $incluir_bonos<br>";
$debug_info .= "Incluir horas extra: $incluir_horas_extra<br>";
$debug_info .= "Usuario: " . ($_SESSION['user_id'] ?? 'No autenticado') . "<br>";

// Archivo de depuración
$debug_file = fopen('debug_planilla.txt', 'a');
fwrite($debug_file, date('Y-m-d H:i:s') . " - INICIO PROCESAMIENTO\n" . str_replace('<br>', "\n", $debug_info) . "\n");

try {
    // Mostrar parámetros para depuración
    echo "<!-- Debug: ";
    echo "id_periodo: " . $id_periodo . ", ";
    echo "id_departamento: " . $id_departamento . ", ";
    echo "descripcion: " . $descripcion . ", ";
    echo "usuario: " . ($_SESSION['user_id'] ?? 'No autenticado');
    echo " -->";
    
    fwrite($debug_file, "Obteniendo información del período...\n");
    // Obtener información del período
    $queryPeriodo = "SELECT * FROM Periodos_Pago WHERE id_periodo = :id_periodo";
    $stmtPeriodo = $db->prepare($queryPeriodo);
    $stmtPeriodo->bindParam(':id_periodo', $id_periodo, PDO::PARAM_INT);
    $stmtPeriodo->execute();
    
    if ($stmtPeriodo->rowCount() == 0) {
        fwrite($debug_file, "ERROR: Período no encontrado\n");
        throw new Exception('El período especificado no existe');
    }
    
    $periodo = $stmtPeriodo->fetch(PDO::FETCH_ASSOC);
    fwrite($debug_file, "Período encontrado: " . json_encode($periodo) . "\n");
    
    // Verificar si ya existe una planilla para este período y departamento
    fwrite($debug_file, "Verificando si existe planilla para este período...\n");
    $queryVerificar = "SELECT id_planilla FROM Planillas 
                      WHERE id_periodo = :id_periodo";
    $stmtVerificar = $db->prepare($queryVerificar);
    $stmtVerificar->bindParam(':id_periodo', $id_periodo, PDO::PARAM_INT);
    $stmtVerificar->execute();
    
    if ($stmtVerificar->rowCount() > 0) {
        $planillaExistente = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
        fwrite($debug_file, "ERROR: Ya existe una planilla para este período\n");
        throw new Exception('Ya existe una planilla para el período seleccionado');
    }
    
    // Obtener empleados activos para el período y departamento (si aplica)
    fwrite($debug_file, "Buscando empleados activos...\n");
    $queryEmpleados = "SELECT e.*, 
                      c.salario, c.bonificacion_incentivo,
                      d.nombre as departamento, p.nombre as puesto
                      FROM Empleados e
                      JOIN Contratos c ON e.id_empleado = c.id_empleado AND c.fecha_fin IS NULL
                      LEFT JOIN Puestos p ON c.id_puesto = p.id_puesto
                      LEFT JOIN Departamentos d ON p.id_departamento = d.id_departamento
                      WHERE e.estado = 'Activo'";
    
    // Si se especificó departamento, filtrar
    if ($id_departamento > 0) {
        $queryEmpleados .= " AND p.id_departamento = :id_departamento";
    }
    
    $queryEmpleados .= " ORDER BY e.primer_apellido, e.primer_nombre";
    fwrite($debug_file, "Query empleados: " . $queryEmpleados . "\n");
    
    $stmtEmpleados = $db->prepare($queryEmpleados);
    
    if ($id_departamento > 0) {
        $stmtEmpleados->bindParam(':id_departamento', $id_departamento, PDO::PARAM_INT);
    }
    
    $stmtEmpleados->execute();
    $empleados = $stmtEmpleados->fetchAll(PDO::FETCH_ASSOC);
    
    fwrite($debug_file, "Empleados encontrados: " . count($empleados) . "\n");
    
    if (count($empleados) == 0) {
        fwrite($debug_file, "ERROR: No se encontraron empleados activos\n");
        throw new Exception('No se encontraron empleados activos para generar la planilla');
    }
    
    // Iniciar transacción para asegurar integridad
    $db->beginTransaction();
    fwrite($debug_file, "Iniciando transacción...\n");
    
    // Crear la planilla
    fwrite($debug_file, "Creando planilla...\n");
    $queryPlanilla = "INSERT INTO Planillas 
                     (id_periodo, descripcion, fecha_generacion, estado, usuario_genero)
                     VALUES 
                     (:id_periodo, :descripcion, NOW(), 'Borrador', :usuario_genero)";
    
    $stmtPlanilla = $db->prepare($queryPlanilla);
    $stmtPlanilla->bindParam(':id_periodo', $id_periodo, PDO::PARAM_INT);
    $stmtPlanilla->bindParam(':descripcion', $descripcion);
    
    // Asegurarse que usuario_genero sea string
    $usuario_genero = isset($_SESSION['user_id']) ? strval($_SESSION['user_id']) : "1";
    $stmtPlanilla->bindParam(':usuario_genero', $usuario_genero);
    
    fwrite($debug_file, "Ejecutando query de inserción de planilla...\n");
    $stmtPlanilla->execute();
    $id_planilla = $db->lastInsertId();
    
    fwrite($debug_file, "ID Planilla generada: " . $id_planilla . "\n");
    
    if (!$id_planilla) {
        fwrite($debug_file, "ERROR: No se pudo crear la planilla\n");
        throw new Exception('Error al crear la planilla');
    }
    
    // Procesar cada empleado
    $empleadosProcesados = 0;
    $totalPlanilla = 0;
    
    fwrite($debug_file, "Iniciando procesamiento de " . count($empleados) . " empleados...\n");
    
    foreach ($empleados as $empleado) {
        fwrite($debug_file, "Procesando empleado: " . $empleado['primer_nombre'] . " " . $empleado['primer_apellido'] . "\n");
        
        $salario_base = floatval($empleado['salario']);
        $bonificaciones = floatval($empleado['bonificacion_incentivo']);
        $horas_extra = 0;
        $otras_percepciones = 0;
        
        // Si se deben incluir bonos
        if ($incluir_bonos) {
            fwrite($debug_file, "Incluyendo bonificación estándar de Q250.00\n");
            $otras_percepciones += 250.00; // Bonificación estándar
        }
        
        // Si se deben incluir horas extra, buscar registros de horas extra para el período
        if ($incluir_horas_extra) {
            fwrite($debug_file, "Buscando horas extra para empleado...\n");
            try {
                $queryHoras = "SELECT SUM(horas) as total_horas FROM Horas_Extra 
                              WHERE id_empleado = :id_empleado 
                              AND fecha BETWEEN :fecha_inicio AND :fecha_fin
                              AND estado = 'Aprobado'";
                
                $stmtHoras = $db->prepare($queryHoras);
                $stmtHoras->bindParam(':id_empleado', $empleado['id_empleado'], PDO::PARAM_INT);
                $stmtHoras->bindParam(':fecha_inicio', $periodo['fecha_inicio']);
                $stmtHoras->bindParam(':fecha_fin', $periodo['fecha_fin']);
                $stmtHoras->execute();
                
                $horas = $stmtHoras->fetch(PDO::FETCH_ASSOC);
                if ($horas && $horas['total_horas']) {
                    // Calculate overtime as 1.5x regular hourly rate
                    $hourly_rate = $salario_base / 30 / 8; // daily rate / 8 hours
                    $horas_extra = floatval($horas['total_horas']) * $hourly_rate * 1.5;
                    fwrite($debug_file, "Horas extra encontradas: " . $horas['total_horas'] . " horas, monto: Q" . $horas_extra . "\n");
                } else {
                    fwrite($debug_file, "No se encontraron horas extra para este empleado\n");
                }
            } catch (Exception $e) {
                fwrite($debug_file, "ERROR al buscar horas extra: " . $e->getMessage() . "\n");
            }
        }
        
        // Calcular deducciones
        $igss = $salario_base * 0.0483; // 4.83% de IGSS
        $isr = 0; // El ISR se debe calcular según tablas y procedimientos específicos
        $otras_deducciones = 0;
        
        fwrite($debug_file, "Cálculos: Salario=$salario_base, Bonificaciones=$bonificaciones, IGSS=$igss\n");
        
        // Calcular totales
        $total_percepciones = $salario_base + $bonificaciones + $horas_extra + $otras_percepciones;
        $total_deducciones = $igss + $isr + $otras_deducciones;
        $salario_liquido = $total_percepciones - $total_deducciones;
        
        fwrite($debug_file, "Totales: Percepciones=$total_percepciones, Deducciones=$total_deducciones, Líquido=$salario_liquido\n");
        
        // Registrar detalle de planilla para el empleado
        fwrite($debug_file, "Insertando detalle de planilla para el empleado...\n");
        
        $queryDetalle = "INSERT INTO Detalle_Planilla 
                        (id_planilla, id_empleado, dias_trabajados, salario_base, bonificacion_incentivo, 
                         horas_extra, monto_horas_extra, comisiones, bonificaciones_adicionales, 
                         salario_total, igss_laboral, isr_retenido, otras_deducciones, anticipos, prestamos, 
                         descuentos_judiciales, total_deducciones, liquido_recibir)
                        VALUES 
                        (:id_planilla, :id_empleado, 30, :salario_base, :bonificaciones, 
                         0, :horas_extra, 0, :otras_percepciones, 
                         :total_percepciones, :igss, :isr, :otras_deducciones, 0, 0, 
                         0, :total_deducciones, :salario_liquido)";
        
        $stmtDetalle = $db->prepare($queryDetalle);
        $stmtDetalle->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
        $stmtDetalle->bindParam(':id_empleado', $empleado['id_empleado'], PDO::PARAM_INT);
        $stmtDetalle->bindParam(':salario_base', $salario_base);
        $stmtDetalle->bindParam(':bonificaciones', $bonificaciones);
        $stmtDetalle->bindParam(':horas_extra', $horas_extra);
        $stmtDetalle->bindParam(':otras_percepciones', $otras_percepciones);
        $stmtDetalle->bindParam(':igss', $igss);
        $stmtDetalle->bindParam(':isr', $isr);
        $stmtDetalle->bindParam(':otras_deducciones', $otras_deducciones);
        $stmtDetalle->bindParam(':total_percepciones', $total_percepciones);
        $stmtDetalle->bindParam(':total_deducciones', $total_deducciones);
        $stmtDetalle->bindParam(':salario_liquido', $salario_liquido);
        
        if ($stmtDetalle->execute()) {
            $empleadosProcesados++;
            $totalPlanilla += $salario_liquido;
        }
    }
    
    fwrite($debug_file, "Total empleados procesados: $empleadosProcesados\n");
    
    // Actualizar totales en la planilla
    fwrite($debug_file, "Actualizando totales en la planilla...\n");
    
    // Primero calculamos los totales
    $queryTotales = "SELECT 
                      SUM(salario_total) as total_bruto,
                      SUM(total_deducciones) as total_deducciones,
                      SUM(liquido_recibir) as total_neto
                     FROM Detalle_Planilla 
                     WHERE id_planilla = :id_planilla";
                     
    $stmtTotales = $db->prepare($queryTotales);
    $stmtTotales->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtTotales->execute();
    $totales = $stmtTotales->fetch(PDO::FETCH_ASSOC);
    
    fwrite($debug_file, "Totales calculados: " . json_encode($totales) . "\n");
    
    // Ahora sí actualizamos la planilla
    $queryActualizar = "UPDATE Planillas SET 
                       total_bruto = :total_bruto,
                       total_deducciones = :total_deducciones,
                       total_neto = :total_neto
                       WHERE id_planilla = :id_planilla";
    
    $stmtActualizar = $db->prepare($queryActualizar);
    $stmtActualizar->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtActualizar->bindParam(':total_bruto', $totales['total_bruto'], PDO::PARAM_STR);
    $stmtActualizar->bindParam(':total_deducciones', $totales['total_deducciones'], PDO::PARAM_STR);
    $stmtActualizar->bindParam(':total_neto', $totales['total_neto'], PDO::PARAM_STR);
    $stmtActualizar->execute();
    
    // Confirmar transacción
    fwrite($debug_file, "Confirmando transacción...\n");
    $db->commit();
    
    $resultado = [
        'status' => 'success',
        'mensaje' => 'Planilla generada correctamente',
        'id_planilla' => $id_planilla,
        'empleados_procesados' => $empleadosProcesados,
        'total_planilla' => $totalPlanilla
    ];
    
    fwrite($debug_file, "ÉXITO: Planilla generada. ID=$id_planilla, Empleados=$empleadosProcesados\n");
    
    setFlashMessage('Planilla generada correctamente. Se procesaron ' . $empleadosProcesados . ' empleados.', 'success');
    
    fwrite($debug_file, "Redireccionando a: " . BASE_URL . '?page=planillas/ver&id=' . $id_planilla . "\n");
    header('Location: ' . BASE_URL . '?page=planillas/ver&id=' . $id_planilla);
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
        fwrite($debug_file, "ERROR: Transacción revertida\n");
    }
    
    // Mostrar información detallada del error
    $error_message = 'Error al generar la planilla: ' . $e->getMessage();
    
    fwrite($debug_file, "ERROR FINAL: " . $error_message . "\n");
    if ($e instanceof PDOException) {
        $error_message .= ' - SQL Error: ' . $e->getCode();
        fwrite($debug_file, "SQL Error Code: " . $e->getCode() . "\n");
        
        // Guardar detalles en el log para depuración
        error_log('Error SQL en generación de planilla: ' . $e->getMessage() . ' - ' . $e->getCode());
    }
    
    setFlashMessage($error_message, 'danger');
    fwrite($debug_file, "Redireccionando a página de generación por ERROR\n");
    fwrite($debug_file, "--------- FIN DE PROCESAMIENTO CON ERROR ---------\n\n");
    fclose($debug_file);
    
    header('Location: ' . BASE_URL . '?page=planillas/generar');
    exit;
}

// Cerrar archivo de depuración en caso de éxito
fwrite($debug_file, "--------- FIN DE PROCESAMIENTO EXITOSO ---------\n\n");
fclose($debug_file);
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-cog fa-spin fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Procesando la generación de la planilla, por favor espere...</p>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Estado del Proceso</h6>
        </div>
        <div class="card-body">
            <div class="progress mb-3">
                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
            </div>
            <div id="status" class="text-center">Iniciando proceso...</div>
            
            <div id="resultado" class="mt-4 d-none">
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> Planilla generada correctamente</h5>
                    <p>Se han procesado <span id="numEmpleados">0</span> empleados por un total de <span id="totalPlanilla">Q 0.00</span></p>
                </div>
                <div class="text-center">
                    <a id="btnVerPlanilla" href="#" class="btn btn-primary">
                        <i class="fas fa-eye fa-fw"></i> Ver Planilla
                    </a>
                    <a href="<?php echo BASE_URL; ?>?page=planillas/lista" class="btn btn-secondary">
                        <i class="fas fa-list fa-fw"></i> Ver Lista de Planillas
                    </a>
                </div>
            </div>
            
            <div id="error" class="mt-4 d-none">
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Error al generar la planilla</h5>
                    <p id="mensajeError">Ocurrió un error durante el proceso.</p>
                </div>
                <div class="text-center">
                    <a href="<?php echo BASE_URL; ?>?page=planillas/generar" class="btn btn-primary">
                        <i class="fas fa-redo fa-fw"></i> Intentar Nuevamente
                    </a>
                    <a href="<?php echo BASE_URL; ?>?page=planillas/lista" class="btn btn-secondary">
                        <i class="fas fa-list fa-fw"></i> Ver Lista de Planillas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const progressBar = document.getElementById('progressBar');
    const status = document.getElementById('status');
    const resultado = document.getElementById('resultado');
    const error = document.getElementById('error');
    const numEmpleados = document.getElementById('numEmpleados');
    const totalPlanilla = document.getElementById('totalPlanilla');
    const btnVerPlanilla = document.getElementById('btnVerPlanilla');
    const mensajeError = document.getElementById('mensajeError');
    
    // Simular progreso (en un sistema real, esto podría ser reemplazado por actualizaciones AJAX)
    let progress = 0;
    const interval = setInterval(function() {
        progress += 5;
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', progress);
        
        if (progress <= 30) {
            status.textContent = "Obteniendo información de empleados...";
        } else if (progress <= 60) {
            status.textContent = "Calculando salarios y deducciones...";
        } else if (progress <= 90) {
            status.textContent = "Registrando datos en el sistema...";
        } else {
            status.textContent = "Finalizando proceso...";
        }
        
        if (progress >= 100) {
            clearInterval(interval);
            
            // Mostrar resultado según el estado almacenado en PHP
            const resultadoPHP = <?php echo json_encode($resultado); ?>;
            
            if (resultadoPHP.status === 'success') {
                // Mostrar éxito
                resultado.classList.remove('d-none');
                numEmpleados.textContent = resultadoPHP.empleados_procesados;
                totalPlanilla.textContent = 'Q ' + parseFloat(resultadoPHP.total_planilla).toFixed(2);
                btnVerPlanilla.href = '<?php echo BASE_URL; ?>?page=planillas/ver&id=' + resultadoPHP.id_planilla;
                
                // Redireccionar automáticamente después de 2 segundos
                setTimeout(function() {
                    window.location.href = '<?php echo BASE_URL; ?>?page=planillas/ver&id=' + resultadoPHP.id_planilla;
                }, 2000);
            } else {
                // Mostrar error
                error.classList.remove('d-none');
                mensajeError.textContent = resultadoPHP.mensaje;
            }
        }
    }, 100);
});
</script> 