<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Actualizar Consultas SQL de Planilla y Empleados</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // Verificar si se agregaron las columnas
    $columnsQuery = "SHOW COLUMNS FROM empleados";
    $columnsResult = $db->query($columnsQuery);
    $columns = $columnsResult->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $hasDepartamento = in_array('id_departamento', $columns);
    $hasPuesto = in_array('id_puesto', $columns);
    
    // Mostrar estado actual
    echo "<div style='padding: 10px; border: 1px solid #ccc; margin-bottom: 20px;'>";
    echo "<h2>Estado de la tabla empleados:</h2>";
    echo "<ul>";
    echo "<li>Campo id_departamento: " . ($hasDepartamento ? "<span style='color: green;'>Existe</span>" : "<span style='color: red;'>No existe</span>") . "</li>";
    echo "<li>Campo id_puesto: " . ($hasPuesto ? "<span style='color: green;'>Existe</span>" : "<span style='color: red;'>No existe</span>") . "</li>";
    echo "</ul>";
    
    if (!$hasDepartamento || !$hasPuesto) {
        echo "<p style='color: red;'>Debe agregar los campos faltantes antes de actualizar las consultas.</p>";
        echo "<p><a href='add_missing_columns.php' class='btn btn-primary'>Agregar columnas faltantes</a></p>";
        echo "</div>";
        exit;
    }
    
    echo "</div>";
    
    // Archivos a modificar
    $archivos = [
        'pages/planillas/ver.php',
        'debug_planilla.php',
        'check_planilla_data.php'
    ];
    
    // Patrones a buscar y reemplazar
    $patterns = [
        // Consulta que une con departamentos pero no existe id_departamento
        [
            'pattern' => '/JOIN\s+departamentos\s+d\s+ON\s+e\.id_departamento\s*=\s*d\.id_departamento/i',
            'replacement' => "LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento"
        ],
        // Consulta que une con puestos pero no existe id_puesto
        [
            'pattern' => '/JOIN\s+puestos\s+p\s+ON\s+e\.id_puesto\s*=\s*p\.id_puesto/i',
            'replacement' => "LEFT JOIN puestos p ON e.id_puesto = p.id_puesto"
        ]
    ];
    
    echo "<h2>Analizando archivos...</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Archivo</th><th>Estado</th><th>Acción</th></tr>";
    
    foreach ($archivos as $archivo) {
        if (file_exists($archivo)) {
            $contenido = file_get_contents($archivo);
            $contenidoOriginal = $contenido;
            $modificado = false;
            
            // Aplicar cada patrón
            foreach ($patterns as $pattern) {
                // Verificar si el patrón existe en el archivo
                if (preg_match($pattern['pattern'], $contenido)) {
                    // Reemplazar el patrón
                    $contenido = preg_replace($pattern['pattern'], $pattern['replacement'], $contenido);
                    $modificado = true;
                }
            }
            
            // Si hubo cambios, guardar el archivo
            if ($modificado && $contenido !== $contenidoOriginal) {
                file_put_contents($archivo, $contenido);
                echo "<tr>";
                echo "<td>$archivo</td>";
                echo "<td><span style='color: green;'>Modificado</span></td>";
                echo "<td><a href='javascript:void(0);' onclick='alert(\"Archivo actualizado con éxito\")' class='btn btn-sm btn-info'>Ver cambios</a></td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td>$archivo</td>";
                echo "<td>No requiere cambios</td>";
                echo "<td>-</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr>";
            echo "<td>$archivo</td>";
            echo "<td><span style='color: red;'>No encontrado</span></td>";
            echo "<td>-</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    // Comprobar la consulta principal para verla
    echo "<h2>Probar la consulta actual de planillas:</h2>";
    
    $queryTemplate = "SELECT pd.*, e.*
                     FROM Detalle_Planilla pd
                     JOIN empleados e ON pd.id_empleado = e.id_empleado
                     LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                     LEFT JOIN puestos p ON e.id_puesto = p.id_puesto
                     WHERE pd.id_planilla = :id_planilla";
    
    $id_planilla = 14; // ID de planilla de ejemplo
    
    echo "<pre>" . htmlspecialchars($queryTemplate) . "</pre>";
    
    try {
        $stmt = $db->prepare($queryTemplate);
        $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalResultados = count($resultados);
        
        if ($totalResultados > 0) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                  ✓ La consulta funciona correctamente y devuelve $totalResultados registros
                  </div>";
            
            // Mostrar el primer resultado
            echo "<h3>Muestra de datos obtenidos:</h3>";
            echo "<pre>";
            print_r($resultados[0]);
            echo "</pre>";
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                  ⚠ La consulta no devuelve resultados
                  </div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
              Error al ejecutar la consulta: " . $e->getMessage() . "
              </div>";
    }
    
    // Opciones de solución
    echo "<h2>Siguientes pasos:</h2>";
    echo "<ul>";
    echo "<li><a href='fix_planilla_query.php' class='btn btn-primary'>Verificar consulta de planilla</a></li>";
    echo "<li><a href='add_missing_columns.php' class='btn btn-success'>Gestionar departamentos y puestos</a></li>";
    echo "<li><a href='index.php?page=planillas/ver&id=14' class='btn btn-info'>Ver planilla #14</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
          Error general: " . $e->getMessage() . "
          </div>";
}
?> 