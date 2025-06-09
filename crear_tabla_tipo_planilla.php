<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Creación de la tabla tipo_planilla y relación con planillas</h1>";

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Verificar si la tabla tipo_planilla ya existe
    $tablaExiste = $db->query("SHOW TABLES LIKE 'tipo_planilla'")->rowCount() > 0;
    
    if (!$tablaExiste) {
        // Crear la tabla tipo_planilla
        $sql = "CREATE TABLE tipo_planilla (
            id_tipo_planilla INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            periodicidad ENUM('Quincenal', 'Mensual', 'Semanal') NOT NULL,
            calculo_igss BOOLEAN NOT NULL DEFAULT 1,
            calculo_isr BOOLEAN NOT NULL DEFAULT 1,
            bonificacion_incentivo BOOLEAN NOT NULL DEFAULT 1,
            activo BOOLEAN NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
        echo "<p>Tabla 'tipo_planilla' creada exitosamente.</p>";
        
        // Insertar tipos de planilla por defecto
        $tipos = [
            ['nombre' => 'Ordinaria', 'descripcion' => 'Planilla regular de pago de salarios', 'periodicidad' => 'Quincenal'],
            ['nombre' => 'Extraordinaria', 'descripcion' => 'Pagos extraordinarios como bonos o comisiones', 'periodicidad' => 'Mensual'],
            ['nombre' => 'Aguinaldo', 'descripcion' => 'Planilla de pago de aguinaldo', 'periodicidad' => 'Anual', 'calculo_igss' => 0, 'calculo_isr' => 0],
            ['nombre' => 'Bono 14', 'descripcion' => 'Planilla de pago de bono 14', 'periodicidad' => 'Anual', 'calculo_igss' => 0, 'calculo_isr' => 0],
            ['nombre' => 'Liquidación', 'descripcion' => 'Planilla para liquidación de empleados', 'periodicidad' => 'Mensual']
        ];
        
        $sql = "INSERT INTO tipo_planilla (nombre, descripcion, periodicidad, calculo_igss, calculo_isr) VALUES (:nombre, :descripcion, :periodicidad, :calculo_igss, :calculo_isr)";
        $stmt = $db->prepare($sql);
        
        foreach ($tipos as $tipo) {
            $calculo_igss = isset($tipo['calculo_igss']) ? $tipo['calculo_igss'] : 1;
            $calculo_isr = isset($tipo['calculo_isr']) ? $tipo['calculo_isr'] : 1;
            
            $stmt->bindParam(':nombre', $tipo['nombre']);
            $stmt->bindParam(':descripcion', $tipo['descripcion']);
            $stmt->bindParam(':periodicidad', $tipo['periodicidad']);
            $stmt->bindParam(':calculo_igss', $calculo_igss, PDO::PARAM_BOOL);
            $stmt->bindParam(':calculo_isr', $calculo_isr, PDO::PARAM_BOOL);
            $stmt->execute();
        }
        
        echo "<p>Datos iniciales insertados en la tabla 'tipo_planilla'.</p>";
    } else {
        echo "<p>La tabla 'tipo_planilla' ya existe en la base de datos.</p>";
    }
    
    // Verificar si existe la columna id_tipo_planilla en la tabla planillas
    $columnExists = false;
    $columnsResult = $db->query("SHOW COLUMNS FROM planillas");
    while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
        if ($column['Field'] == 'id_tipo_planilla') {
            $columnExists = true;
            break;
        }
    }
    
    if (!$columnExists) {
        // Agregar la columna id_tipo_planilla a la tabla planillas
        $sql = "ALTER TABLE planillas ADD COLUMN id_tipo_planilla INT NULL AFTER id_periodo";
        $db->exec($sql);
        
        // Crear índice para mejorar rendimiento
        $sql = "ALTER TABLE planillas ADD INDEX idx_id_tipo_planilla (id_tipo_planilla)";
        $db->exec($sql);
        
        // Agregar constraint de clave foránea
        $sql = "ALTER TABLE planillas ADD CONSTRAINT fk_planillas_tipo_planilla 
                FOREIGN KEY (id_tipo_planilla) REFERENCES tipo_planilla(id_tipo_planilla) 
                ON DELETE RESTRICT ON UPDATE CASCADE";
        $db->exec($sql);
        
        echo "<p>Columna 'id_tipo_planilla' agregada a la tabla 'planillas' con sus relaciones.</p>";
        
        // Actualizar planillas existentes con tipo por defecto (Ordinaria - ID 1)
        $sql = "UPDATE planillas SET id_tipo_planilla = 1 WHERE id_tipo_planilla IS NULL";
        $affected = $db->exec($sql);
        echo "<p>Se actualizaron $affected planillas existentes con el tipo 'Ordinaria' por defecto.</p>";
    } else {
        echo "<p>La columna 'id_tipo_planilla' ya existe en la tabla 'planillas'.</p>";
    }
    
    $db->commit();
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>
            <h3>Proceso completado exitosamente</h3>
            <p>La tabla 'tipo_planilla' y sus relaciones han sido creadas correctamente.</p>
          </div>";

} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>
            <h3>Error al crear la tabla</h3>
            <p>" . $e->getMessage() . "</p>
          </div>";
}

// Mostrar todas las tablas después de los cambios
try {
    echo "<h2>Estado actual de las tablas:</h2>";
    
    echo "<h3>Tabla: tipo_planilla</h3>";
    $stmt = $db->query("DESCRIBE tipo_planilla");
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Registros en tipo_planilla</h3>";
    $stmt = $db->query("SELECT * FROM tipo_planilla");
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>";
    foreach (array_keys($tipos[0]) as $header) {
        echo "<th>{$header}</th>";
    }
    echo "</tr>";
    
    foreach ($tipos as $tipo) {
        echo "<tr>";
        foreach ($tipo as $value) {
            echo "<td>" . (is_null($value) ? 'NULL' : $value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar estructura actualizada de la tabla planillas
    echo "<h3>Estructura actualizada de la tabla planillas</h3>";
    $stmt = $db->query("DESCRIBE planillas");
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p>Error al mostrar información de las tablas: " . $e->getMessage() . "</p>";
}
?> 