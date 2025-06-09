<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener el ID de planilla desde la URL o usar 14 por defecto
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : 14;

echo "<h1>Diagnóstico Detallado de Planilla #$id_planilla</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // 1. Verificar si la planilla existe
    $queryPlanilla = "SELECT * FROM Planillas WHERE id_planilla = :id_planilla";
    $stmtPlanilla = $db->prepare($queryPlanilla);
    $stmtPlanilla->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtPlanilla->execute();
    
    $planilla = $stmtPlanilla->fetch(PDO::FETCH_ASSOC);
    
    if (!$planilla) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
              La planilla #$id_planilla no existe en la base de datos
              </div>";
    } else {
        echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
              ✓ La planilla #$id_planilla existe en la base de datos
              </div>";
        
        echo "<h2>Datos de la planilla:</h2>";
        echo "<pre>";
        print_r($planilla);
        echo "</pre>";
        
        // 2. Verificar si hay detalles para esta planilla
        $queryDetalles = "SELECT COUNT(*) as total FROM Detalle_Planilla WHERE id_planilla = :id_planilla";
        $stmtDetalles = $db->prepare($queryDetalles);
        $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
        $stmtDetalles->execute();
        
        $totalDetalles = $stmtDetalles->fetchColumn();
        
        if ($totalDetalles > 0) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                  ✓ La planilla tiene $totalDetalles registros de detalle asociados
                  </div>";
            
            // 3. Obtener una muestra de detalles
            $queryMuestra = "SELECT * FROM Detalle_Planilla WHERE id_planilla = :id_planilla LIMIT 3";
            $stmtMuestra = $db->prepare($queryMuestra);
            $stmtMuestra->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
            $stmtMuestra->execute();
            
            $detalles = $stmtMuestra->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h2>Muestra de detalles:</h2>";
            echo "<pre>";
            print_r($detalles);
            echo "</pre>";
            
            // 4. Probar la consulta exacta que se usa en ver.php
            echo "<h2>Prueba de la consulta completa:</h2>";
            
            $queryCompleta = "SELECT pd.*, e.*
                            FROM Detalle_Planilla pd
                            JOIN empleados e ON pd.id_empleado = e.id_empleado
                            WHERE pd.id_planilla = :id_planilla";
            
            echo "<p>Ejecutando consulta:<br><code>$queryCompleta</code></p>";
            
            try {
                $stmtCompleta = $db->prepare($queryCompleta);
                $stmtCompleta->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
                $stmtCompleta->execute();
                
                $resultados = $stmtCompleta->fetchAll(PDO::FETCH_ASSOC);
                $totalResultados = count($resultados);
                
                if ($totalResultados > 0) {
                    echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                          ✓ La consulta completa devolvió $totalResultados resultados
                          </div>";
                          
                    // Mostrar el primer resultado
                    echo "<h3>Primer registro obtenido:</h3>";
                    echo "<pre>";
                    print_r($resultados[0]);
                    echo "</pre>";
                } else {
                    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                          ⚠ La consulta completa no devolvió resultados aunque hay detalles
                          </div>";
                    
                    // 5. Verificar si el problema está en el JOIN con empleados
                    echo "<h3>Diagnóstico del problema:</h3>";
                    
                    // Verificar si los empleados referenciados existen
                    $empleadosReferenciados = array_column($detalles, 'id_empleado');
                    
                    echo "<p>IDs de empleados referenciados en detalles: " . implode(", ", $empleadosReferenciados) . "</p>";
                    
                    $placeholders = implode(',', array_fill(0, count($empleadosReferenciados), '?'));
                    $queryEmpleados = "SELECT id_empleado, primer_nombre, primer_apellido FROM empleados WHERE id_empleado IN ($placeholders)";
                    
                    $stmtEmpleados = $db->prepare($queryEmpleados);
                    foreach ($empleadosReferenciados as $i => $id) {
                        $stmtEmpleados->bindValue($i+1, $id, PDO::PARAM_INT);
                    }
                    $stmtEmpleados->execute();
                    
                    $empleadosEncontrados = $stmtEmpleados->fetchAll(PDO::FETCH_ASSOC);
                    $totalEmpleados = count($empleadosEncontrados);
                    
                    if ($totalEmpleados == count($empleadosReferenciados)) {
                        echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                              ✓ Todos los empleados referenciados existen en la base de datos
                              </div>";
                              
                        echo "<pre>";
                        print_r($empleadosEncontrados);
                        echo "</pre>";
                    } else {
                        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                              ⚠ Algunos empleados referenciados no existen en la base de datos
                              </div>";
                              
                        echo "<p>Empleados encontrados: $totalEmpleados de " . count($empleadosReferenciados) . "</p>";
                        
                        if ($totalEmpleados > 0) {
                            echo "<pre>";
                            print_r($empleadosEncontrados);
                            echo "</pre>";
                        }
                    }
                }
            } catch (Exception $e) {
                echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                      ⚠ Error en la consulta completa: " . $e->getMessage() . "
                      </div>";
            }
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                  ⚠ La planilla no tiene detalles asociados
                  </div>";
            
            echo "<p>No hay registros en la tabla Detalle_Planilla para esta planilla.</p>";
            echo "<p>Opciones disponibles:</p>";
            echo "<ul>";
            echo "<li><a href='util_fix_detalle_planilla.php?id=$id_planilla'>Generar datos de prueba para esta planilla</a></li>";
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
          Error: " . $e->getMessage() . "
          </div>";
}
?> 