<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Corregir Nombre de Tabla en Consultas</h1>";

// Verificar las opciones posibles
$tablaOriginal = 'Detalle_Planilla';
$posiblesTablas = ['detalle_planilla', 'planilla_detalle', 'detalleplanilla'];
$tablaCorrecta = null;

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    echo "<h2>Verificando tablas de detalle:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Nombre de Tabla</th><th>¿Existe?</th><th>Acción</th></tr>";
    
    // Verificar tabla original primero
    $checkOriginal = $db->query("SHOW TABLES LIKE '$tablaOriginal'");
    $existeOriginal = ($checkOriginal && $checkOriginal->rowCount() > 0);
    
    echo "<tr>";
    echo "<td>$tablaOriginal (Original)</td>";
    echo "<td>" . ($existeOriginal ? "<span style='color: green;'>Sí</span>" : "<span style='color: red;'>No</span>") . "</td>";
    echo "<td>" . ($existeOriginal ? "Ninguna - La tabla ya existe" : "Se debe corregir el nombre en las consultas") . "</td>";
    echo "</tr>";
    
    // Si la tabla original no existe, verificar posibles alternativas
    if (!$existeOriginal) {
        foreach ($posiblesTablas as $tabla) {
            $checkTabla = $db->query("SHOW TABLES LIKE '$tabla'");
            $existe = ($checkTabla && $checkTabla->rowCount() > 0);
            
            echo "<tr>";
            echo "<td>$tabla</td>";
            echo "<td>" . ($existe ? "<span style='color: green;'>Sí</span>" : "<span style='color: red;'>No</span>") . "</td>";
            echo "<td>";
            
            if ($existe) {
                echo "<a href='?actualizar=1&tabla=$tabla' class='btn btn-sm btn-primary'>Usar esta tabla</a>";
                $tablaCorrecta = $tabla;
            } else {
                echo "No se puede usar";
            }
            
            echo "</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    // Si se solicitó actualizar la tabla
    if (isset($_GET['actualizar']) && isset($_GET['tabla'])) {
        $nuevaTabla = $_GET['tabla'];
        $checkNueva = $db->query("SHOW TABLES LIKE '$nuevaTabla'");
        
        if ($checkNueva && $checkNueva->rowCount() > 0) {
            // Listar los archivos a modificar
            $archivos = [
                'pages/planillas/ver.php',
                'debug_planilla.php',
                'util_fix_detalle_planilla.php'
            ];
            
            echo "<h2>Actualizando archivos:</h2>";
            echo "<ul>";
            
            foreach ($archivos as $archivo) {
                if (file_exists($archivo)) {
                    $contenido = file_get_contents($archivo);
                    $contenidoNuevo = str_replace("Detalle_Planilla", $nuevaTabla, $contenido);
                    
                    if ($contenido != $contenidoNuevo) {
                        file_put_contents($archivo, $contenidoNuevo);
                        echo "<li>✓ <strong>$archivo</strong>: Actualizado correctamente</li>";
                    } else {
                        echo "<li>□ <strong>$archivo</strong>: No requiere cambios</li>";
                    }
                } else {
                    echo "<li>⚠️ <strong>$archivo</strong>: No existe</li>";
                }
            }
            
            echo "</ul>";
            
            echo "<div class='alert alert-success'>
                  <p>La actualización se ha completado. Ahora las consultas utilizarán la tabla <strong>$nuevaTabla</strong>
                  en lugar de <strong>Detalle_Planilla</strong>.</p>
                  <p>Pruebe la visualización de planillas:</p>
                  <ul>
                    <li><a href='index.php?page=planillas/ver&id=14' class='btn btn-primary'>Ver planilla #14</a></li>
                    <li><a href='debug_planilla.php?id=14' class='btn btn-info'>Diagnóstico de planilla #14</a></li>
                  </ul>
                  </div>";
        } else {
            echo "<div class='alert alert-danger'>La tabla '$nuevaTabla' no existe en la base de datos.</div>";
        }
    }
    
    // Si no se encontró la tabla original pero sí una alternativa, mostrar instrucciones
    if (!$existeOriginal && $tablaCorrecta) {
        echo "<div class='alert alert-warning'>
              <p>La tabla <strong>Detalle_Planilla</strong> no existe, pero se encontró una alternativa: <strong>$tablaCorrecta</strong></p>
              <p>Haga clic en 'Usar esta tabla' para actualizar las consultas en los archivos del sistema.</p>
              </div>";
    }
    
    // Si no existe ninguna tabla de detalle, ofrecer crearla
    if (!$existeOriginal && !$tablaCorrecta) {
        echo "<div class='alert alert-danger'>
              <p>No se encontró ninguna tabla de detalle de planilla en la base de datos.</p>
              <p>Es necesario crear la tabla:</p>
              <a href='util_fix_detalle_planilla.php' class='btn btn-success'>Crear tabla y datos de prueba</a>
              </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?> 