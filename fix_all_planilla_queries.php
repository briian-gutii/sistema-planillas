<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Reparar Todas las Consultas de Planilla</h1>";

// Archivos donde se podrían encontrar consultas JOIN problemáticas
$archivos = [
    'pages/planillas/ver.php',
    'pages/planillas/editar.php',
    'pages/planillas/imprimir.php',
    'pages/planillas/reporte.php',
    'pages/reportes/planilla.php'
];

try {
    foreach ($archivos as $archivo) {
        echo "<h2>Revisando archivo: $archivo</h2>";
        
        if (!file_exists($archivo)) {
            echo "<p style='color: red;'>Archivo no encontrado</p>";
            continue;
        }
        
        $contenido = file_get_contents($archivo);
        
        // Buscar el patrón de consulta JOIN
        $patronConsulta = '/FROM\s+Detalle_Planilla\s+pd\s+JOIN\s+empleados\s+e\s+ON\s+pd\.id_empleado\s+=\s+e\.id_empleado/is';
        
        if (preg_match_all($patronConsulta, $contenido, $coincidencias)) {
            echo "<p style='color: orange;'>Se encontraron " . count($coincidencias[0]) . " consultas JOIN para corregir</p>";
            
            // Reemplazar JOIN con LEFT JOIN
            $contenidoModificado = preg_replace(
                $patronConsulta,
                'FROM Detalle_Planilla pd LEFT JOIN empleados e ON pd.id_empleado = e.id_empleado',
                $contenido
            );
            
            if ($contenidoModificado !== $contenido) {
                // Crear respaldo del archivo original
                $respaldo = $archivo . '.bak.' . date('Ymd_His');
                file_put_contents($respaldo, $contenido);
                
                // Guardar el archivo modificado
                file_put_contents($archivo, $contenidoModificado);
                
                echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                      ✓ Archivo actualizado exitosamente (respaldo creado: $respaldo)
                      </div>";
            } else {
                echo "<p style='color: red;'>No se pudo aplicar la corrección</p>";
            }
        } else {
            echo "<p style='color: green;'>No se encontraron consultas JOIN para corregir</p>";
        }
    }
    
    echo "<h2>Proceso Completado</h2>";
    echo "<p>Se han revisado y corregido los archivos con posibles problemas en las consultas JOIN.</p>";
    
    echo "<div style='background-color: #f8f9fa; padding: 15px; border: 1px solid #ddd; margin-top: 20px;'>";
    echo "<h3>Explicación del problema:</h3>";
    echo "<p>El problema se debía a que las consultas estaban usando JOIN (INNER JOIN) en lugar de LEFT JOIN.</p>";
    echo "<p>Cuando algunos empleados referenciados en la tabla Detalle_Planilla no existen en la tabla empleados, el JOIN regular (INNER JOIN) filtra esas filas completamente, lo que causa que no se muestren datos.</p>";
    echo "<p>Al cambiar a LEFT JOIN, se muestran todos los registros de Detalle_Planilla incluso si no tienen un empleado correspondiente.</p>";
    echo "</div>";
    
    echo "<p style='margin-top: 20px;'><a href='index.php?page=planillas/lista' class='btn btn-primary'>Ver Planillas</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
          Error: " . $e->getMessage() . "
          </div>";
}
?> 