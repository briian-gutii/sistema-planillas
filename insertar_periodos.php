<?php
require_once 'config/database.php';

try {
    // Intentar crear la tabla si no existe correctamente
    $sql = "CREATE TABLE IF NOT EXISTS periodos_nomina (
        id_periodo INT AUTO_INCREMENT PRIMARY KEY,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NOT NULL,
        descripcion VARCHAR(100) NOT NULL,
        estado ENUM('Activo', 'Cerrado', 'Anulado') DEFAULT 'Activo',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    query($sql);
    
    // Truncar la tabla para asegurarnos de que no haya datos conflictivos
    $sql = "TRUNCATE TABLE periodos_nomina";
    query($sql);
    
    // Insertar periodos de nómina
    $periodos = [
        [
            'fecha_inicio' => date('Y-m-d', strtotime('first day of last month')),
            'fecha_fin' => date('Y-m-d', strtotime('last day of last month')),
            'descripcion' => 'Periodo Mensual - ' . date('F Y', strtotime('last month'))
        ],
        [
            'fecha_inicio' => date('Y-m-d', strtotime('first day of this month')),
            'fecha_fin' => date('Y-m-d', strtotime('last day of this month')),
            'descripcion' => 'Periodo Mensual - ' . date('F Y')
        ],
        [
            'fecha_inicio' => date('Y-m-d', strtotime('first day of next month')),
            'fecha_fin' => date('Y-m-d', strtotime('last day of next month')),
            'descripcion' => 'Periodo Mensual - ' . date('F Y', strtotime('next month'))
        ],
        // Añadir periodos quincenales para el mes actual
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
    
    // Contador para seguimiento de inserciones
    $count = 0;
    
    foreach ($periodos as $periodo) {
        $sqlInsert = "INSERT INTO periodos_nomina (fecha_inicio, fecha_fin, descripcion, estado) 
                     VALUES (:fecha_inicio, :fecha_fin, :descripcion, 'Activo')";
        
        query($sqlInsert, [
            ':fecha_inicio' => $periodo['fecha_inicio'],
            ':fecha_fin' => $periodo['fecha_fin'],
            ':descripcion' => $periodo['descripcion']
        ]);
        
        $count++;
    }
    
    echo "Se han insertado $count periodos de nómina correctamente.<br>";
    
    // Verificar los datos insertados
    $sql = "SELECT * FROM periodos_nomina ORDER BY fecha_inicio";
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