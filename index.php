<?php
// Iniciar buffer de salida para evitar errores de headers
ob_start();

session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'recovery.php'])) {
    header('Location: login.php');
    exit;
        }
        
// Definir la página actual
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Incluir el header
include_once 'includes/header.php';

// Cargar la página solicitada
if (file_exists('pages/' . $page . '.php')) {
    include_once 'pages/' . $page . '.php';
} else {
    include_once 'pages/error.php';
}

// Incluir el footer
include_once 'includes/footer.php';

// Asegurar que el reloj se inicialice
?>
    <script>
    // Fallback para asegurar que el reloj se inicialice en el index
    $(document).ready(function() {
        if (typeof actualizarReloj === "function" && $('#reloj-tiempo-real').length > 0) {
            actualizarReloj();
        }
    });
    </script>
<?php
// Enviar todo el contenido al navegador
ob_end_flush();