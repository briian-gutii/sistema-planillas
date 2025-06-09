<?php
// Script para agregar el campo estado a la tabla contratos si no existe
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Añadir campo estado a la tabla contratos</h2>";

try {
    $db = getDB();
    
    // Primero verificamos si el campo ya existe
    $stmt = $db->query("SHOW COLUMNS FROM contratos LIKE 'estado'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "<p>El campo 'estado' ya existe en la tabla contratos.</p>";
    } else {
        // Agregar el campo estado (1=Activo, 0=Finalizado)
        $sql = "ALTER TABLE contratos ADD COLUMN estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Estado del contrato: 1=Activo, 0=Finalizado'";
        $db->exec($sql);
        
        // Actualizar contratos con fecha_fin diferente de NULL a estado=0
        $sql = "UPDATE contratos SET estado = 0 WHERE fecha_fin IS NOT NULL";
        $count = $db->exec($sql);
        
        echo "<p>Campo 'estado' añadido exitosamente a la tabla contratos.</p>";
        echo "<p>Se actualizaron $count contratos con fecha de finalización a estado=0 (Finalizado).</p>";
    }
    
    // Mostrar la estructura actual de la tabla
    echo "<h3>Estructura actual de la tabla contratos:</h3>";
    $stmt = $db->query("DESCRIBE contratos");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 