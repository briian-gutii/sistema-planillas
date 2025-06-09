<?php
// Include database configuration
include_once 'config/database.php';

// Get database connection
$db = getDB();

// Query to get table structure
$query = "DESCRIBE vacaciones";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display column information
echo "<h2>Estructura de la tabla 'vacaciones'</h2>";
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

// Check if there are any rows in the table
$query = "SELECT * FROM vacaciones LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "<h2>Sample record from 'vacaciones'</h2>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "<h2>No records found in 'vacaciones' table</h2>";
}
?> 