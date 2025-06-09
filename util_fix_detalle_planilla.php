<?php
require_once 'config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get the planilla ID from URL parameter
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Create test data if form was submitted
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $db = getDB();
        
        // Handle create test data action
        if ($_POST['action'] === 'create_test_data' && !empty($_POST['id_planilla'])) {
            $targetPlanillaId = intval($_POST['id_planilla']);
            
            // First check if the planilla exists
            $checkPlanilla = $db->prepare("SELECT * FROM Planillas WHERE id_planilla = :id_planilla");
            $checkPlanilla->bindParam(':id_planilla', $targetPlanillaId, PDO::PARAM_INT);
            $checkPlanilla->execute();
            
            if ($checkPlanilla->rowCount() === 0) {
                $message = "Error: No existe una planilla con ID $targetPlanillaId";
                $messageType = 'danger';
            } else {
                // Check if there are already records for this planilla
                $checkExisting = $db->prepare("SELECT COUNT(*) as count FROM Detalle_Planilla WHERE id_planilla = :id_planilla");
                $checkExisting->bindParam(':id_planilla', $targetPlanillaId, PDO::PARAM_INT);
                $checkExisting->execute();
                $existingCount = $checkExisting->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($existingCount > 0) {
                    $message = "Esta planilla ya tiene $existingCount registros de detalle. No se necesitan registros de prueba.";
                    $messageType = 'warning';
                } else {
                    // Get active employees for test data
                    $getEmployees = $db->prepare("SELECT id_empleado FROM empleados WHERE estado = 'Activo' LIMIT 5");
                    $getEmployees->execute();
                    $employees = $getEmployees->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (count($employees) === 0) {
                        $message = "No se encontraron empleados activos para crear registros de prueba.";
                        $messageType = 'danger';
                    } else {
                        // Create test records for each employee
                        $insertCount = 0;
                        $insertStmt = $db->prepare("
                            INSERT INTO Detalle_Planilla (
                                id_planilla, id_empleado, dias_trabajados, salario_base, 
                                bonificacion_incentivo, horas_extra, monto_horas_extra, comisiones,
                                bonificaciones_adicionales, salario_total, igss_laboral, isr_retenido,
                                otras_deducciones, anticipos, prestamos, descuentos_judiciales,
                                total_deducciones, liquido_recibir
                            ) VALUES (
                                :id_planilla, :id_empleado, 30, 5000,
                                250, 0, 0, 0,
                                0, 5250, 250, 0,
                                0, 0, 0, 0,
                                250, 5000
                            )
                        ");
                        
                        foreach ($employees as $employeeId) {
                            $insertStmt->bindParam(':id_planilla', $targetPlanillaId, PDO::PARAM_INT);
                            $insertStmt->bindParam(':id_empleado', $employeeId, PDO::PARAM_INT);
                            $insertStmt->execute();
                            $insertCount++;
                        }
                        
                        $message = "Se han creado $insertCount registros de prueba para la planilla ID $targetPlanillaId";
                        $messageType = 'success';
                    }
                }
            }
        }
        
        // Handle delete test data action
        else if ($_POST['action'] === 'delete_test_data' && !empty($_POST['id_planilla'])) {
            $targetPlanillaId = intval($_POST['id_planilla']);
            
            $deleteStmt = $db->prepare("DELETE FROM Detalle_Planilla WHERE id_planilla = :id_planilla");
            $deleteStmt->bindParam(':id_planilla', $targetPlanillaId, PDO::PARAM_INT);
            $deleteStmt->execute();
            $deletedCount = $deleteStmt->rowCount();
            
            if ($deletedCount > 0) {
                $message = "Se han eliminado $deletedCount registros de detalle para la planilla ID $targetPlanillaId";
                $messageType = 'success';
            } else {
                $message = "No se encontraron registros para eliminar en la planilla ID $targetPlanillaId";
                $messageType = 'warning';
            }
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// If ID was specified, use it for the form
if ($id_planilla > 0) {
    $planilla_for_form = $id_planilla;
} else {
    // Try to get the most recent planilla ID
    try {
        $db = getDB();
        $query = "SELECT id_planilla FROM Planillas ORDER BY fecha_generacion DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $planilla_for_form = $result ? $result['id_planilla'] : '';
    } catch (Exception $e) {
        $planilla_for_form = '';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Detalle Planilla Utility</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-4">
        <h1>Utilidad para Arreglar Detalle de Planilla</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Crear Datos de Prueba para una Planilla
            </div>
            <div class="card-body">
                <p class="card-text">
                    Esta herramienta creará registros de prueba en la tabla <code>Detalle_Planilla</code> para la planilla especificada.
                    Esto es útil si una planilla no muestra detalles porque faltan los registros en la tabla.
                </p>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="id_planilla" class="form-label">ID de Planilla:</label>
                        <input type="number" class="form-control" id="id_planilla" name="id_planilla" value="<?php echo htmlspecialchars($planilla_for_form); ?>" required>
                    </div>
                    <input type="hidden" name="action" value="create_test_data">
                    <button type="submit" class="btn btn-primary">Crear Datos de Prueba</button>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                Eliminar Datos de Prueba
            </div>
            <div class="card-body">
                <p class="card-text">
                    Esta opción eliminará todos los registros de detalle asociados a la planilla especificada.
                    Úsela con precaución, ya que los datos eliminados no se pueden recuperar.
                </p>
                
                <form method="post" action="" onsubmit="return confirm('¿Está seguro que desea eliminar todos los registros de detalle para esta planilla?');">
                    <div class="mb-3">
                        <label for="id_planilla_delete" class="form-label">ID de Planilla:</label>
                        <input type="number" class="form-control" id="id_planilla_delete" name="id_planilla" value="<?php echo htmlspecialchars($planilla_for_form); ?>" required>
                    </div>
                    <input type="hidden" name="action" value="delete_test_data">
                    <button type="submit" class="btn btn-danger">Eliminar Datos</button>
                </form>
            </div>
        </div>
        
        <h2 class="mt-4">Planillas Disponibles</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Fecha Generación</th>
                        <th>Estado</th>
                        <th>Registros Detalle</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $db = getDB();
                        $query = "SELECT p.*, 
                                (SELECT COUNT(*) FROM Detalle_Planilla dp WHERE dp.id_planilla = p.id_planilla) as detalles_count
                                FROM Planillas p
                                ORDER BY p.fecha_generacion DESC
                                LIMIT 10";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $planillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($planillas as $planilla) {
                            echo '<tr>';
                            echo '<td>' . $planilla['id_planilla'] . '</td>';
                            echo '<td>' . htmlspecialchars($planilla['descripcion'] ?? 'N/A') . '</td>';
                            echo '<td>' . ($planilla['fecha_generacion'] ?? 'N/A') . '</td>';
                            echo '<td>' . ($planilla['estado'] ?? 'N/A') . '</td>';
                            echo '<td>' . $planilla['detalles_count'] . '</td>';
                            echo '<td>';
                            echo '<a href="index.php?page=planillas/ver&id=' . $planilla['id_planilla'] . '" class="btn btn-sm btn-primary">Ver</a> ';
                            echo '<a href="?id=' . $planilla['id_planilla'] . '" class="btn btn-sm btn-info">Seleccionar</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        if (count($planillas) === 0) {
                            echo '<tr><td colspan="6" class="text-center">No se encontraron planillas</td></tr>';
                        }
                    } catch (Exception $e) {
                        echo '<tr><td colspan="6" class="text-center text-danger">Error: ' . $e->getMessage() . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
            <a href="debug_planilla.php<?php echo $id_planilla ? "?id=$id_planilla" : ''; ?>" class="btn btn-warning">Diagnóstico Detallado</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 