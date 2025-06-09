<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corregir Estado de Periodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Corregir Estado de Periodos de Pago</h1>
        
        <?php
        try {
            $db = getDB();
            
            // Verificar si la tabla existe
            $checkTable = $db->query("SHOW TABLES LIKE 'Periodos_Pago'");
            $tableExists = ($checkTable->rowCount() > 0);
            
            if (!$tableExists) {
                echo '<div class="alert alert-danger">
                    <strong>Error:</strong> La tabla Periodos_Pago no existe.
                    <hr>
                    <a href="insertar_periodos_pago.php" class="btn btn-primary">Crear Tabla e Insertar Períodos</a>
                </div>';
            } else {
                // Si se envió el formulario para corregir
                if (isset($_POST['action']) && $_POST['action'] == 'fix') {
                    // Actualizar todos los períodos a estado 'Abierto'
                    $updateQuery = "UPDATE Periodos_Pago SET estado = 'Abierto'";
                    $stmt = $db->prepare($updateQuery);
                    $result = $stmt->execute();
                    
                    if ($result) {
                        echo '<div class="alert alert-success">
                            <strong>¡Éxito!</strong> Todos los períodos han sido actualizados al estado "Abierto".
                        </div>';
                    } else {
                        echo '<div class="alert alert-danger">
                            <strong>Error:</strong> No se pudieron actualizar los períodos.
                        </div>';
                    }
                }
                
                // Mostrar los periodos actuales y su estado
                $selectQuery = "SELECT id_periodo, fecha_inicio, fecha_fin, tipo, estado FROM Periodos_Pago ORDER BY fecha_inicio DESC";
                $stmt = $db->prepare($selectQuery);
                $stmt->execute();
                $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($periodos) > 0) {
                    echo '<div class="card mb-4">
                        <div class="card-header">Períodos actuales</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                    
                    $allOpen = true;
                    foreach ($periodos as $periodo) {
                        echo '<tr>';
                        echo '<td>' . $periodo['id_periodo'] . '</td>';
                        echo '<td>' . $periodo['fecha_inicio'] . '</td>';
                        echo '<td>' . $periodo['fecha_fin'] . '</td>';
                        echo '<td>' . $periodo['tipo'] . '</td>';
                        
                        $stateClass = ($periodo['estado'] == 'Abierto') ? 'text-success' : 'text-warning';
                        echo '<td><span class="' . $stateClass . '">' . $periodo['estado'] . '</span></td>';
                        
                        if ($periodo['estado'] != 'Abierto') {
                            $allOpen = false;
                        }
                        
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table></div>';
                    
                    if ($allOpen) {
                        echo '<div class="alert alert-success mt-3">
                            <strong>¡Todo en orden!</strong> Todos los períodos ya están en estado "Abierto".
                        </div>';
                    } else {
                        echo '<form method="post" class="mt-3">
                            <input type="hidden" name="action" value="fix">
                            <button type="submit" class="btn btn-primary">Establecer todos los períodos como "Abierto"</button>
                        </form>';
                    }
                    
                    echo '</div></div>';
                } else {
                    echo '<div class="alert alert-warning">
                        <strong>Advertencia:</strong> No hay períodos en la tabla Periodos_Pago.
                        <hr>
                        <a href="insertar_periodos_pago.php" class="btn btn-primary">Insertar Períodos</a>
                    </div>';
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">
                <strong>Error:</strong> ' . $e->getMessage() . '
            </div>';
        }
        ?>
        
        <div class="mt-4">
            <a href="periodos_pago_diagnostico.php" class="btn btn-secondary">Volver al Diagnóstico</a>
            <a href="index.php?page=planillas/generar" class="btn btn-success">Ir a Generar Planilla</a>
        </div>
    </div>
</body>
</html> 