<?php
/**
 * Script para reiniciar la base de datos del sistema de planillas
 * Este script elimina y vuelve a crear la base de datos con su estructura
 */

// Parámetros de conexión
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "planillasguatemala";

echo "<h1>Reinicio de la base de datos del sistema de planillas</h1>";

try {
    // Conectar sin especificar base de datos
    $conn = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>Conexión al servidor MySQL establecida correctamente</p>";
    
    // Eliminar la base de datos si existe
    $conn->exec("DROP DATABASE IF EXISTS `$dbName`");
    echo "<p>✓ Base de datos anterior eliminada</p>";
    
    // Crear la base de datos
    $conn->exec("CREATE DATABASE `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✓ Nueva base de datos '$dbName' creada correctamente</p>";
    
    // Seleccionar la base de datos
    $conn->exec("USE `$dbName`");

    // Creación directa de todas las tablas para mayor control y evitar errores
    
    // Tabla: Empleados
    $conn->exec("
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
    )");
    echo "<p>✓ Tabla 'Empleados' creada</p>";

    // Tabla: Departamentos
    $conn->exec("
    CREATE TABLE Departamentos (
        id_departamento INT PRIMARY KEY AUTO_INCREMENT,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        id_responsable INT,
        FOREIGN KEY (id_responsable) REFERENCES Empleados(id_empleado)
    )");
    echo "<p>✓ Tabla 'Departamentos' creada</p>";

    // Tabla: Puestos
    $conn->exec("
    CREATE TABLE Puestos (
        id_puesto INT PRIMARY KEY AUTO_INCREMENT,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        salario_base DECIMAL(10,2) NOT NULL,
        id_departamento INT,
        FOREIGN KEY (id_departamento) REFERENCES Departamentos(id_departamento)
    )");
    echo "<p>✓ Tabla 'Puestos' creada</p>";

    // Tabla: Contratos
    $conn->exec("
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
    )");
    echo "<p>✓ Tabla 'Contratos' creada</p>";

    // Tabla: Periodos_Pago
    $conn->exec("
    CREATE TABLE Periodos_Pago (
        id_periodo INT PRIMARY KEY AUTO_INCREMENT,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NOT NULL,
        fecha_pago DATE NOT NULL,
        tipo ENUM('Quincenal', 'Mensual', 'Semanal') NOT NULL,
        estado ENUM('Abierto', 'Cerrado', 'Procesando') NOT NULL DEFAULT 'Abierto',
        anio INT NOT NULL,
        mes INT NOT NULL
    )");
    echo "<p>✓ Tabla 'Periodos_Pago' creada</p>";

    // Tabla: Planillas
    $conn->exec("
    CREATE TABLE Planillas (
        id_planilla INT PRIMARY KEY AUTO_INCREMENT,
        id_periodo INT NOT NULL,
        descripcion VARCHAR(200) NOT NULL,
        fecha_generacion DATETIME NOT NULL,
        estado ENUM('Borrador', 'Aprobada', 'Pagada') NOT NULL DEFAULT 'Borrador',
        total_bruto DECIMAL(12,2) NOT NULL DEFAULT 0,
        total_deducciones DECIMAL(12,2) NOT NULL DEFAULT 0, 
        total_neto DECIMAL(12,2) NOT NULL DEFAULT 0,
        usuario_genero VARCHAR(50) NOT NULL,
        usuario_aprobo VARCHAR(50),
        FOREIGN KEY (id_periodo) REFERENCES Periodos_Pago(id_periodo)
    )");
    echo "<p>✓ Tabla 'Planillas' creada</p>";

    // Usuarios del sistema
    $conn->exec("
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
    )");
    echo "<p>✓ Tabla 'Usuarios_Sistema' creada</p>";

    // Insertar usuario administrador predeterminado
    $passwordHash = password_hash('123456', PASSWORD_DEFAULT);
    $conn->exec("
    INSERT INTO Usuarios_Sistema (nombre_usuario, contrasena, correo, rol, fecha_creacion, estado) VALUES
    ('admin', '$passwordHash', 'admin@sistema.com', 'Administrador', NOW(), 'Activo')");
    echo "<p>✓ Usuario administrador creado</p>";
    
    echo "<h2 style='color:green'>✓ Base de datos reiniciada correctamente con tablas básicas</h2>";
    echo "<p>Se han creado las tablas básicas para iniciar. Para crear todas las tablas del esquema completo, debe ejecutar el archivo completo de esquema.</p>";
    echo "<p>Puede <a href='index.php'>ir al sistema</a> para comenzar a trabajar con las tablas básicas.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error en la configuración de la base de datos:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 