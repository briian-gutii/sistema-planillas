<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insertar Periodos de Pago</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Insertar Periodos de Pago</h1>
        <div class="card">
            <div class="card-body">
<?php
try {
    // Conectar a la base de datos
    $db = getDB();
    
    // Verificar si la tabla existe
    $checkTable = $db->query("SHOW TABLES LIKE 'Periodos_Pago'");
    $tableExists = ($checkTable->rowCount() > 0);
    
    if (!$tableExists) {
        echo '<div class="alert alert-warning">La tabla Periodos_Pago no existe. Creándola...</div>';
        
        // Crear la tabla
        $createQuery = "CREATE TABLE IF NOT EXISTS Periodos_Pago (
            id_periodo INT PRIMARY KEY AUTO_INCREMENT,
            fecha_inicio DATE NOT NULL,
            fecha_fin DATE NOT NULL,
            fecha_pago DATE NOT NULL,
            tipo ENUM('Quincenal', 'Mensual', 'Semanal') NOT NULL,
            estado ENUM('Abierto', 'Cerrado', 'Procesando') NOT NULL DEFAULT 'Abierto',
            anio INT NOT NULL,
            mes INT NOT NULL
        )";
        $db->exec($createQuery);
        
        echo '<div class="alert alert-success">Tabla Periodos_Pago creada correctamente.</div>';
    } else {
        echo '<div class="alert alert-info">La tabla Periodos_Pago ya existe.</div>';
    }
    
    // Verificar si hay datos en la tabla
    $countQuery = "SELECT COUNT(*) as total FROM Periodos_Pago";
    $stmt = $db->query($countQuery);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($count > 0) {
        echo '<div class="alert alert-info">Ya existen ' . $count . ' periodos en la tabla.</div>';
        
        // Mostrar los periodos existentes
        $selectQuery = "SELECT id_periodo, fecha_inicio, fecha_fin, tipo, estado FROM Periodos_Pago ORDER BY fecha_inicio DESC";
        $stmt = $db->query($selectQuery);
        $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<h4 class="mt-4">Periodos existentes:</h4>';
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-bordered">';
        echo '<thead><tr><th>ID</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Tipo</th><th>Estado</th></tr></thead>';
        echo '<tbody>';
        foreach ($periodos as $periodo) {
            echo '<tr>';
            echo '<td>' . $periodo['id_periodo'] . '</td>';
            echo '<td>' . $periodo['fecha_inicio'] . '</td>';
            echo '<td>' . $periodo['fecha_fin'] . '</td>';
            echo '<td>' . $periodo['tipo'] . '</td>';
            echo '<td>' . $periodo['estado'] . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    } else {
        echo '<div class="alert alert-warning">No hay periodos en la tabla. Insertando periodos de ejemplo...</div>';
        
        // Obtener año y mes actual
        $anioActual = date('Y');
        $mesActual = date('n');
        
        // Crear array con periodos a insertar
        $periodos = [];
        
        // Periodos del mes actual
        $primerDiaMesActual = date('Y-m-01');
        $ultimoDiaMesActual = date('Y-m-t');
        $medioDiaMesActual = date('Y-m-15');
        
        // Periodos quincenales del mes actual
        $periodos[] = [
            'fecha_inicio' => $primerDiaMesActual,
            'fecha_fin' => $medioDiaMesActual,
            'fecha_pago' => date('Y-m-d', strtotime($medioDiaMesActual . ' +1 day')),
            'tipo' => 'Quincenal',
            'anio' => $anioActual,
            'mes' => $mesActual
        ];
        
        $periodos[] = [
            'fecha_inicio' => date('Y-m-d', strtotime($medioDiaMesActual . ' +1 day')),
            'fecha_fin' => $ultimoDiaMesActual,
            'fecha_pago' => date('Y-m-d', strtotime($ultimoDiaMesActual . ' +1 day')),
            'tipo' => 'Quincenal',
            'anio' => $anioActual,
            'mes' => $mesActual
        ];
        
        // Periodo mensual del mes actual
        $periodos[] = [
            'fecha_inicio' => $primerDiaMesActual,
            'fecha_fin' => $ultimoDiaMesActual,
            'fecha_pago' => date('Y-m-d', strtotime($ultimoDiaMesActual . ' +1 day')),
            'tipo' => 'Mensual',
            'anio' => $anioActual,
            'mes' => $mesActual
        ];
        
        // Periodos del mes anterior
        $primerDiaMesAnterior = date('Y-m-01', strtotime('-1 month'));
        $ultimoDiaMesAnterior = date('Y-m-t', strtotime('-1 month'));
        $medioDiaMesAnterior = date('Y-m-15', strtotime('-1 month'));
        $mesAnterior = date('n', strtotime('-1 month'));
        $anioMesAnterior = date('Y', strtotime('-1 month'));
        
        // Periodo mensual del mes anterior
        $periodos[] = [
            'fecha_inicio' => $primerDiaMesAnterior,
            'fecha_fin' => $ultimoDiaMesAnterior,
            'fecha_pago' => date('Y-m-d', strtotime($ultimoDiaMesAnterior . ' +1 day')),
            'tipo' => 'Mensual',
            'anio' => $anioMesAnterior,
            'mes' => $mesAnterior
        ];
        
        // Periodos del próximo mes
        $primerDiaMesSiguiente = date('Y-m-01', strtotime('+1 month'));
        $ultimoDiaMesSiguiente = date('Y-m-t', strtotime('+1 month'));
        $medioDiaMesSiguiente = date('Y-m-15', strtotime('+1 month'));
        $mesSiguiente = date('n', strtotime('+1 month'));
        $anioMesSiguiente = date('Y', strtotime('+1 month'));
        
        // Periodo mensual del próximo mes
        $periodos[] = [
            'fecha_inicio' => $primerDiaMesSiguiente,
            'fecha_fin' => $ultimoDiaMesSiguiente,
            'fecha_pago' => date('Y-m-d', strtotime($ultimoDiaMesSiguiente . ' +1 day')),
            'tipo' => 'Mensual',
            'anio' => $anioMesSiguiente,
            'mes' => $mesSiguiente
        ];
        
        // Insertar periodos
        $insertQuery = "INSERT INTO Periodos_Pago (fecha_inicio, fecha_fin, fecha_pago, tipo, estado, anio, mes) 
                        VALUES (:fecha_inicio, :fecha_fin, :fecha_pago, :tipo, :estado, :anio, :mes)";
        $stmt = $db->prepare($insertQuery);
        
        $insertCount = 0;
        foreach ($periodos as $periodo) {
            $params = [
                ':fecha_inicio' => $periodo['fecha_inicio'],
                ':fecha_fin' => $periodo['fecha_fin'],
                ':fecha_pago' => $periodo['fecha_pago'],
                ':tipo' => $periodo['tipo'],
                ':estado' => 'Abierto',
                ':anio' => $periodo['anio'],
                ':mes' => $periodo['mes']
            ];
            
            if ($stmt->execute($params)) {
                $insertCount++;
            }
        }
        
        echo '<div class="alert alert-success">Se insertaron ' . $insertCount . ' periodos correctamente.</div>';
        
        // Mostrar los periodos insertados
        $selectQuery = "SELECT id_periodo, fecha_inicio, fecha_fin, tipo, estado FROM Periodos_Pago ORDER BY fecha_inicio DESC";
        $stmt = $db->query($selectQuery);
        $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<h4 class="mt-4">Periodos insertados:</h4>';
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-bordered">';
        echo '<thead><tr><th>ID</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Tipo</th><th>Estado</th></tr></thead>';
        echo '<tbody>';
        foreach ($periodos as $periodo) {
            echo '<tr>';
            echo '<td>' . $periodo['id_periodo'] . '</td>';
            echo '<td>' . $periodo['fecha_inicio'] . '</td>';
            echo '<td>' . $periodo['fecha_fin'] . '</td>';
            echo '<td>' . $periodo['tipo'] . '</td>';
            echo '<td>' . $periodo['estado'] . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
    
    echo '<div class="alert alert-success mt-4">Script completado con éxito.</div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
}
?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Volver al Inicio</a>
            <a href="index.php?page=planillas/generar" class="btn btn-success">Ir a Generar Planilla</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 