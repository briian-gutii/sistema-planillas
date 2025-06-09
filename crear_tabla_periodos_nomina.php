<?php
require_once 'config/database.php';

try {
    // Crear la tabla periodos_nomina
    $sql = "CREATE TABLE IF NOT EXISTS periodos_nomina (
        id_periodo INT AUTO_INCREMENT PRIMARY KEY,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NOT NULL,
        descripcion VARCHAR(100) NOT NULL,
        estado ENUM('Activo', 'Cerrado', 'Anulado') DEFAULT 'Activo',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        usuario_creacion INT,
        usuario_modificacion INT,
        INDEX idx_fechas (fecha_inicio, fecha_fin),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    query($sql);
    
    // Insertar periodos iniciales para pruebas
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
    
    echo "La tabla 'periodos_nomina' ha sido creada correctamente y se han insertado los periodos iniciales.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 