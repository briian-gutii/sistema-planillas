<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Registrar en bitácora si el usuario estaba logueado
if (isset($_SESSION['user_id'])) {
    registrarBitacora('Cierre de sesión', 'usuarios_sistema', $_SESSION['user_id'], 'Salida del sistema');
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: login.php');
exit;
?> 