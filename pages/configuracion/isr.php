<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || !hasPermission(ROL_ADMIN)) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Configuración de ISR';
$activeMenu = 'configuracion';

// Obtener la configuración actual de ISR
$db = getDB();
$query = "SELECT * FROM configuracion WHERE categoria = 'isr'";
$stmt = $db->prepare($query);
$stmt->execute();
$configuraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear un array asociativo para facilitar el acceso a los valores
$config = [];
foreach ($configuraciones as $configuracion) {
    $config[$configuracion['clave']] = $configuracion['valor'];
}

// Valores por defecto si no existen en la base de datos
$limiteInferior = isset($config['limite_inferior']) ? $config['limite_inferior'] : 48000;
$limiteSuperior = isset($config['limite_superior']) ? $config['limite_superior'] : 300000;
$porcentajeTranche1 = isset($config['porcentaje_tranche1']) ? $config['porcentaje_tranche1'] : 5;
$porcentajeTranche2 = isset($config['porcentaje_tranche2']) ? $config['porcentaje_tranche2'] : 7;
$deduccionBasica = isset($config['deduccion_basica']) ? $config['deduccion_basica'] : 48000;
$deduccionEspecial = isset($config['deduccion_especial']) ? $config['deduccion_especial'] : 0;

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Validar datos
        $limiteInferior = isset($_POST['limite_inferior']) ? (float)$_POST['limite_inferior'] : 0;
        $limiteSuperior = isset($_POST['limite_superior']) ? (float)$_POST['limite_superior'] : 0;
        $porcentajeTranche1 = isset($_POST['porcentaje_tranche1']) ? (float)$_POST['porcentaje_tranche1'] : 0;
        $porcentajeTranche2 = isset($_POST['porcentaje_tranche2']) ? (float)$_POST['porcentaje_tranche2'] : 0;
        $deduccionBasica = isset($_POST['deduccion_basica']) ? (float)$_POST['deduccion_basica'] : 0;
        $deduccionEspecial = isset($_POST['deduccion_especial']) ? (float)$_POST['deduccion_especial'] : 0;
        
        if ($limiteInferior <= 0) {
            throw new Exception("El límite inferior debe ser mayor a 0.");
        }
        
        if ($limiteSuperior <= $limiteInferior) {
            throw new Exception("El límite superior debe ser mayor al límite inferior.");
        }
        
        if ($porcentajeTranche1 <= 0 || $porcentajeTranche1 > 100) {
            throw new Exception("El porcentaje del primer tramo debe ser mayor a 0 y menor o igual a 100.");
        }
        
        if ($porcentajeTranche2 <= 0 || $porcentajeTranche2 > 100) {
            throw new Exception("El porcentaje del segundo tramo debe ser mayor a 0 y menor o igual a 100.");
        }
        
        if ($deduccionBasica < 0) {
            throw new Exception("La deducción básica no puede ser negativa.");
        }
        
        if ($deduccionEspecial < 0) {
            throw new Exception("La deducción especial no puede ser negativa.");
        }
        
        // Actualizar o insertar la configuración
        $claves = ['limite_inferior', 'limite_superior', 'porcentaje_tranche1', 'porcentaje_tranche2', 'deduccion_basica', 'deduccion_especial'];
        $valores = [$limiteInferior, $limiteSuperior, $porcentajeTranche1, $porcentajeTranche2, $deduccionBasica, $deduccionEspecial];
        
        for ($i = 0; $i < count($claves); $i++) {
            $clave = $claves[$i];
            $valor = $valores[$i];
            
            // Verificar si la configuración existe
            $query = "SELECT id FROM configuracion WHERE categoria = 'isr' AND clave = :clave";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':clave', $clave);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Actualizar
                $query = "UPDATE configuracion SET valor = :valor, updated_at = NOW() WHERE categoria = 'isr' AND clave = :clave";
            } else {
                // Insertar
                $query = "INSERT INTO configuracion (categoria, clave, valor, created_at) VALUES ('isr', :clave, :valor, NOW())";
            }
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':clave', $clave);
            $stmt->bindParam(':valor', $valor);
            $stmt->execute();
        }
        
        // Registrar en la bitácora
        $accion = "Actualización de configuración de ISR";
        $detalles = json_encode([
            'limite_inferior' => $limiteInferior,
            'limite_superior' => $limiteSuperior,
            'porcentaje_tranche1' => $porcentajeTranche1,
            'porcentaje_tranche2' => $porcentajeTranche2,
            'deduccion_basica' => $deduccionBasica,
            'deduccion_especial' => $deduccionEspecial
        ]);
        
        $query = "INSERT INTO bitacora (id_usuario, accion, detalles, created_at)
                 VALUES (:id_usuario, :accion, :detalles, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
        $stmt->bindParam(':accion', $accion);
        $stmt->bindParam(':detalles', $detalles);
        $stmt->execute();
        
        $db->commit();
        setFlashMessage('Configuración de ISR actualizada correctamente', 'success');
        header('Location: ' . BASE_URL . '?page=configuracion/isr');
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-file-invoice fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Configuración de parámetros del ISR (Impuesto Sobre la Renta)</p>

    <div class="row">
        <!-- Información de ISR -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del ISR</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle fa-fw"></i> ¿Qué es el ISR?</h5>
                        <p>El Impuesto Sobre la Renta (ISR) es un impuesto que grava los ingresos obtenidos por personas individuales, jurídicas, entes o patrimonios, residentes o no en Guatemala. Para trabajadores en relación de dependencia, se debe retener y pagar mensualmente según los tramos establecidos.</p>
                    </div>
                    
                    <h5 class="mt-4">Configuración actual del ISR:</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Rango</th>
                                    <th>Importe</th>
                                    <th>Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>0 hasta Q <?php echo number_format($limiteInferior, 2); ?></td>
                                    <td>Imponible</td>
                                    <td>Exento (0%)</td>
                                </tr>
                                <tr>
                                    <td>Q <?php echo number_format($limiteInferior, 2); ?> hasta Q <?php echo number_format($limiteSuperior, 2); ?></td>
                                    <td>Sobre el excedente de Q <?php echo number_format($limiteInferior, 2); ?></td>
                                    <td><?php echo $porcentajeTranche1; ?>%</td>
                                </tr>
                                <tr>
                                    <td>Más de Q <?php echo number_format($limiteSuperior, 2); ?></td>
                                    <td>Sobre el excedente de Q <?php echo number_format($limiteSuperior, 2); ?></td>
                                    <td><?php echo $porcentajeTranche2; ?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h5 class="mt-4">Deducciones:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Deducción Básica Anual</h6>
                                    <p class="display-4 text-primary">Q <?php echo number_format($deduccionBasica, 2); ?></p>
                                    <p class="card-text text-muted">Monto mínimo anual no gravado con ISR.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Deducción Especial</h6>
                                    <p class="display-4 text-success">Q <?php echo number_format($deduccionEspecial, 2); ?></p>
                                    <p class="card-text text-muted">Deducciones adicionales permitidas por ley.</p>
                                </div>
                            </div>
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
                            <label for="limite_inferior" class="form-label">Límite Inferior *</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="limite_inferior" name="limite_inferior" value="<?php echo $limiteInferior; ?>" required>
                            </div>
                            <div class="form-text">Ingresos anuales exentos de ISR</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="limite_superior" class="form-label">Límite Superior *</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="limite_superior" name="limite_superior" value="<?php echo $limiteSuperior; ?>" required>
                            </div>
                            <div class="form-text">Límite entre el primer y segundo tramo</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="porcentaje_tranche1" class="form-label">% Primer Tramo *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentaje_tranche1" name="porcentaje_tranche1" value="<?php echo $porcentajeTranche1; ?>" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="porcentaje_tranche2" class="form-label">% Segundo Tramo *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentaje_tranche2" name="porcentaje_tranche2" value="<?php echo $porcentajeTranche2; ?>" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deduccion_basica" class="form-label">Deducción Básica *</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="deduccion_basica" name="deduccion_basica" value="<?php echo $deduccionBasica; ?>" required>
                            </div>
                            <div class="form-text">Monto mínimo anual no gravado</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deduccion_especial" class="form-label">Deducción Especial</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="deduccion_especial" name="deduccion_especial" value="<?php echo $deduccionEspecial; ?>">
                            </div>
                            <div class="form-text">Deducciones adicionales permitidas</div>
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
        const limiteInferior = parseFloat(document.getElementById('limite_inferior').value);
        const limiteSuperior = parseFloat(document.getElementById('limite_superior').value);
        const porcentajeTranche1 = parseFloat(document.getElementById('porcentaje_tranche1').value);
        const porcentajeTranche2 = parseFloat(document.getElementById('porcentaje_tranche2').value);
        
        if (limiteInferior <= 0) {
            event.preventDefault();
            alert('El límite inferior debe ser mayor a 0.');
        }
        
        if (limiteSuperior <= limiteInferior) {
            event.preventDefault();
            alert('El límite superior debe ser mayor al límite inferior.');
        }
        
        if (porcentajeTranche1 <= 0 || porcentajeTranche1 > 100) {
            event.preventDefault();
            alert('El porcentaje del primer tramo debe ser mayor a 0 y menor o igual a 100.');
        }
        
        if (porcentajeTranche2 <= 0 || porcentajeTranche2 > 100) {
            event.preventDefault();
            alert('El porcentaje del segundo tramo debe ser mayor a 0 y menor o igual a 100.');
        }
    });
});
</script> 