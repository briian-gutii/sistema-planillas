<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || !hasPermission(ROL_ADMIN)) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Configuración de IGSS';
$activeMenu = 'configuracion';

// Obtener la configuración actual de IGSS
$db = getDB();
$query = "SELECT * FROM configuracion WHERE categoria = 'igss'";
$stmt = $db->prepare($query);
$stmt->execute();
$configuraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear un array asociativo para facilitar el acceso a los valores
$config = [];
foreach ($configuraciones as $configuracion) {
    $config[$configuracion['clave']] = $configuracion['valor'];
}

// Valores por defecto si no existen en la base de datos
$porcentajeLaboral = isset($config['porcentaje_laboral']) ? $config['porcentaje_laboral'] : 4.83;
$porcentajePatronal = isset($config['porcentaje_patronal']) ? $config['porcentaje_patronal'] : 10.67;
$topeMensual = isset($config['tope_mensual']) ? $config['tope_mensual'] : 0; // 0 significa sin tope

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Validar datos
        $porcentajeLaboral = isset($_POST['porcentaje_laboral']) ? (float)$_POST['porcentaje_laboral'] : 0;
        $porcentajePatronal = isset($_POST['porcentaje_patronal']) ? (float)$_POST['porcentaje_patronal'] : 0;
        $topeMensual = isset($_POST['tope_mensual']) ? (float)$_POST['tope_mensual'] : 0;
        
        if ($porcentajeLaboral <= 0 || $porcentajeLaboral > 100) {
            throw new Exception("El porcentaje laboral debe ser mayor a 0 y menor o igual a 100.");
        }
        
        if ($porcentajePatronal <= 0 || $porcentajePatronal > 100) {
            throw new Exception("El porcentaje patronal debe ser mayor a 0 y menor o igual a 100.");
        }
        
        if ($topeMensual < 0) {
            throw new Exception("El tope mensual no puede ser negativo.");
        }
        
        // Actualizar o insertar la configuración
        $claves = ['porcentaje_laboral', 'porcentaje_patronal', 'tope_mensual'];
        $valores = [$porcentajeLaboral, $porcentajePatronal, $topeMensual];
        
        for ($i = 0; $i < count($claves); $i++) {
            $clave = $claves[$i];
            $valor = $valores[$i];
            
            // Verificar si la configuración existe
            $query = "SELECT id FROM configuracion WHERE categoria = 'igss' AND clave = :clave";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':clave', $clave);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Actualizar
                $query = "UPDATE configuracion SET valor = :valor, updated_at = NOW() WHERE categoria = 'igss' AND clave = :clave";
            } else {
                // Insertar
                $query = "INSERT INTO configuracion (categoria, clave, valor, created_at) VALUES ('igss', :clave, :valor, NOW())";
            }
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':clave', $clave);
            $stmt->bindParam(':valor', $valor);
            $stmt->execute();
        }
        
        // Registrar en la bitácora
        $accion = "Actualización de configuración de IGSS";
        $detalles = json_encode([
            'porcentaje_laboral' => $porcentajeLaboral,
            'porcentaje_patronal' => $porcentajePatronal,
            'tope_mensual' => $topeMensual
        ]);
        
        $query = "INSERT INTO bitacora (id_usuario, accion, detalles, created_at)
                 VALUES (:id_usuario, :accion, :detalles, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
        $stmt->bindParam(':accion', $accion);
        $stmt->bindParam(':detalles', $detalles);
        $stmt->execute();
        
        $db->commit();
        setFlashMessage('Configuración de IGSS actualizada correctamente', 'success');
        header('Location: ' . BASE_URL . '?page=configuracion/igss');
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-hospital-user fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Configuración de porcentajes y parámetros del IGSS (Instituto Guatemalteco de Seguridad Social)</p>

    <div class="row">
        <!-- Información de IGSS -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del IGSS</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle fa-fw"></i> ¿Qué es el IGSS?</h5>
                        <p>El Instituto Guatemalteco de Seguridad Social (IGSS) es una institución gubernamental, autónoma, dedicada a brindar servicios de salud y seguridad social a la población que cuente con afiliación al instituto.</p>
                    </div>
                    
                    <h5 class="mt-4">Porcentajes actuales:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Cuota Laboral</h6>
                                    <p class="display-4 text-primary"><?php echo number_format($porcentajeLaboral, 2); ?>%</p>
                                    <p class="card-text text-muted">Porcentaje que se descuenta del salario del trabajador.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Cuota Patronal</h6>
                                    <p class="display-4 text-success"><?php echo number_format($porcentajePatronal, 2); ?>%</p>
                                    <p class="card-text text-muted">Porcentaje que debe aportar el patrono por cada trabajador.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mt-4">Tope de contribución:</h5>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Tope Mensual</h6>
                            <?php if ($topeMensual > 0): ?>
                                <p class="display-4 text-danger">Q <?php echo number_format($topeMensual, 2); ?></p>
                                <p class="card-text text-muted">Monto máximo sobre el cual se calcula la contribución al IGSS.</p>
                            <?php else: ?>
                                <p class="display-4 text-info">Sin Tope</p>
                                <p class="card-text text-muted">No hay un límite máximo para el cálculo de la contribución al IGSS.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Configuración -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actualizar Configuración</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="porcentaje_laboral" class="form-label">Porcentaje Laboral *</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentaje_laboral" name="porcentaje_laboral" value="<?php echo $porcentajeLaboral; ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Porcentaje que se descuenta del salario del trabajador</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="porcentaje_patronal" class="form-label">Porcentaje Patronal *</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentaje_patronal" name="porcentaje_patronal" value="<?php echo $porcentajePatronal; ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Porcentaje que debe aportar el patrono</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tope_mensual" class="form-label">Tope Mensual</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="tope_mensual" name="tope_mensual" value="<?php echo $topeMensual; ?>">
                            </div>
                            <div class="form-text">Monto máximo para calcular la contribución (0 = sin tope)</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save fa-fw"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(event) {
        const porcentajeLaboral = parseFloat(document.getElementById('porcentaje_laboral').value);
        const porcentajePatronal = parseFloat(document.getElementById('porcentaje_patronal').value);
        const topeMensual = parseFloat(document.getElementById('tope_mensual').value);
        
        if (porcentajeLaboral <= 0 || porcentajeLaboral > 100) {
            event.preventDefault();
            alert('El porcentaje laboral debe ser mayor a 0 y menor o igual a 100.');
        }
        
        if (porcentajePatronal <= 0 || porcentajePatronal > 100) {
            event.preventDefault();
            alert('El porcentaje patronal debe ser mayor a 0 y menor o igual a 100.');
        }
        
        if (topeMensual < 0) {
            event.preventDefault();
            alert('El tope mensual no puede ser negativo.');
        }
    });
});
</script> 