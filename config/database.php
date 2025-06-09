<?php
// Configuración de conexión a la base de datos
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "planillasguatemala";

// Crear conexión PDO
try {
    $conn = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    // Configurar el modo de error para que lance excepciones
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configurar para que devuelva arrays asociativos
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Emular sentencias preparadas
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para obtener una conexión global
function getDB() {
    global $conn;
    return $conn;
}

// Función para realizar consultas seguras
function query($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Función para obtener un solo registro
function fetchRow($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch();
}

// Función para obtener múltiples registros
function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

// Función para obtener el último ID insertado
function lastInsertId() {
    return getDB()->lastInsertId();
}

// Función para iniciar una transacción
function beginTransaction() {
    return getDB()->beginTransaction();
}

// Función para confirmar una transacción
function commitTransaction() {
    return getDB()->commit();
}

// Función para revertir una transacción
function rollbackTransaction() {
    return getDB()->rollBack();
}
?> 