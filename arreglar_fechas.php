<?php
require_once 'config/database.php';

echo "Script iniciado.<br>";

try {
    // Eliminar todos los periodos existentes
    $sql = "TRUNCATE TABLE periodos_nomina";
    echo "Ejecutando: " . $sql . "<br>";
    query($sql);
    
    // Insertar periodos con fechas explícitas
    $periodos = [
        [
            'fecha_inicio' => '2025-04-01',
            'fecha_fin' => '2025-04-30',
            'descripcion' => 'Periodo Mensual - Abril 2025'
        ],
        [
            'fecha_inicio' => '2025-05-01',
            'fecha_fin' => '2025-05-31',
            'descripcion' => 'Periodo Mensual - Mayo 2025'
        ],
        [
            'fecha_inicio' => '2025-06-01',
            'fecha_fin' => '2025-06-30',
            'descripcion' => 'Periodo Mensual - Junio 2025'
        ],
        [
            'fecha_inicio' => '2025-05-01',
            'fecha_fin' => '2025-05-15',
            'descripcion' => 'Primera Quincena - Mayo 2025'
        ],
        [
            'fecha_inicio' => '2025-05-16',
            'fecha_fin' => '2025-05-31',
            'descripcion' => 'Segunda Quincena - Mayo 2025'
        ]
    ];
    
    echo "Insertando " . count($periodos) . " periodos...<br>";
    foreach ($periodos as $periodo) {
        $sqlInsert = "INSERT INTO periodos_nomina (fecha_inicio, fecha_fin, descripcion, estado) 
                     VALUES (:fecha_inicio, :fecha_fin, :descripcion, 'Activo')";
        
        echo "Ejecutando: " . $sqlInsert . " con parámetros: " . json_encode($periodo) . "<br>";
        query($sqlInsert, [
            ':fecha_inicio' => $periodo['fecha_inicio'],
            ':fecha_fin' => $periodo['fecha_fin'],
            ':descripcion' => $periodo['descripcion']
        ]);
    }
    
    echo "Periodos con fechas explícitas insertados correctamente.<br>";
    
    // Verificar los datos corregidos
    $sql = "SELECT * FROM periodos_nomina ORDER BY fecha_inicio DESC";
    echo "Ejecutando: " . $sql . "<br>";
    $periodos = fetchAll($sql);
    
    echo "<br>Periodos de nómina disponibles (" . count($periodos) . "):<br>";
    foreach ($periodos as $periodo) {
        echo "ID: " . $periodo['id_periodo'] . 
             " | Fechas: " . $periodo['fecha_inicio'] . " - " . $periodo['fecha_fin'] . 
             " | Descripción: " . $periodo['descripcion'] . 
             " | Estado: " . $periodo['estado'] . "<br>";
    }
    
    // Probar la consulta específica que usa planillas/generar.php
    $sql = "SELECT p.id_periodo, CONCAT(DATE_FORMAT(p.fecha_inicio, '%d/%m/%Y'), ' - ', 
            DATE_FORMAT(p.fecha_fin, '%d/%m/%Y'), ' (', p.descripcion, ')') as periodo_texto,
            (SELECT COUNT(*) FROM planillas pl WHERE pl.id_periodo = p.id_periodo) as tiene_planilla
            FROM periodos_nomina p
            WHERE p.estado = 'Activo'
            ORDER BY p.fecha_inicio DESC 
            LIMIT 12";
    echo "Ejecutando consulta final: " . $sql . "<br>";
    $periodos = fetchAll($sql);
    
    echo "<br>Resultado de la consulta usada en planillas/generar.php (" . count($periodos) . " registros):<br>";
    echo "<pre>";
    print_r($periodos);
    echo "</pre>";
    
    echo "Script completado correctamente.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "<br>Traza: <pre>" . $e->getTraceAsString() . "</pre>";
} 