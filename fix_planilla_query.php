<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener ID de planilla o usar 14 por defecto
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : 14;
$fix_applied = isset($_GET['fix']) ? $_GET['fix'] : null;

echo "<h1>Reparación de Consulta de Planilla</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // 1. Verificar si la planilla existe
    $planillaQuery = "SELECT * FROM Planillas WHERE id_planilla = :id_planilla";
    $stmtPlanilla = $db->prepare($planillaQuery);
    $stmtPlanilla->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtPlanilla->execute();
    
    $planilla = $stmtPlanilla->fetch(PDO::FETCH_ASSOC);
    
    if (!$planilla) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
              La planilla #$id_planilla no existe
              </div>";
        exit;
    }
    
    // 2. Verificar detalles de la planilla
    $detallesQuery = "SELECT COUNT(*) FROM Detalle_Planilla WHERE id_planilla = :id_planilla";
    $stmtDetalles = $db->prepare($detallesQuery);
    $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtDetalles->execute();
    
    $detallesCount = $stmtDetalles->fetchColumn();
    
    if ($detallesCount == 0) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
              No hay detalles para esta planilla
              </div>";
        
        echo "<p>Para generar datos de prueba, use: <a href='util_fix_detalle_planilla.php?id=$id_planilla'>Generar datos de prueba</a></p>";
        exit;
    }
    
    echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
          La planilla #$id_planilla tiene $detallesCount registros de detalle
          </div>";
    
    // 3. Verificar la consulta original
    $originalQuery = "SELECT pd.*, e.*
                    FROM Detalle_Planilla pd
                    JOIN empleados e ON pd.id_empleado = e.id_empleado
                    WHERE pd.id_planilla = :id_planilla";
    
    echo "<h2>Consulta Original:</h2>";
    echo "<pre>" . htmlspecialchars($originalQuery) . "</pre>";
    
    try {
        $stmtOriginal = $db->prepare($originalQuery);
        $stmtOriginal->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
        $stmtOriginal->execute();
        $resultadosOriginal = $stmtOriginal->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($resultadosOriginal) > 0) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                  ✓ La consulta original funciona correctamente y devuelve " . count($resultadosOriginal) . " resultados
                  </div>";
                  
            echo "<p>No es necesario aplicar ninguna corrección.</p>";
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                  ⚠ La consulta original no devuelve resultados aunque hay detalles en la tabla
                  </div>";
            
            // 4. Probar soluciones
            echo "<h2>Intentando soluciones:</h2>";
            
            // Solución 1: Usar LEFT JOIN
            $solucion1 = "SELECT pd.*, e.*
                         FROM Detalle_Planilla pd
                         LEFT JOIN empleados e ON pd.id_empleado = e.id_empleado
                         WHERE pd.id_planilla = :id_planilla";
            
            echo "<h3>Solución 1: Usar LEFT JOIN</h3>";
            echo "<pre>" . htmlspecialchars($solucion1) . "</pre>";
            
            try {
                $stmtSol1 = $db->prepare($solucion1);
                $stmtSol1->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
                $stmtSol1->execute();
                $resultadosSol1 = $stmtSol1->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($resultadosSol1) > 0) {
                    echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                          ✓ Solución 1 exitosa: " . count($resultadosSol1) . " resultados
                          </div>";
                    
                    echo "<p><a href='?id=$id_planilla&fix=1' class='btn btn-success'>Aplicar Solución 1</a></p>";
                } else {
                    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                          ⚠ Solución 1 no devuelve resultados
                          </div>";
                }
            } catch (Exception $e) {
                echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                      Error en Solución 1: " . $e->getMessage() . "
                      </div>";
            }
            
            // Solución 2: Omitir JOINs
            $solucion2 = "SELECT pd.*
                         FROM Detalle_Planilla pd
                         WHERE pd.id_planilla = :id_planilla";
            
            echo "<h3>Solución 2: Omitir JOINs</h3>";
            echo "<pre>" . htmlspecialchars($solucion2) . "</pre>";
            
            try {
                $stmtSol2 = $db->prepare($solucion2);
                $stmtSol2->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
                $stmtSol2->execute();
                $resultadosSol2 = $stmtSol2->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($resultadosSol2) > 0) {
                    echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                          ✓ Solución 2 exitosa: " . count($resultadosSol2) . " resultados
                          </div>";
                    
                    echo "<p><a href='?id=$id_planilla&fix=2' class='btn btn-success'>Aplicar Solución 2</a></p>";
                } else {
                    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                          ⚠ Solución 2 no devuelve resultados
                          </div>";
                }
            } catch (Exception $e) {
                echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                      Error en Solución 2: " . $e->getMessage() . "
                      </div>";
            }
            
            // Solución 3: Consulta sub-query
            $solucion3 = "SELECT pd.*, 
                         (SELECT CONCAT(primer_nombre, ' ', primer_apellido) FROM empleados WHERE id_empleado = pd.id_empleado) as nombre_empleado
                         FROM Detalle_Planilla pd
                         WHERE pd.id_planilla = :id_planilla";
            
            echo "<h3>Solución 3: Usar Sub-query</h3>";
            echo "<pre>" . htmlspecialchars($solucion3) . "</pre>";
            
            try {
                $stmtSol3 = $db->prepare($solucion3);
                $stmtSol3->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
                $stmtSol3->execute();
                $resultadosSol3 = $stmtSol3->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($resultadosSol3) > 0) {
                    echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                          ✓ Solución 3 exitosa: " . count($resultadosSol3) . " resultados
                          </div>";
                    
                    echo "<p><a href='?id=$id_planilla&fix=3' class='btn btn-success'>Aplicar Solución 3</a></p>";
                } else {
                    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                          ⚠ Solución 3 no devuelve resultados
                          </div>";
                }
            } catch (Exception $e) {
                echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                      Error en Solución 3: " . $e->getMessage() . "
                      </div>";
            }
        }
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
              Error al ejecutar consulta original: " . $e->getMessage() . "
              </div>";
    }
    
    // Aplicar corrección si se solicitó
    if ($fix_applied) {
        $archivosPlanilla = [
            'pages/planillas/ver.php'
        ];
        
        $nuevaConsulta = '';
        
        switch ($fix_applied) {
            case '1':
                $nuevaConsulta = "SELECT pd.*, e.*
                                 FROM Detalle_Planilla pd
                                 LEFT JOIN empleados e ON pd.id_empleado = e.id_empleado
                                 WHERE pd.id_planilla = :id_planilla";
                break;
            case '2':
                $nuevaConsulta = "SELECT pd.*
                                 FROM Detalle_Planilla pd
                                 WHERE pd.id_planilla = :id_planilla";
                break;
            case '3':
                $nuevaConsulta = "SELECT pd.*, 
                                 (SELECT CONCAT(primer_nombre, ' ', primer_apellido) FROM empleados WHERE id_empleado = pd.id_empleado) as nombre_empleado
                                 FROM Detalle_Planilla pd
                                 WHERE pd.id_planilla = :id_planilla";
                break;
        }
        
        if ($nuevaConsulta) {
            echo "<h2>Aplicando corrección:</h2>";
            echo "<div style='background-color: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>";
            echo "<p>Reemplazando consulta por:</p>";
            echo "<pre>" . htmlspecialchars($nuevaConsulta) . "</pre>";
            echo "</div>";
            
            // Actualizar archivos
            foreach ($archivosPlanilla as $archivo) {
                if (file_exists($archivo)) {
                    $contenido = file_get_contents($archivo);
                    
                    // Escapar caracteres especiales para usar en expresión regular
                    $patronOriginal = preg_quote($originalQuery, '/');
                    $patronOriginal = str_replace('\s+', '\s+', $patronOriginal);
                    
                    // Realizar el reemplazo
                    $contenidoNuevo = preg_replace('/' . $patronOriginal . '/', $nuevaConsulta, $contenido);
                    
                    // Si no funcionó el reemplazo exacto, intentar un patrón más genérico
                    if ($contenido === $contenidoNuevo) {
                        $patronGenerico = '/SELECT pd\.\*, e\.\*.*?FROM Detalle_Planilla pd.*?JOIN empleados e ON pd\.id_empleado = e\.id_empleado.*?WHERE pd\.id_planilla = :id_planilla/s';
                        $contenidoNuevo = preg_replace($patronGenerico, $nuevaConsulta, $contenido);
                    }
                    
                    if ($contenido !== $contenidoNuevo) {
                        file_put_contents($archivo, $contenidoNuevo);
                        echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                              ✓ Actualizado: $archivo
                              </div>";
                    } else {
                        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                              ⚠ No se pudo actualizar: $archivo
                              </div>";
                    }
                } else {
                    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                          ⚠ Archivo no encontrado: $archivo
                          </div>";
                }
            }
            
            echo "<div style='margin-top: 20px;'>";
            echo "<p>Pruebe ahora la visualización de planillas:</p>";
            echo "<ul>";
            echo "<li><a href='index.php?page=planillas/ver&id=$id_planilla' target='_blank' class='btn btn-primary'>Ver planilla #$id_planilla</a></li>";
            echo "</ul>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
          Error general: " . $e->getMessage() . "
          </div>";
}
?> 