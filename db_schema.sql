-- Script para crear la estructura de la base de datos del sistema de planillas
-- Este script crea todas las tablas necesarias para el funcionamiento del sistema

-- Eliminar tablas si existen para reinstalar desde cero
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Bitacora_Sistema;
DROP TABLE IF EXISTS Usuarios_Sistema;
DROP TABLE IF EXISTS Documentos_Empleado;
DROP TABLE IF EXISTS Historial_Salarios;
DROP TABLE IF EXISTS Libro_Salarios;
DROP TABLE IF EXISTS Pagos_Prestamo;
DROP TABLE IF EXISTS Prestamos;
DROP TABLE IF EXISTS Detalle_Aguinaldo;
DROP TABLE IF EXISTS Aguinaldo;
DROP TABLE IF EXISTS Detalle_Bono14;
DROP TABLE IF EXISTS Bono14;
DROP TABLE IF EXISTS Solicitud_Vacaciones;
DROP TABLE IF EXISTS Vacaciones;
DROP TABLE IF EXISTS Horas_Extra;
DROP TABLE IF EXISTS Ausencias;
DROP TABLE IF EXISTS Detalle_Planilla;
DROP TABLE IF EXISTS Planillas;
DROP TABLE IF EXISTS Periodos_Pago;
DROP TABLE IF EXISTS Contratos;
DROP TABLE IF EXISTS Puestos;
DROP TABLE IF EXISTS Departamentos;
DROP TABLE IF EXISTS Empleados;
DROP TABLE IF EXISTS Bancos;
DROP TABLE IF EXISTS Conceptos_Nomina;
DROP TABLE IF EXISTS Configuracion_IGSS;
DROP TABLE IF EXISTS Configuracion_ISR;
SET FOREIGN_KEY_CHECKS = 1;

-- Crear tabla de usuarios
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    rol ENUM('Admin', 'Contabilidad', 'RRHH', 'Gerencia', 'Supervisor') NOT NULL,
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_conexion DATETIME NULL
) ENGINE=InnoDB;

