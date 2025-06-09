<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Periodos_Pago</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Diagnóstico de Periodos_Pago</h1>
        <div class="card">
            <div class="card-body">
<?php
try {
    // Conectar a la base de datos
    $db = getDB();
    
    // Probar la consulta que se usa en generar.php
    echo '<h4>Verificando consulta de generación de planilla:</h4>';
    
    $sql = "SELECT p.id_periodo, 
            CONCAT(DATE_FORMAT(p.fecha_inicio, '%d/%m/%Y'), ' - ', 
            DATE_FORMAT(p.fecha_fin, '%d/%m/%Y'), ' (', p.tipo, ')') as periodo_texto,
            (SELECT COUNT(*) FROM Planillas pl WHERE pl.id_periodo = p.id_periodo) as tiene_planilla
            FROM Periodos_Pago p
            WHERE p.estado = 'Abierto'
            ORDER BY p.fecha_inicio DESC 
            LIMIT 12";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($periodos) > 0) {
            echo '<div class="alert alert-success">La consulta se ejecutó correctamente y devolvió ' . count($periodos) . ' resultados.</div>';
            
            // Mostrar los periodos encontrados
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped">';
            echo '<thead><tr><th>ID</th><th>Periodo</th><th>Tiene planilla</th></tr></thead>';
            echo '<tbody>';
            foreach ($periodos as $periodo) {
                echo '<tr>';
                echo '<td>' . $periodo['id_periodo'] . '</td>';
                echo '<td>' . $periodo['periodo_texto'] . '</td>';
                echo '<td>' . $periodo['tiene_planilla'] . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">La consulta se ejecutó correctamente pero no devolvió resultados.</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error al ejecutar la consulta: ' . $e->getMessage() . '</div>';
    }
    
    // Verificar si la tabla existe y su estructura
    echo '<h4 class="mt-4">Verificando estructura de la tabla:</h4>';
    
    try {
        $checkTable = $db->query("SHOW TABLES LIKE 'Periodos_Pago'");
        $tableExists = ($checkTable->rowCount() > 0);
        
        if ($tableExists) {
            echo '<div class="alert alert-success">La tabla Periodos_Pago existe.</div>';
            
            // Mostrar la estructura de la tabla
            $describeQuery = "DESCRIBE Periodos_Pago";
            $stmt = $db->prepare($describeQuery);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="table-responsive">';
            echo '<table class="table table-sm table-bordered">';
            echo '<thead><tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr></thead>';
            echo '<tbody>';
            foreach ($columns as $column) {
                echo '<tr>';
                foreach ($column as $key => $value) {
                    echo '<td>' . $value . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
            
            // Verificar si hay datos en la tabla
            $countQuery = "SELECT COUNT(*) as total FROM Periodos_Pago";
            $stmt = $db->prepare($countQuery);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($count > 0) {
                echo '<div class="alert alert-info">La tabla contiene ' . $count . ' registros.</div>';
                
                // Verificar el estado de los periodos
                $stateQuery = "SELECT estado, COUNT(*) as total FROM Periodos_Pago GROUP BY estado";
                $stmt = $db->prepare($stateQuery);
                $stmt->execute();
                $states = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<h5>Distribución por estado:</h5>';
                echo '<div class="table-responsive">';
                echo '<table class="table table-sm">';
                echo '<thead><tr><th>Estado</th><th>Cantidad</th></tr></thead>';
                echo '<tbody>';
                foreach ($states as $state) {
                    echo '<tr>';
                    echo '<td>' . $state['estado'] . '</td>';
                    echo '<td>' . $state['total'] . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
                
                // Si no hay períodos en estado "Abierto", ofrecer solución
                $hasOpenPeriods = false;
                foreach ($states as $state) {
                    if ($state['estado'] == 'Abierto' && $state['total'] > 0) {
                        $hasOpenPeriods = true;
                        break;
                    }
                }
                
                if (!$hasOpenPeriods) {
                    echo '<div class="alert alert-warning">
                        <strong>Problema detectado:</strong> No hay períodos en estado "Abierto". 
                        Esto explica por qué no aparecen períodos en la página de generación de planilla.
                        <hr>
                        <div class="mt-2">
                            <a href="periodos_pago_fix.php" class="btn btn-primary">Corregir Estado de Períodos</a>
                        </div>
                    </div>';
                }
            } else {
                echo '<div class="alert alert-warning">
                    <strong>Problema detectado:</strong> La tabla existe pero no tiene registros. 
                    Por favor, use el script de inserción de períodos para agregar datos.
                    <hr>
                    <div class="mt-2">
                        <a href="insertar_periodos_pago.php" class="btn btn-primary">Insertar Períodos</a>
                    </div>
                </div>';
            }
        } else {
            echo '<div class="alert alert-danger">
                <strong>Problema detectado:</strong> La tabla Periodos_Pago no existe.
                <hr>
                <div class="mt-2">
                    <a href="insertar_periodos_pago.php" class="btn btn-primary">Crear Tabla e Insertar Períodos</a>
                </div>
            </div>';
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error al verificar la estructura: ' . $e->getMessage() . '</div>';
    }
    
    // Verificar si las mayúsculas/minúsculas son un problema
    echo '<h4 class="mt-4">Verificando sensibilidad de mayúsculas/minúsculas:</h4>';
    
    try {
        $variants = [
            'Periodos_Pago', 
            'periodos_pago', 
            'PERIODOS_PAGO', 
            'Periodos_pago', 
            'periodos_Pago'
        ];
        
        $results = [];
        foreach ($variants as $variant) {
            $checkVariant = $db->query("SHOW TABLES LIKE '$variant'");
            $exists = ($checkVariant->rowCount() > 0);
            $results[$variant] = $exists;
        }
        
        echo '<div class="table-responsive">';
        echo '<table class="table table-sm">';
        echo '<thead><tr><th>Variante</th><th>Existe</th></tr></thead>';
        echo '<tbody>';
        foreach ($results as $variant => $exists) {
            echo '<tr>';
            echo '<td>' . $variant . '</td>';
            echo '<td>' . ($exists ? '<span class="text-success">Sí</span>' : '<span class="text-danger">No</span>') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
        
        // Si hay diferencias en los resultados, el sistema es sensible a mayúsculas/minúsculas
        $allSame = count(array_unique(array_values($results))) === 1;
        if (!$allSame) {
            echo '<div class="alert alert-warning">
                <strong>Problema detectado:</strong> El sistema de base de datos es sensible a mayúsculas/minúsculas.
                Esto puede causar problemas si el nombre de la tabla se escribe diferente en distintas partes del código.
                <hr>
                <p>Solución recomendada: Actualizar todas las consultas para usar el mismo nombre de tabla.</p>
                <div class="mt-2">
                    <a href="periodos_pago_fix_case.php" class="btn btn-primary">Corregir Nombres de Tabla</a>
                </div>
            </div>';
        } else {
            echo '<div class="alert alert-success">El sistema no es sensible a mayúsculas/minúsculas para los nombres de tablas.</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error al verificar sensibilidad de mayúsculas/minúsculas: ' . $e->getMessage() . '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error general: ' . $e->getMessage() . '</div>';
}
?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
            <a href="index.php?page=planillas/generar" class="btn btn-primary">Ir a Generar Planilla</a>
        </div>
    </div>
</body>
</html> 