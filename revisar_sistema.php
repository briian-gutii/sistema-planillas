<?php
// Script para revisar todo el sistema y encontrar el problema
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Análisis Completo del Sistema de Planillas</h1>";

try {
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Conectado a la base de datos</p>";
    
    // 1. Verificar todas las tablas en la base de datos
    echo "<h2>Tablas en la Base de Datos</h2>";
    $tablas = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tablas as $tabla) {
        echo "<li>{$tabla}</li>";
    }
    echo "</ul>";
    
    // 2. Examinar la planilla problemática
    $idPlanilla = 21; // La que acabamos de crear
    echo "<h2>Verificando Planilla ID: {$idPlanilla}</h2>";
    
    $planillaQuery = $db->query("SELECT * FROM Planillas WHERE id_planilla = {$idPlanilla}");
    $planilla = $planillaQuery->fetch(PDO::FETCH_ASSOC);
    
    if ($planilla) {
        echo "<h3>Datos de la Planilla:</h3>";
        echo "<pre>";
        print_r($planilla);
        echo "</pre>";
        
        // Verificar periodo asociado
        $idPeriodo = $planilla['id_periodo'];
        $periodoQuery = $db->query("SELECT * FROM periodos_pago WHERE id_periodo = {$idPeriodo}");
        $periodo = $periodoQuery->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Periodo Asociado:</h3>";
        if ($periodo) {
            echo "<pre>";
            print_r($periodo);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>⚠️ No se encontró el periodo asociado (ID: {$idPeriodo})</p>";
        }
        
        // Verificar detalles
        $detallesQuery = $db->query("SELECT COUNT(*) as total FROM Detalle_Planilla WHERE id_planilla = {$idPlanilla}");
        $totalDetalles = $detallesQuery->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "<h3>Detalles de la Planilla:</h3>";
        echo "<p>Total de detalles encontrados: <strong>{$totalDetalles}</strong></p>";
        
        if ($totalDetalles > 0) {
            // Mostrar algunos ejemplos
            $detallesEjemplos = $db->query("SELECT * FROM Detalle_Planilla WHERE id_planilla = {$idPlanilla} LIMIT 2");
            echo "<p>Ejemplos de detalles:</p>";
            echo "<pre>";
            while ($detalle = $detallesEjemplos->fetch(PDO::FETCH_ASSOC)) {
                print_r($detalle);
            }
            echo "</pre>";
        }
        
        // 3. Verificar si hay triggers o constraints afectando la visualización
        echo "<h3>Triggers de la tabla Detalle_Planilla:</h3>";
        $triggers = $db->query("SHOW TRIGGERS LIKE 'Detalle_Planilla'");
        $hasTriggers = false;
        
        echo "<pre>";
        while ($trigger = $triggers->fetch(PDO::FETCH_ASSOC)) {
            print_r($trigger);
            $hasTriggers = true;
        }
        
        if (!$hasTriggers) {
            echo "No hay triggers definidos para Detalle_Planilla";
        }
        echo "</pre>";
        
        // 4. Verificar las consultas que utiliza el sistema para mostrar planillas
        echo "<h3>Análisis de archivo ver_detalles_planilla.php:</h3>";
        $verDetallesPath = __DIR__ . '/modulos/planillas/ver_detalles_planilla.php';
        
        if (file_exists($verDetallesPath)) {
            $contenido = file_get_contents($verDetallesPath);
            
            // Buscar patrones de consultas SQL
            preg_match_all('/SELECT.*?FROM.*?Detalle_Planilla/is', $contenido, $consultas);
            
            echo "<p>Consultas encontradas en el archivo:</p>";
            echo "<pre>";
            foreach ($consultas[0] as $consulta) {
                echo htmlspecialchars(trim($consulta)) . "\n\n";
            }
            echo "</pre>";
        } else {
            echo "<p>No se encontró el archivo ver_detalles_planilla.php</p>";
            
            // Buscar en otros archivos
            echo "<p>Buscando archivos relacionados con planillas:</p>";
            $archivosEncontrados = [];
            $directorios = ['modulos/planillas', 'controllers', '.'];
            
            foreach ($directorios as $dir) {
                $path = __DIR__ . '/' . $dir;
                if (is_dir($path)) {
                    $files = scandir($path);
                    foreach ($files as $file) {
                        if (strpos($file, 'planilla') !== false && pathinfo($file, PATHINFO_EXTENSION) == 'php') {
                            $archivosEncontrados[] = $dir . '/' . $file;
                        }
                    }
                }
            }
            
            echo "<ul>";
            foreach ($archivosEncontrados as $archivo) {
                echo "<li>{$archivo}</li>";
            }
            echo "</ul>";
        }
        
        // 5. Soluciones propuestas
        echo "<h2>Soluciones Propuestas</h2>";
        
        // Verificar si falta el salario base
        echo "<h3>1. Verificar campos de Detalle_Planilla</h3>";
        $missingFields = $db->query("SELECT * FROM Detalle_Planilla WHERE id_planilla = {$idPlanilla} AND (salario_base IS NULL OR salario_total IS NULL)");
        $hasMissingFields = false;
        
        while ($row = $missingFields->fetch(PDO::FETCH_ASSOC)) {
            $hasMissingFields = true;
            echo "<p style='color: orange;'>⚠️ El detalle ID {$row['id_detalle']} tiene campos NULL que pueden estar causando problemas</p>";
        }
        
        if ($hasMissingFields) {
            echo "<p>Solución: Actualizar los registros con valores predeterminados</p>";
            echo "<a href='actualizar_detalles.php?id_planilla={$idPlanilla}' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Aplicar Corrección</a>";
        } else {
            echo "<p style='color: green;'>✓ Todos los detalles de la planilla parecen tener sus campos completos</p>";
        }
    } else {
        echo "<p style='color: red;'>⚠️ No se encontró la planilla con ID {$idPlanilla}</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 4px;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 