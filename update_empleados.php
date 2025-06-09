<?php
// Asignar id_departamento e id_puesto a todos los empleados

try {
    // Conectar a la base de datos
    $db = new PDO('mysql:host=localhost;dbname=planillasguatemala', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Actualizando empleados...</h2>";
    
    // 1. Primero verificar si existen departamentos
    $deptoQuery = "SELECT COUNT(*) FROM departamentos";
    $deptoCount = $db->query($deptoQuery)->fetchColumn();
    
    if ($deptoCount == 0) {
        // Crear un departamento si no existe ninguno
        $db->exec("INSERT INTO departamentos (nombre, descripcion, estado) VALUES ('Administración', 'Departamento Administrativo', 'Activo')");
        echo "<p>Se creó el departamento 'Administración' con ID 1</p>";
    }
    
    // 2. Verificar si existen puestos
    $puestoQuery = "SELECT COUNT(*) FROM puestos";
    $puestoCount = $db->query($puestoQuery)->fetchColumn();
    
    if ($puestoCount == 0) {
        // Crear un puesto si no existe ninguno
        $db->exec("INSERT INTO puestos (nombre, descripcion, estado) VALUES ('Asistente', 'Puesto Asistencial', 'Activo')");
        echo "<p>Se creó el puesto 'Asistente' con ID 1</p>";
    }
    
    // 3. Actualizar todos los empleados
    $updateQuery = "UPDATE empleados SET id_departamento = 1, id_puesto = 1 WHERE id_departamento IS NULL OR id_puesto IS NULL";
    $count = $db->exec($updateQuery);
    
    echo "<p>Empleados actualizados: $count</p>";
    
    // 4. Verificar los datos actualizados
    $empleadosQuery = "SELECT id_empleado, primer_nombre, primer_apellido, id_departamento, id_puesto FROM empleados";
    $empleados = $db->query($empleadosQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estado actual de los empleados:</h3>";
    echo "<table border='1' cellpadding='4'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Departamento</th><th>Puesto</th></tr>";
    
    foreach ($empleados as $emp) {
        echo "<tr>";
        echo "<td>" . $emp['id_empleado'] . "</td>";
        echo "<td>" . $emp['primer_nombre'] . " " . $emp['primer_apellido'] . "</td>";
        echo "<td>" . $emp['id_departamento'] . "</td>";
        echo "<td>" . $emp['id_puesto'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p>Todos los empleados han sido actualizados. <a href='index.php?page=planillas/ver&id=15'>Ver planilla ID 15</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<p>Error al actualizar: " . $e->getMessage() . "</p>";
}
?> 