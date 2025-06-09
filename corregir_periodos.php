<?php
require_once 'config/database.php';

try {
    // Eliminar periodos incorrectos
    $sql = "DELETE FROM periodos_nomina WHERE id_periodo IN (4, 5)";
    query($sql);
    
    // Insertar periodos quincenales correctos
    $periodos = [
        [
            'fecha_inicio' => date('Y-m-d', strtotime('first day of this month')),
            'fecha_fin' => date('Y-m-d', strtotime('15th day of this month')),
            'descripcion' => 'Primera Quincena - ' . date('F Y')
        ],
        [
            'fecha_inicio' => date('Y-m-d', strtotime('16th day of this month')),
            'fecha_fin' => date('Y-m-d', strtotime('last day of this month')),
            'descripcion' => 'Segunda Quincena - ' . date('F Y')
        ]
    ];
    
    foreach ($periodos as $periodo) {
        $sqlInsert = "INSERT INTO periodos_nomina (fecha_inicio, fecha_fin, descripcion, estado) 
                     VALUES (:fecha_inicio, :fecha_fin, :descripcion, 'Activo')";
        
        query($sqlInsert, [
            ':fecha_inicio' => $periodo['fecha_inicio'],
            ':fecha_fin' => $periodo['fecha_fin'],
            ':descripcion' => $periodo['descripcion']
        ]);
    }
    
    echo "Periodos quincenales corregidos.<br>";
    
    // Verificar los datos corregidos
    $sql = "SELECT * FROM periodos_nomina ORDER BY fecha_inicio DESC";
    $periodos = fetchAll($sql);
    
    echo "<br>Periodos de nómina disponibles:<br>";
    foreach ($periodos as $periodo) {
        echo "ID: " . $periodo['id_periodo'] . 
             " | Fechas: " . $periodo['fecha_inicio'] . " - " . $periodo['fecha_fin'] . 
             " | Descripción: " . $periodo['descripcion'] . 
             " | Estado: " . $periodo['estado'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 