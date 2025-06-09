<?php
// Script para diagnosticar la estructura completa de la base de datos relacionada con planillas
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Diagnóstico Completo de la Base de Datos de Planillas</h1>";

try {
    // Conexión a la base de datos
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Conexión exitosa a la base de datos.</p>";

    $tablasPrincipales = ['Planillas', 'Detalle_Planilla', 'empleados'];
    $tablasPeriodosPosibles = ['periodos_pago', 'periodos_nomina', 'periodos']; // Añadir más si es necesario

    echo "<h2>Estructura de Tablas Clave:</h2>";

    // Función para mostrar la estructura de una tabla
    function mostrarEstructuraTabla($db, $nombreTabla) {
        try {
            $stmt = $db->query("DESCRIBE `{$nombreTabla}`");
            $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($columnas)) {
                echo "<p style='color: orange;'><strong>Tabla `{$nombreTabla}`:</strong> No se encontró o está vacía.</p>";
                return false;
            }

            echo "<div style='margin-bottom: 20px; padding:10px; border: 1px solid #ccc;'>";
            echo "<h3>Tabla: `{$nombreTabla}`</h3>";
            echo "<table border='1' cellspacing='0' cellpadding='5'>";
            echo "<thead><tr><th>Campo (Field)</th><th>Tipo (Type)</th><th>Nulo (Null)</th><th>Clave (Key)</th><th>Por Defecto (Default)</th><th>Extra</th></tr></thead>";
            echo "<tbody>";
            foreach ($columnas as $columna) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($columna['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($columna['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($columna['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($columna['Key']) . "</td>";
                echo "<td>" . htmlspecialchars(isset($columna['Default']) ? $columna['Default'] : 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars(isset($columna['Extra']) ? $columna['Extra'] : 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "</div>";
            return true;
        } catch (PDOException $e) {
            echo "<p style='color: red;'><strong>Error describiendo tabla `{$nombreTabla}`:</strong> " . $e->getMessage() . "</p>";
            return false;
        }
    }

    // Mostrar estructura de tablas principales
    foreach ($tablasPrincipales as $tabla) {
        mostrarEstructuraTabla($db, $tabla);
    }

    // Intentar encontrar y mostrar la tabla de periodos
    echo "<h3>Buscando Tabla de Periodos:</h3>";
    $tablaPeriodosEncontrada = false;
    foreach ($tablasPeriodosPosibles as $tabla) {
        // Primero verificar si la tabla existe
        $checkStmt = $db->query("SHOW TABLES LIKE '{$tabla}'");
        if ($checkStmt->rowCount() > 0) {
            echo "<p>Tabla de periodos encontrada: <strong>`{$tabla}`</strong>. Mostrando su estructura:</p>";
            if (mostrarEstructuraTabla($db, $tabla)) {
                $tablaPeriodosEncontrada = true;
                break; // Encontramos una, asumimos que es la correcta
            }
        }
    }
    if (!$tablaPeriodosEncontrada) {
        echo "<p style='color: orange;'>No se pudo encontrar una tabla de periodos estándar (ej: periodos_pago, periodos_nomina).</p>";
    }
    
    echo "<hr>";
    echo "<h2>Listado Completo de Tablas en la Base de Datos:</h2>";
    $todasLasTablas = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($todasLasTablas)) {
        echo "<p>No se encontraron tablas en la base de datos.</p>";
    } else {
        echo "<ul>";
        foreach ($todasLasTablas as $t) {
            echo "<li>" . htmlspecialchars($t) . "</li>";
        }
        echo "</ul>";
        echo "<p>Si ves alguna otra tabla que crees que es relevante para las planillas (además de las ya detalladas arriba), por favor indícamelo.</p>";
    }


    echo "<hr><h2>Verificación de Datos Recientes (Última Planilla Creada):</h2>";
    $idPlanillaReciente = isset($_GET['id_planilla_test']) ? intval($_GET['id_planilla_test']) : 21; // Podemos ajustar este ID o pasarlo por URL

    $stmtPlanilla = $db->prepare("SELECT * FROM Planillas WHERE id_planilla = :id_planilla");
    $stmtPlanilla->bindParam(':id_planilla', $idPlanillaReciente, PDO::PARAM_INT);
    $stmtPlanilla->execute();
    $planillaReciente = $stmtPlanilla->fetch(PDO::FETCH_ASSOC);

    if ($planillaReciente) {
        echo "<h3>Datos de la Planilla ID: {$idPlanillaReciente}</h3>";
        echo "<pre>" . htmlspecialchars(print_r($planillaReciente, true)) . "</pre>";

        $stmtDetalles = $db->prepare("SELECT * FROM Detalle_Planilla WHERE id_planilla = :id_planilla LIMIT 5");
        $stmtDetalles->bindParam(':id_planilla', $idPlanillaReciente, PDO::PARAM_INT);
        $stmtDetalles->execute();
        $detallesRecientes = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

        if ($detallesRecientes) {
            echo "<h4>Primeros 5 Detalles de la Planilla ID: {$idPlanillaReciente}</h4>";
            echo "<pre>" . htmlspecialchars(print_r($detallesRecientes, true)) . "</pre>";
        } else {
            echo "<p style='color: orange;'>La planilla ID {$idPlanillaReciente} no tiene detalles.</p>";
        }
    } else {
         echo "<p style='color: orange;'>No se encontró la planilla ID {$idPlanillaReciente} para mostrar datos de ejemplo.</p>";
    }


} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>Error de Conexión o Consulta:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr><p><strong>Instrucciones:</strong> Por favor, revisa la estructura de las tablas mostradas arriba. Fíjate bien en los nombres de las columnas (Campo/Field), especialmente en la tabla de periodos y en `Detalle_Planilla`. Comparte conmigo esta información para que pueda crear la solución definitiva.</p>";
?> 