-- Crear tabla de departamentos
CREATE TABLE departamentos (
    id_departamento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB;

-- Crear tabla de puestos
CREATE TABLE puestos (
    id_puesto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB;

-- Crear tabla de empleados
CREATE TABLE empleados (
    id_empleado INT AUTO_INCREMENT PRIMARY KEY,
    codigo_empleado VARCHAR(20) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    dpi VARCHAR(20) NOT NULL UNIQUE,
    fecha_nacimiento DATE NOT NULL,
    direccion TEXT NOT NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    id_departamento INT NULL,
    id_puesto INT NULL,
    estado ENUM('Activo', 'Inactivo', 'Vacaciones', 'Suspendido') NOT NULL DEFAULT 'Activo',
    fecha_ingreso DATE NULL,
    fecha_retiro DATE NULL,
    FOREIGN KEY (id_departamento) REFERENCES departamentos(id_departamento) ON DELETE SET NULL,
    FOREIGN KEY (id_puesto) REFERENCES puestos(id_puesto) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Crear tabla de contratos
CREATE TABLE contratos (
    id_contrato INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    salario_base DECIMAL(10,2) NOT NULL,
    bonificacion DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('Activo', 'Finalizado') NOT NULL DEFAULT 'Activo',
    tipo_contrato ENUM('Tiempo Completo', 'Tiempo Parcial', 'Temporal', 'Por Obra') NOT NULL,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Crear tabla de periodos
CREATE TABLE periodos (
    id_periodo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    tipo ENUM('Mensual', 'Quincenal', 'Semanal', 'Especial') NOT NULL DEFAULT 'Mensual',
    estado ENUM('Activo', 'Cerrado') NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB;

-- Insertar usuario administrador predeterminado
INSERT INTO usuarios (id_usuario, usuario, password, nombres, apellidos, email, rol, estado) VALUES
-- (1, 'admin', '$2y$10$W0/Y5EuWT1ncFMcS/ej3OegsjoiCQtMvS8Rw.n6Z0yCgG8g9AE1Gu', 'Administrador', 'Sistema', 'admin@sistema.com', 'Admin', 'Activo');
-- IMPORTANT: The following line sets a PLAIN TEXT password for the admin user.
-- This is INSECURE and the application's login system will likely NOT WORK correctly with a plain text password.
-- You MUST replace '1234556' with its BCRYPT HASH before running this script or deploying the system.
-- To generate a bcrypt hash in PHP (which appears to be the method used):
-- echo password_hash('1234556', PASSWORD_BCRYPT);
(1, 'admin', '1234556', 'Administrador', 'Sistema', 'admin@sistema.com', 'Admin', 'Activo');

-- Crear tabla de horas extra
CREATE TABLE horas_extra (
    id_hora_extra INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    fecha DATE NOT NULL,
    horas DECIMAL(5,2) NOT NULL,
    valor_hora DECIMAL(10,2) NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('Pendiente', 'Aprobado', 'Rechazado') NOT NULL DEFAULT 'Pendiente',
    fecha_registro DATETIME NOT NULL,
    registrado_por INT NOT NULL,
    fecha_aprobacion DATETIME NULL,
    aprobado_por INT NULL,
    observaciones TEXT NULL,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Crear tabla de vacaciones
CREATE TABLE vacaciones (
    id_vacacion INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    dias INT NOT NULL,
    estado ENUM('Pendiente', 'Aprobada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
    fecha_solicitud DATE NOT NULL,
    registrado_por INT NOT NULL,
    fecha_aprobacion DATE NULL,
    aprobado_por INT NULL,
    observaciones TEXT NULL,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Crear tabla de planillas
CREATE TABLE planillas (
    id_planilla INT AUTO_INCREMENT PRIMARY KEY,
    id_periodo INT NOT NULL,
    id_departamento INT NULL,
    tipo_planilla ENUM('General', 'Especial') NOT NULL DEFAULT 'General',
    estado ENUM('Borrador', 'Aprobada', 'Pagada', 'Anulada') NOT NULL DEFAULT 'Borrador',
    fecha_generacion DATE NOT NULL,
    fecha_pago DATE NULL,
    referencia_pago VARCHAR(100) NULL,
    observaciones TEXT NULL,
    usuario_id INT NOT NULL,
    FOREIGN KEY (id_periodo) REFERENCES periodos(id_periodo) ON DELETE CASCADE,
    FOREIGN KEY (id_departamento) REFERENCES departamentos(id_departamento) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Crear tabla de detalle de planilla
CREATE TABLE planilla_detalle (
    id_planilla_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_planilla INT NOT NULL,
    id_empleado INT NOT NULL,
    salario_base DECIMAL(10,2) NOT NULL,
    bonificaciones DECIMAL(10,2) NOT NULL DEFAULT 0,
    horas_extra DECIMAL(10,2) NOT NULL DEFAULT 0,
    otras_percepciones DECIMAL(10,2) NOT NULL DEFAULT 0,
    igss DECIMAL(10,2) NOT NULL DEFAULT 0,
    isr DECIMAL(10,2) NOT NULL DEFAULT 0,
    otras_deducciones DECIMAL(10,2) NOT NULL DEFAULT 0,
    salario_liquido DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_planilla) REFERENCES planillas(id_planilla) ON DELETE CASCADE,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Crear tabla de historial
CREATE TABLE historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    tipo_entidad VARCHAR(50) NOT NULL,
    id_entidad INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha DATETIME NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Tabla: Empleados
CREATE TABLE Empleados (
    id_empleado INT PRIMARY KEY AUTO_INCREMENT,
    DPI VARCHAR(13) UNIQUE NOT NULL,
    NIT VARCHAR(10) UNIQUE NOT NULL,
    numero_IGSS VARCHAR(12),
    primer_nombre VARCHAR(50) NOT NULL,
    segundo_nombre VARCHAR(50),
    primer_apellido VARCHAR(50) NOT NULL,
    segundo_apellido VARCHAR(50),
    apellido_casada VARCHAR(50),
    fecha_nacimiento DATE NOT NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro') NOT NULL,
    estado_civil ENUM('Soltero', 'Casado', 'Divorciado', 'Viudo', 'Unido') NOT NULL,
    direccion VARCHAR(200) NOT NULL,
    zona INT,
    departamento VARCHAR(50) NOT NULL,
    municipio VARCHAR(50) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    cuenta_bancaria VARCHAR(30),
    banco VARCHAR(50),
    tipo_cuenta ENUM('Ahorro', 'Monetaria'),
    contacto_emergencia VARCHAR(100),
    telefono_emergencia VARCHAR(20),
    fecha_ingreso DATE NOT NULL,
    fecha_egreso DATE,
    estado ENUM('Activo', 'Inactivo', 'Suspendido') NOT NULL DEFAULT 'Activo'
);

-- Tabla: Departamentos
CREATE TABLE Departamentos (
    id_departamento INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    id_responsable INT,
    FOREIGN KEY (id_responsable) REFERENCES Empleados(id_empleado)
);

-- Tabla: Puestos
CREATE TABLE Puestos (
    id_puesto INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    salario_base DECIMAL(10,2) NOT NULL,
    id_departamento INT,
    FOREIGN KEY (id_departamento) REFERENCES Departamentos(id_departamento)
);

-- Tabla: Contratos
CREATE TABLE Contratos (
    id_contrato INT PRIMARY KEY AUTO_INCREMENT,
    id_empleado INT NOT NULL,
    id_puesto INT NOT NULL,
    tipo_contrato ENUM('Indefinido', 'Plazo fijo', 'Por obra') NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    salario DECIMAL(10,2) NOT NULL,
    jornada ENUM('Diurna', 'Mixta', 'Nocturna') NOT NULL,
    horas_semanales INT NOT NULL,
    bonificacion_incentivo DECIMAL(10,2) NOT NULL DEFAULT 250.00,
    observaciones TEXT,
    FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado),
    FOREIGN KEY (id_puesto) REFERENCES Puestos(id_puesto)
);

-- Tabla: Periodos_Pago
CREATE TABLE Periodos_Pago (
    id_periodo INT PRIMARY KEY AUTO_INCREMENT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    fecha_pago DATE NOT NULL,
    tipo ENUM('Quincenal', 'Mensual', 'Semanal') NOT NULL,
    estado ENUM('Abierto', 'Cerrado', 'Procesando') NOT NULL DEFAULT 'Abierto',
    anio INT NOT NULL,
    mes INT NOT NULL
);

-- Tabla: Planillas
CREATE TABLE Planillas (
    id_planilla INT PRIMARY KEY AUTO_INCREMENT,
    id_periodo INT NOT NULL,
    id_departamento INT NULL,
    tipo_planilla ENUM('General', 'Especial') NOT NULL DEFAULT 'General',
    descripcion VARCHAR(200) NOT NULL,
    fecha_generacion DATETIME NOT NULL,
    estado ENUM('Borrador', 'Aprobada', 'Pagada') NOT NULL DEFAULT 'Borrador',
    total_bruto DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_deducciones DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_neto DECIMAL(12,2) NOT NULL DEFAULT 0,
    usuario_genero VARCHAR(50) NOT NULL,
    usuario_aprobo VARCHAR(50),
    FOREIGN KEY (id_periodo) REFERENCES Periodos_Pago(id_periodo),
    FOREIGN KEY (id_departamento) REFERENCES Departamentos(id_departamento) ON DELETE SET NULL
);

-- Tabla: Detalle_Planilla
CREATE TABLE Detalle_Planilla (
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
);

-- Tabla: Conceptos_Nomina
CREATE TABLE Conceptos_Nomina (
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
);

-- Tabla: Ausencias
CREATE TABLE Ausencias (
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
);

-- Tabla: Horas_Extra
CREATE TABLE Horas_Extra (
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
);

-- Tabla: Prestamos
CREATE TABLE Prestamos (
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
);

-- Tabla: Pagos_Prestamo
CREATE TABLE Pagos_Prestamo (
    id_pago INT PRIMARY KEY AUTO_INCREMENT,
    id_prestamo INT NOT NULL,
    id_planilla INT NOT NULL,
    fecha DATE NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_prestamo) REFERENCES Prestamos(id_prestamo),
    FOREIGN KEY (id_planilla) REFERENCES Planillas(id_planilla)
);

-- Tabla: Aguinaldo
CREATE TABLE Aguinaldo (
    id_aguinaldo INT PRIMARY KEY AUTO_INCREMENT,
    anio INT NOT NULL,
    fecha_calculo DATE NOT NULL,
    fecha_pago DATE NOT NULL,
    usuario_genero VARCHAR(50) NOT NULL,
    usuario_aprobo VARCHAR(50),
    estado ENUM('Borrador', 'Aprobado', 'Pagado') NOT NULL DEFAULT 'Borrador'
);

-- Tabla: Detalle_Aguinaldo
CREATE TABLE Detalle_Aguinaldo (
    id_detalle INT PRIMARY KEY AUTO_INCREMENT,
    id_aguinaldo INT NOT NULL,
    id_empleado INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    dias_calculados INT NOT NULL,
    observaciones TEXT,
    FOREIGN KEY (id_aguinaldo) REFERENCES Aguinaldo(id_aguinaldo),
    FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
);

-- Tabla: Bono14
CREATE TABLE Bono14 (
    id_bono14 INT PRIMARY KEY AUTO_INCREMENT,
    anio INT NOT NULL,
    fecha_calculo DATE NOT NULL,
    fecha_pago DATE NOT NULL,
    usuario_genero VARCHAR(50) NOT NULL,
    usuario_aprobo VARCHAR(50),
    estado ENUM('Borrador', 'Aprobado', 'Pagado') NOT NULL DEFAULT 'Borrador'
);

-- Tabla: Detalle_Bono14
CREATE TABLE Detalle_Bono14 (
    id_detalle INT PRIMARY KEY AUTO_INCREMENT,
    id_bono14 INT NOT NULL,
    id_empleado INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    dias_calculados INT NOT NULL,
    observaciones TEXT,
    FOREIGN KEY (id_bono14) REFERENCES Bono14(id_bono14),
    FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
);

-- Tabla: Vacaciones
CREATE TABLE Vacaciones (
    id_vacaciones INT PRIMARY KEY AUTO_INCREMENT,
    id_empleado INT NOT NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fin DATE NOT NULL, 
    dias_correspondientes INT NOT NULL,
    dias_gozados INT DEFAULT 0,
    dias_pendientes INT NOT NULL,
    FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
);

-- Tabla: Solicitud_Vacaciones
CREATE TABLE Solicitud_Vacaciones (
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
);

-- Tabla: Configuracion_IGSS
CREATE TABLE Configuracion_IGSS (
    id_config INT PRIMARY KEY AUTO_INCREMENT,
    porcentaje_patronal DECIMAL(5,2) NOT NULL,
    porcentaje_laboral DECIMAL(5,2) NOT NULL,
    fecha_vigencia DATE NOT NULL,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla: Configuracion_ISR
CREATE TABLE Configuracion_ISR (
    id_config INT PRIMARY KEY AUTO_INCREMENT,
    anio INT NOT NULL,
    rango_minimo DECIMAL(12,2) NOT NULL,
    rango_maximo DECIMAL(12,2),
    importe_fijo DECIMAL(12,2),
    porcentaje DECIMAL(5,2),
    fecha_vigencia DATE NOT NULL,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla: Libro_Salarios
CREATE TABLE Libro_Salarios (
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
);

-- Tabla: Bancos
CREATE TABLE Bancos (
    id_banco INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla: Historial_Salarios
CREATE TABLE Historial_Salarios (
    id_historial INT PRIMARY KEY AUTO_INCREMENT,
    id_empleado INT NOT NULL,
    fecha_cambio DATE NOT NULL,
    salario_anterior DECIMAL(10,2) NOT NULL,
    salario_nuevo DECIMAL(10,2) NOT NULL,
    motivo VARCHAR(200),
    usuario_registro VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
);

-- Tabla: Documentos_Empleado
CREATE TABLE Documentos_Empleado (
    id_documento INT PRIMARY KEY AUTO_INCREMENT,
    id_empleado INT NOT NULL,
    tipo_documento VARCHAR(100) NOT NULL,
    nombre_archivo VARCHAR(200) NOT NULL,
    ubicacion VARCHAR(255) NOT NULL,
    fecha_subida DATETIME NOT NULL,
    usuario_subio VARCHAR(50) NOT NULL,
    descripcion TEXT,
    FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
);

-- Tabla: Usuarios_Sistema
CREATE TABLE Usuarios_Sistema (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    id_empleado INT,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    rol ENUM('Administrador', 'RRHH', 'Contabilidad', 'Gerencia', 'Consulta') NOT NULL,
    fecha_creacion DATETIME NOT NULL,
    ultimo_acceso DATETIME,
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
);

-- Tabla: Bitacora_Sistema
CREATE TABLE Bitacora_Sistema (
    id_bitacora INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50),
    registro_afectado INT,
    detalles TEXT,
    direccion_ip VARCHAR(45) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios_Sistema(id_usuario)
);

-- Creación de Índices Adicionales
-- Índices para empleados
CREATE INDEX idx_empleado_dpi ON Empleados(DPI);
CREATE INDEX idx_empleado_nit ON Empleados(NIT);
CREATE INDEX idx_empleado_igss ON Empleados(numero_IGSS);
CREATE INDEX idx_empleado_estado ON Empleados(estado);

-- Índices para contratos
CREATE INDEX idx_contrato_empleado ON Contratos(id_empleado);
CREATE INDEX idx_contrato_puesto ON Contratos(id_puesto);
CREATE INDEX idx_contrato_fechas ON Contratos(fecha_inicio, fecha_fin);

-- Índices para planillas
CREATE INDEX idx_planilla_periodo ON Planillas(id_periodo);
CREATE INDEX idx_planilla_estado ON Planillas(estado);

-- Índices para detalle planilla
CREATE INDEX idx_detalle_planilla ON Detalle_Planilla(id_planilla);
CREATE INDEX idx_detalle_empleado ON Detalle_Planilla(id_empleado);

-- Índices para libro salarios
CREATE INDEX idx_libro_anio_empleado ON Libro_Salarios(anio, id_empleado);

-- Inserción de datos iniciales para configuración del IGSS (valores actuales en Guatemala)
INSERT INTO Configuracion_IGSS (porcentaje_patronal, porcentaje_laboral, fecha_vigencia, activo)
VALUES (10.67, 4.83, CURDATE(), TRUE);

-- Inserción de datos iniciales para configuración del ISR (rangos actuales en Guatemala)
INSERT INTO Configuracion_ISR (anio, rango_minimo, rango_maximo, importe_fijo, porcentaje, fecha_vigencia, activo)
VALUES 
(2025, 0, 48000, 0, 5, CURDATE(), TRUE),
(2025, 48000.01, NULL, 2400, 7, CURDATE(), TRUE);

-- DATOS DE PRUEBA PARA AUSENCIAS Y VACACIONES --
-- Asumir que existen Empleados con id_empleado 1, 2, 3

-- Ausencias de Prueba --
INSERT INTO Ausencias (id_empleado, tipo, fecha_inicio, fecha_fin, dias, justificacion, aprobado_por, estado) VALUES
(1, 'Enfermedad común', '2025-06-10', '2025-06-11', 2, 'Gripe y fiebre', 2, 'Aprobado'),
(1, 'Permiso', '2025-07-01', '2025-07-01', 1, 'Cita médica personal', NULL, 'Solicitado'),
(3, 'Falta injustificada', '2025-07-15', '2025-07-15', 1, 'No se presentó a laborar sin previo aviso.', NULL, 'Aprobado');

-- Vacaciones de Prueba (Saldo y Solicitudes) --
-- Primero, el registro de saldo para el empleado 1
INSERT INTO Vacaciones (id_empleado, periodo_inicio, periodo_fin, dias_correspondientes, dias_gozados, dias_pendientes) VALUES
(1, '2024-01-01', '2024-12-31', 15, 0, 15);

-- Luego, solicitudes basadas en ese saldo (id_vacaciones se autoincrementa, asumimos que el anterior es 1 si es el primer registro)
-- Para que esto funcione correctamente, el id_vacaciones debe ser el correcto del registro de saldo.
-- Si la tabla Vacaciones está vacía antes de esta inserción, el primer id_vacaciones será 1.
INSERT INTO Solicitud_Vacaciones (id_empleado, id_vacaciones, fecha_solicitud, fecha_inicio, fecha_fin, dias, aprobado_por, fecha_aprobacion, estado) VALUES
(1, LAST_INSERT_ID(), '2025-08-01', '2025-08-18', '2025-08-22', 5, 2, '2025-08-02', 'Aprobado'),
(1, LAST_INSERT_ID(), '2025-09-01', '2025-12-22', '2025-12-26', 5, NULL, NULL, 'Solicitado');

-- Actualizar saldo de vacaciones para el empleado 1 después de la solicitud aprobada (5 días)
-- Esto es un ejemplo, la lógica de actualización de saldo real debería estar en la aplicación.
UPDATE Vacaciones SET dias_gozados = 5, dias_pendientes = 10 WHERE id_empleado = 1 AND id_vacaciones = LAST_INSERT_ID(); 