<?php
/**
 * Script para completar la estructura completa de la base de datos
 * Este script agrega las tablas restantes al esquema básico
 */

// Parámetros de conexión
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "planillasguatemala";

echo "<h1>Completar estructura de la base de datos del sistema de planillas</h1>";

try {
    // Conectar a la base de datos
    $conn = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>Conexión a la base de datos establecida correctamente</p>";
    
    // Verificar si ya existen las tablas básicas
    $tablaEmpleados = $conn->query("SHOW TABLES LIKE 'Empleados'")->rowCount();
    
    if ($tablaEmpleados == 0) {
        echo "<p style='color:red'>Error: No se encuentra la estructura básica de la base de datos. Ejecute primero el script reset_database.php</p>";
        exit;
    }
    
    echo "<p>Verificación de estructura básica: OK</p>";
    
    // Crear tablas adicionales
    
    // Tabla: Detalle_Planilla
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Detalle_Planilla (
        id_detalle INT PRIMARY KEY AUTO_INCREMENT,
        id_planilla INT NOT NULL,
        id_empleado INT NOT NULL,
        dias_trabajados DECIMAL(5,2) NOT NULL,
        salario_base DECIMAL(10,2) NOT NULL,
        bonificacion_incentivo DECIMAL(10,2) NOT NULL,
        horas_extra DECIMAL(5,2) DEFAULT 0,
        monto_horas_extra DECIMAL(10,2) DEFAULT 0,
        comisiones DECIMAL(10,2) DEFAULT 0,
        bonificaciones_adicionales DECIMAL(10,2) DEFAULT 0,
        salario_total DECIMAL(10,2) NOT NULL,
        igss_laboral DECIMAL(10,2) NOT NULL,
        isr_retenido DECIMAL(10,2) DEFAULT 0,
        otras_deducciones DECIMAL(10,2) DEFAULT 0,
        anticipos DECIMAL(10,2) DEFAULT 0,
        prestamos DECIMAL(10,2) DEFAULT 0,
        descuentos_judiciales DECIMAL(10,2) DEFAULT 0,
        total_deducciones DECIMAL(10,2) NOT NULL,
        liquido_recibir DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (id_planilla) REFERENCES Planillas(id_planilla),
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Detalle_Planilla' creada o ya existente</p>";
    
    // Tabla: Conceptos_Nomina
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Conceptos_Nomina (
        id_concepto INT PRIMARY KEY AUTO_INCREMENT,
        codigo VARCHAR(10) NOT NULL UNIQUE,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        tipo ENUM('Ingreso', 'Deducción') NOT NULL,
        afecta_igss BOOLEAN DEFAULT FALSE,
        afecta_isr BOOLEAN DEFAULT FALSE,
        formula TEXT,
        es_fijo BOOLEAN DEFAULT FALSE,
        activo BOOLEAN DEFAULT TRUE
    )");
    echo "<p>✓ Tabla 'Conceptos_Nomina' creada o ya existente</p>";
    
    // Tabla: Ausencias
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Ausencias (
        id_ausencia INT PRIMARY KEY AUTO_INCREMENT,
        id_empleado INT NOT NULL,
        tipo ENUM('Vacaciones', 'Enfermedad común', 'Suspensión IGSS', 'Licencia', 'Permiso', 'Falta injustificada') NOT NULL,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NOT NULL,
        dias INT NOT NULL,
        justificacion TEXT,
        documento_respaldo VARCHAR(200),
        aprobado_por INT,
        estado ENUM('Solicitado', 'Aprobado', 'Rechazado') NOT NULL DEFAULT 'Solicitado',
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado),
        FOREIGN KEY (aprobado_por) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Ausencias' creada o ya existente</p>";
    
    // Tabla: Horas_Extra
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Horas_Extra (
        id_horas_extra INT PRIMARY KEY AUTO_INCREMENT,
        id_empleado INT NOT NULL,
        fecha DATE NOT NULL,
        horas DECIMAL(5,2) NOT NULL,
        descripcion TEXT,
        aprobado_por INT,
        estado ENUM('Pendiente', 'Aprobado', 'Rechazado', 'Pagado') NOT NULL DEFAULT 'Pendiente',
        id_planilla INT,
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado),
        FOREIGN KEY (aprobado_por) REFERENCES Empleados(id_empleado),
        FOREIGN KEY (id_planilla) REFERENCES Planillas(id_planilla)
    )");
    echo "<p>✓ Tabla 'Horas_Extra' creada o ya existente</p>";
    
    // Tabla: Prestamos
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Prestamos (
        id_prestamo INT PRIMARY KEY AUTO_INCREMENT,
        id_empleado INT NOT NULL,
        fecha_solicitud DATE NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        plazo_meses INT NOT NULL,
        cuota DECIMAL(10,2) NOT NULL,
        motivo TEXT,
        aprobado_por INT,
        fecha_aprobacion DATE,
        estado ENUM('Solicitado', 'Aprobado', 'Rechazado', 'Pagado') NOT NULL DEFAULT 'Solicitado',
        saldo DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado),
        FOREIGN KEY (aprobado_por) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Prestamos' creada o ya existente</p>";
    
    // Tabla: Pagos_Prestamo
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Pagos_Prestamo (
        id_pago INT PRIMARY KEY AUTO_INCREMENT,
        id_prestamo INT NOT NULL,
        id_planilla INT NOT NULL,
        fecha DATE NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (id_prestamo) REFERENCES Prestamos(id_prestamo),
        FOREIGN KEY (id_planilla) REFERENCES Planillas(id_planilla)
    )");
    echo "<p>✓ Tabla 'Pagos_Prestamo' creada o ya existente</p>";
    
    // Tabla: Aguinaldo
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Aguinaldo (
        id_aguinaldo INT PRIMARY KEY AUTO_INCREMENT,
        anio INT NOT NULL,
        fecha_calculo DATE NOT NULL,
        fecha_pago DATE NOT NULL,
        usuario_genero VARCHAR(50) NOT NULL,
        usuario_aprobo VARCHAR(50),
        estado ENUM('Borrador', 'Aprobado', 'Pagado') NOT NULL DEFAULT 'Borrador'
    )");
    echo "<p>✓ Tabla 'Aguinaldo' creada o ya existente</p>";
    
    // Tabla: Detalle_Aguinaldo
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Detalle_Aguinaldo (
        id_detalle INT PRIMARY KEY AUTO_INCREMENT,
        id_aguinaldo INT NOT NULL,
        id_empleado INT NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        dias_calculados INT NOT NULL,
        observaciones TEXT,
        FOREIGN KEY (id_aguinaldo) REFERENCES Aguinaldo(id_aguinaldo),
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Detalle_Aguinaldo' creada o ya existente</p>";
    
    // Tabla: Bono14
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Bono14 (
        id_bono14 INT PRIMARY KEY AUTO_INCREMENT,
        anio INT NOT NULL,
        fecha_calculo DATE NOT NULL,
        fecha_pago DATE NOT NULL,
        usuario_genero VARCHAR(50) NOT NULL,
        usuario_aprobo VARCHAR(50),
        estado ENUM('Borrador', 'Aprobado', 'Pagado') NOT NULL DEFAULT 'Borrador'
    )");
    echo "<p>✓ Tabla 'Bono14' creada o ya existente</p>";
    
    // Tabla: Detalle_Bono14
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Detalle_Bono14 (
        id_detalle INT PRIMARY KEY AUTO_INCREMENT,
        id_bono14 INT NOT NULL,
        id_empleado INT NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        dias_calculados INT NOT NULL,
        observaciones TEXT,
        FOREIGN KEY (id_bono14) REFERENCES Bono14(id_bono14),
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Detalle_Bono14' creada o ya existente</p>";
    
    // Tabla: Vacaciones
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Vacaciones (
        id_vacaciones INT PRIMARY KEY AUTO_INCREMENT,
        id_empleado INT NOT NULL,
        periodo_inicio DATE NOT NULL,
        periodo_fin DATE NOT NULL, 
        dias_correspondientes INT NOT NULL,
        dias_gozados INT DEFAULT 0,
        dias_pendientes INT NOT NULL,
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Vacaciones' creada o ya existente</p>";
    
    // Tabla: Solicitud_Vacaciones
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Solicitud_Vacaciones (
        id_solicitud INT PRIMARY KEY AUTO_INCREMENT,
        id_empleado INT NOT NULL,
        id_vacaciones INT NOT NULL,
        fecha_solicitud DATE NOT NULL,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NOT NULL,
        dias INT NOT NULL,
        aprobado_por INT,
        fecha_aprobacion DATE,
        estado ENUM('Solicitado', 'Aprobado', 'Rechazado', 'Cancelado') NOT NULL DEFAULT 'Solicitado',
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado),
        FOREIGN KEY (id_vacaciones) REFERENCES Vacaciones(id_vacaciones),
        FOREIGN KEY (aprobado_por) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Solicitud_Vacaciones' creada o ya existente</p>";
    
    // Tabla: Configuracion_IGSS
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Configuracion_IGSS (
        id_config INT PRIMARY KEY AUTO_INCREMENT,
        porcentaje_patronal DECIMAL(5,2) NOT NULL,
        porcentaje_laboral DECIMAL(5,2) NOT NULL,
        fecha_vigencia DATE NOT NULL,
        activo BOOLEAN DEFAULT TRUE
    )");
    echo "<p>✓ Tabla 'Configuracion_IGSS' creada o ya existente</p>";
    
    // Tabla: Configuracion_ISR
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Configuracion_ISR (
        id_config INT PRIMARY KEY AUTO_INCREMENT,
        anio INT NOT NULL,
        rango_minimo DECIMAL(12,2) NOT NULL,
        rango_maximo DECIMAL(12,2),
        importe_fijo DECIMAL(12,2),
        porcentaje DECIMAL(5,2),
        fecha_vigencia DATE NOT NULL,
        activo BOOLEAN DEFAULT TRUE
    )");
    echo "<p>✓ Tabla 'Configuracion_ISR' creada o ya existente</p>";
    
    // Tabla: Libro_Salarios
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Libro_Salarios (
        id INT PRIMARY KEY AUTO_INCREMENT,
        anio INT NOT NULL,
        id_empleado INT NOT NULL,
        total_salario_ordinario DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_horas_extra DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_bonificacion_incentivo DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_otras_bonificaciones DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_comisiones DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_aguinaldo DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_bono14 DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_vacaciones DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_indemnizacion DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_ingresos DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_igss DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_isr DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_otras_deducciones DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_deducciones DECIMAL(12,2) NOT NULL DEFAULT 0,
        liquido_anual DECIMAL(12,2) NOT NULL DEFAULT 0,
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Libro_Salarios' creada o ya existente</p>";
    
    // Tabla: Bancos
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Bancos (
        id_banco INT PRIMARY KEY AUTO_INCREMENT,
        nombre VARCHAR(100) NOT NULL,
        codigo VARCHAR(10) NOT NULL UNIQUE,
        activo BOOLEAN DEFAULT TRUE
    )");
    echo "<p>✓ Tabla 'Bancos' creada o ya existente</p>";
    
    // Tabla: Historial_Salarios
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Historial_Salarios (
        id_historial INT PRIMARY KEY AUTO_INCREMENT,
        id_empleado INT NOT NULL,
        fecha_cambio DATE NOT NULL,
        salario_anterior DECIMAL(10,2) NOT NULL,
        salario_nuevo DECIMAL(10,2) NOT NULL,
        motivo VARCHAR(200),
        usuario_registro VARCHAR(50) NOT NULL,
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Historial_Salarios' creada o ya existente</p>";
    
    // Tabla: Documentos_Empleado
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Documentos_Empleado (
        id_documento INT PRIMARY KEY AUTO_INCREMENT,
        id_empleado INT NOT NULL,
        tipo_documento VARCHAR(100) NOT NULL,
        nombre_archivo VARCHAR(200) NOT NULL,
        ubicacion VARCHAR(255) NOT NULL,
        fecha_subida DATETIME NOT NULL,
        usuario_subio VARCHAR(50) NOT NULL,
        descripcion TEXT,
        FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Documentos_Empleado' creada o ya existente</p>";
    
    // Tabla: Bitacora_Sistema
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Bitacora_Sistema (
        id_bitacora INT PRIMARY KEY AUTO_INCREMENT,
        id_usuario INT NOT NULL,
        fecha_hora DATETIME NOT NULL,
        accion VARCHAR(100) NOT NULL,
        tabla_afectada VARCHAR(50),
        registro_afectado INT,
        detalles TEXT,
        direccion_ip VARCHAR(45) NOT NULL,
        FOREIGN KEY (id_usuario) REFERENCES Usuarios_Sistema(id_usuario)
    )");
    echo "<p>✓ Tabla 'Bitacora_Sistema' creada o ya existente</p>";
    
    // Creación de Índices Adicionales
    // Índices para empleados
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_empleado_dpi ON Empleados(DPI)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_empleado_nit ON Empleados(NIT)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_empleado_igss ON Empleados(numero_IGSS)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_empleado_estado ON Empleados(estado)");
    
    // Índices para contratos
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_contrato_empleado ON Contratos(id_empleado)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_contrato_puesto ON Contratos(id_puesto)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_contrato_fechas ON Contratos(fecha_inicio, fecha_fin)");
    
    // Índices para planillas
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_planilla_periodo ON Planillas(id_periodo)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_planilla_estado ON Planillas(estado)");
    
    // Índices para detalle planilla
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_detalle_planilla ON Detalle_Planilla(id_planilla)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_detalle_empleado ON Detalle_Planilla(id_empleado)");
    
    // Índices para libro salarios
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_libro_anio_empleado ON Libro_Salarios(anio, id_empleado)");
    
    echo "<p>✓ Índices adicionales creados o ya existentes</p>";
    
    // Inserción de datos iniciales para configuración del IGSS (valores actuales en Guatemala)
    $conn->exec("
    INSERT INTO Configuracion_IGSS (porcentaje_patronal, porcentaje_laboral, fecha_vigencia, activo)
    SELECT 10.67, 4.83, CURDATE(), TRUE
    WHERE NOT EXISTS (SELECT 1 FROM Configuracion_IGSS LIMIT 1)");
    echo "<p>✓ Configuración IGSS insertada o ya existente</p>";
    
    // Inserción de datos iniciales para configuración del ISR (rangos actuales en Guatemala)
    $conn->exec("
    INSERT INTO Configuracion_ISR (anio, rango_minimo, rango_maximo, importe_fijo, porcentaje, fecha_vigencia, activo)
    SELECT 2023, 0, 48000, 0, 5, CURDATE(), TRUE
    WHERE NOT EXISTS (SELECT 1 FROM Configuracion_ISR WHERE anio = 2023 AND rango_minimo = 0)");
    
    $conn->exec("
    INSERT INTO Configuracion_ISR (anio, rango_minimo, rango_maximo, importe_fijo, porcentaje, fecha_vigencia, activo)
    SELECT 2023, 48000.01, NULL, 2400, 7, CURDATE(), TRUE
    WHERE NOT EXISTS (SELECT 1 FROM Configuracion_ISR WHERE anio = 2023 AND rango_minimo = 48000.01)");
    echo "<p>✓ Configuración ISR insertada o ya existente</p>";
    
    echo "<h2 style='color:green'>✓ Estructura completa de base de datos creada correctamente</h2>";
    echo "<p>La base de datos ha sido configurada con todas las tablas necesarias para el sistema de planillas completo.</p>";
    echo "<p>Ahora puede <a href='datos_prueba.php'>generar los datos de prueba</a> o <a href='index.php'>ir al sistema</a>.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error en la configuración de la base de datos:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 