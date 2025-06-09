<?php
require_once 'config/database.php';

try {
    // Crear tabla planillas
    $sql = "CREATE TABLE IF NOT EXISTS planillas (
        id_planilla INT AUTO_INCREMENT PRIMARY KEY,
        id_periodo INT NOT NULL,
        id_departamento INT NULL,
        descripcion VARCHAR(100) NOT NULL,
        fecha_generacion DATE,
        estado ENUM('Borrador', 'Revisada', 'Procesada', 'Anulada') DEFAULT 'Borrador',
        incluir_bonos TINYINT(1) DEFAULT 1,
        incluir_horas_extra TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        usuario_creacion INT,
        usuario_modificacion INT,
        INDEX idx_periodo (id_periodo),
        INDEX idx_departamento (id_departamento),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    query($sql);
    echo "La tabla 'planillas' ha sido creada correctamente.<br>";
    
    // Crear tabla horas_extra
    $sql = "CREATE TABLE IF NOT EXISTS horas_extra (
        id_hora_extra INT AUTO_INCREMENT PRIMARY KEY,
        id_empleado INT NOT NULL,
        id_periodo INT NOT NULL,
        fecha DATE NOT NULL,
        cantidad DECIMAL(10,2) NOT NULL,
        valor_hora DECIMAL(10,2) NOT NULL,
        descripcion TEXT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        usuario_creacion INT,
        usuario_modificacion INT,
        INDEX idx_empleado (id_empleado),
        INDEX idx_periodo (id_periodo),
        INDEX idx_fecha (fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    query($sql);
    echo "La tabla 'horas_extra' ha sido creada correctamente.<br>";
    
    // Crear tabla departamentos si no existe
    $sql = "CREATE TABLE IF NOT EXISTS departamentos (
        id_departamento INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        usuario_creacion INT,
        usuario_modificacion INT,
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    query($sql);
    
    // Insertar departamentos básicos
    $departamentos = ['Administración', 'Recursos Humanos', 'Contabilidad', 'Ventas', 'Producción'];
    
    foreach ($departamentos as $dep) {
        $sqlCheck = "SELECT id_departamento FROM departamentos WHERE nombre = :nombre";
        $existe = fetchRow($sqlCheck, [':nombre' => $dep]);
        
        if (!$existe) {
            $sqlInsert = "INSERT INTO departamentos (nombre) VALUES (:nombre)";
            query($sqlInsert, [':nombre' => $dep]);
        }
    }
    
    echo "La tabla 'departamentos' ha sido creada y poblada correctamente.<br>";
    
    // Crear tabla puestos si no existe
    $sql = "CREATE TABLE IF NOT EXISTS puestos (
        id_puesto INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        id_departamento INT,
        estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        usuario_creacion INT,
        usuario_modificacion INT,
        INDEX idx_departamento (id_departamento),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    query($sql);
    
    // Insertar algunos puestos básicos
    $puestos = [
        'Gerente General' => 1,
        'Gerente de RRHH' => 2,
        'Contador' => 3,
        'Vendedor' => 4,
        'Operario' => 5
    ];
    
    foreach ($puestos as $puesto => $dep_id) {
        $sqlCheck = "SELECT id_puesto FROM puestos WHERE nombre = :nombre";
        $existe = fetchRow($sqlCheck, [':nombre' => $puesto]);
        
        if (!$existe) {
            $sqlInsert = "INSERT INTO puestos (nombre, id_departamento) VALUES (:nombre, :id_departamento)";
            query($sqlInsert, [':nombre' => $puesto, ':id_departamento' => $dep_id]);
        }
    }
    
    echo "La tabla 'puestos' ha sido creada y poblada correctamente.<br>";
    
    // Crear tabla empleados si no existe
    $sql = "CREATE TABLE IF NOT EXISTS empleados (
        id_empleado INT AUTO_INCREMENT PRIMARY KEY,
        codigo_empleado VARCHAR(20) NOT NULL,
        primer_nombre VARCHAR(50) NOT NULL,
        segundo_nombre VARCHAR(50),
        primer_apellido VARCHAR(50) NOT NULL,
        segundo_apellido VARCHAR(50),
        dpi VARCHAR(20),
        nit VARCHAR(20),
        fecha_nacimiento DATE,
        direccion TEXT,
        telefono VARCHAR(20),
        email VARCHAR(100),
        id_departamento INT NOT NULL,
        id_puesto INT NOT NULL,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE,
        salario_base DECIMAL(10,2) NOT NULL,
        estado ENUM('Activo', 'Inactivo', 'Retirado') DEFAULT 'Activo',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        usuario_creacion INT,
        usuario_modificacion INT,
        UNIQUE KEY uk_codigo (codigo_empleado),
        INDEX idx_departamento (id_departamento),
        INDEX idx_puesto (id_puesto),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    query($sql);
    
    // Insertar un empleado de ejemplo si no existe ninguno
    $sqlCheck = "SELECT COUNT(*) as total FROM empleados";
    $count = fetchRow($sqlCheck);
    
    if ($count['total'] == 0) {
        $sqlInsert = "INSERT INTO empleados (
            codigo_empleado, primer_nombre, segundo_nombre, primer_apellido, 
            segundo_apellido, dpi, nit, fecha_nacimiento, direccion, telefono, 
            email, id_departamento, id_puesto, fecha_inicio, salario_base
        ) VALUES (
            'EMP001', 'Juan', 'Carlos', 'Pérez', 'López', 
            '1234567890101', '12345678', '1990-01-01', 'Ciudad de Guatemala', '55551234', 
            'jperez@example.com', 1, 1, '2023-01-01', 5000.00
        )";
        query($sqlInsert);
        echo "Se ha creado un empleado de ejemplo.<br>";
    }
    
    echo "La tabla 'empleados' ha sido creada correctamente.<br>";
    
    echo "<br>Todas las tablas necesarias han sido creadas correctamente.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 