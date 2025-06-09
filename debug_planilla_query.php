<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener ID de planilla o usar el primero disponible
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : null;

echo "<h1>Depuración de Consulta de Planilla</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // Si no se proporcionó un ID, buscar la primera planilla disponible
    if (!$id_planilla) {
        $queryFirst = "SELECT id_planilla FROM Planillas ORDER BY id_planilla ASC LIMIT 1";
        $stmtFirst = $db->query($queryFirst);
        if ($stmtFirst && $stmtFirst->rowCount() > 0) {
            $id_planilla = $stmtFirst->fetchColumn();
            echo "<p>No se proporcionó ID. Usando la primera planilla disponible: #$id_planilla</p>";
        } else {
            echo "<p style='color: red;'>No hay planillas disponibles en la base de datos.</p>";
            exit;
        }
    }
    
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
    
    echo "<h2>Información de la Planilla:</h2>";
    echo "<pre>";
    print_r($planilla);
    echo "</pre>";
    
    // 2. Verificar detalles de la planilla
    echo "<h2>Verificando tabla Detalle_Planilla:</h2>";
    
    // Verificar si la tabla existe (probando diferentes variantes de mayúsculas/minúsculas)
    $variantesTabla = ['Detalle_Planilla', 'detalle_planilla', 'DETALLE_PLANILLA'];
    $tablaEncontrada = false;
    $nombreTablaReal = '';
    
    foreach ($variantesTabla as $variante) {
        $checkTable = $db->query("SHOW TABLES LIKE '$variante'");
        if ($checkTable && $checkTable->rowCount() > 0) {
            $tablaEncontrada = true;
            $nombreTablaReal = $variante;
            echo "<p style='color: green;'>✓ Tabla '$variante' encontrada</p>";
            break;
        }
    }
    
    if (!$tablaEncontrada) {
        echo "<p style='color: red;'>❌ No se encontró la tabla Detalle_Planilla (en ninguna variante de mayúsculas/minúsculas)</p>";
        exit;
    }
    
    // Contar registros para esta planilla
    $detallesQuery = "SELECT COUNT(*) FROM $nombreTablaReal WHERE id_planilla = :id_planilla";
    $stmtDetalles = $db->prepare($detallesQuery);
    $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtDetalles->execute();
    
    $detallesCount = $stmtDetalles->fetchColumn();
    
    if ($detallesCount == 0) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
              No hay detalles para esta planilla
              </div>";
        exit;
    }
    
    echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
          La planilla #$id_planilla tiene $detallesCount registros de detalle
          </div>";
    
    // 3. Probar la consulta original que no funciona
    $originalQuery = "SELECT pd.*, e.*
                    FROM $nombreTablaReal pd
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
                  ✓ La consulta original funciona y devuelve " . count($resultadosOriginal) . " resultados
                  </div>";
                  
            // Mostrar el primer resultado
            echo "<h3>Primer resultado:</h3>";
            echo "<pre>";
            print_r($resultadosOriginal[0]);
            echo "</pre>";
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                  ⚠ La consulta original no devuelve resultados aunque hay detalles en la tabla
                  </div>";
            
            // 4. Verificar si los empleados existen
            echo "<h2>Verificando empleados referenciados:</h2>";
            
            $queryEmpleadosRef = "SELECT pd.id_empleado, 
                               CASE WHEN e.id_empleado IS NOT NULL THEN 'Encontrado' ELSE 'No existe' END as estado
                               FROM $nombreTablaReal pd
                               LEFT JOIN empleados e ON pd.id_empleado = e.id_empleado
                               WHERE pd.id_planilla = :id_planilla";
            
            $stmtEmpleadosRef = $db->prepare($queryEmpleadosRef);
            $stmtEmpleadosRef->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
            $stmtEmpleadosRef->execute();
            $empleadosRef = $stmtEmpleadosRef->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID Empleado</th><th>Estado</th></tr>";
            
            $empleadosNoEncontrados = 0;
            
            foreach ($empleadosRef as $ref) {
                echo "<tr>";
                echo "<td>" . $ref['id_empleado'] . "</td>";
                echo "<td>" . $ref['estado'] . "</td>";
                echo "</tr>";
                
                if ($ref['estado'] == 'No existe') {
                    $empleadosNoEncontrados++;
                }
            }
            
            echo "</table>";
            
            if ($empleadosNoEncontrados > 0) {
                echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                      ⚠ Hay $empleadosNoEncontrados empleados referenciados que no existen en la tabla empleados
                      </div>";
                      
                echo "<p>Este es el problema principal: la consulta JOIN falla porque algunos empleados referenciados no existen</p>";
            }
            
            // 5. Probar soluciones alternativas
            echo "<h2>Probando soluciones alternativas:</h2>";
            
            // Solución 1: Usar LEFT JOIN
            $solucion1 = "SELECT pd.*, e.*
                         FROM $nombreTablaReal pd
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
                    
                    echo "<a href='fix_planilla_query.php?id=$id_planilla&fix=1' class='btn btn-success'>Aplicar Solución 1</a>";
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
        }
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
              Error al ejecutar consulta original: " . $e->getMessage() . "
              </div>";
              
        echo "<p>Mensaje de error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
          Error general: " . $e->getMessage() . "
          </div>";
}
?> 