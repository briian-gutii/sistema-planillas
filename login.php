<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, ingrese su usuario y contraseña.';
    } else {
        // Buscar usuario en la base de datos
        $sql = "SELECT u.*, e.primer_nombre, e.primer_apellido 
                FROM usuarios_sistema u 
                LEFT JOIN empleados e ON u.id_empleado = e.id_empleado 
                WHERE u.nombre_usuario = :username AND u.estado = 'Activo'";
        $user = fetchRow($sql, [':username' => $username]);
        
        if ($user && password_verify($password, $user['contrasena'])) {
            // Autenticación exitosa
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['user_name'] = ($user['primer_nombre'] && $user['primer_apellido']) ? 
                                     $user['primer_nombre'] . ' ' . $user['primer_apellido'] : 
                                     $user['nombre_usuario'];
            $_SESSION['user_role'] = $user['rol'];
            $_SESSION['user_email'] = $user['correo'];
            
            // Actualizar último acceso
            $sqlUpdate = "UPDATE usuarios_sistema SET ultimo_acceso = NOW() WHERE id_usuario = :id";
            query($sqlUpdate, [':id' => $user['id_usuario']]);
            
            // Registrar en bitácora
            registrarBitacora('Inicio de sesión', 'usuarios_sistema', $user['id_usuario'], 'Ingreso al sistema');
            
            // Redirigir al dashboard
            header('Location: index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/login.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-8">
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3 class="font-weight-light my-1">
                            <i class="fas fa-file-invoice-dollar me-2"></i>
                            <?php echo SITE_NAME; ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Ingrese su usuario" required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Ingrese su contraseña" required>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i> Iniciar sesión
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3">
                        <div class="small">
                            <a href="recovery.php">¿Olvidó su contraseña?</a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4 text-muted">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo EMPRESA_NOMBRE; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 