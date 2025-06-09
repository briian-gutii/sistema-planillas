<?php
// Include database configuration
include_once 'config/database.php';

// Get database connection
$db = getDB();

// Query to get table structure
$query = "DESCRIBE puestos";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display column information
echo "<h2>Estructura de la tabla 'puestos'</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>" . $column['Field'] . "</td>";
    echo "<td>" . $column['Type'] . "</td>";
    echo "<td>" . $column['Null'] . "</td>";
    echo "<td>" . $column['Key'] . "</td>";
    echo "<td>" . $column['Default'] . "</td>";
    echo "<td>" . $column['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";
?> 