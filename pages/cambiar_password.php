<?php
// Verificar si hay sesión activa
if (!isset($_SESSION['user_id'])) {
    setFlashMessage('Debe iniciar sesión para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pageTitle = 'Cambiar Contraseña';
$activeMenu = 'perfil';

// Procesar el formulario de cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // Validar datos
        $password_actual = trim($_POST['password_actual']);
        $password_nuevo = trim($_POST['password_nuevo']);
        $password_confirmar = trim($_POST['password_confirmar']);
        
        if (empty($password_actual) || empty($password_nuevo) || empty($password_confirmar)) {
            throw new Exception("Todos los campos son obligatorios.");
        }
        
        // Verificar que la nueva contraseña tenga al menos 8 caracteres
        if (strlen($password_nuevo) < 8) {
            throw new Exception("La nueva contraseña debe tener al menos 8 caracteres.");
        }
        
        // Verificar que la nueva contraseña y la confirmación coincidan
        if ($password_nuevo !== $password_confirmar) {
            throw new Exception("La nueva contraseña y la confirmación no coinciden.");
        }
        
        // Obtener la contraseña actual del usuario
        $query = "SELECT password FROM usuarios WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar que la contraseña actual sea correcta
        if (!password_verify($password_actual, $usuario['password'])) {
            throw new Exception("La contraseña actual es incorrecta.");
        }
        
        // Hashear la nueva contraseña
        $hash = password_hash($password_nuevo, PASSWORD_BCRYPT);
        
        // Actualizar la contraseña
        $query = "UPDATE usuarios SET password = :password, updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Registrar en la bitácora
            $accion = "Cambio de contraseña";
            $detalles = "Usuario cambió su contraseña";
            
            $query = "INSERT INTO bitacora (id_usuario, accion, detalles, created_at)
                     VALUES (:id_usuario, :accion, :detalles, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':detalles', $detalles);
            $stmt->execute();
            
            $db->commit();
            setFlashMessage('Contraseña actualizada correctamente', 'success');
            header('Location: ' . BASE_URL . '?page=perfil');
            exit;
        } else {
            throw new Exception("Error al actualizar la contraseña.");
        }
        
    } catch (Exception $e) {
        if (isset($db)) $db->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-key fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Actualice su contraseña de acceso al sistema</p>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actualizar Contraseña</h6>
                </div>
                <div class="card-body">
                    <form method="post" id="formCambiarPassword">
                        <div class="mb-3">
                            <label for="password_actual" class="form-label">Contraseña Actual *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_actual">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_nuevo" class="form-label">Nueva Contraseña *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_nuevo" name="password_nuevo" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_nuevo">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Debe tener al menos 8 caracteres.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirmar" name="password_confirmar" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirmar">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="progress mb-3" style="height: 10px;">
                            <div id="password-strength" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p id="password-strength-text" class="small mb-3"></p>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save fa-fw"></i> Actualizar Contraseña
                            </button>
                            <a href="<?php echo BASE_URL; ?>?page=perfil" class="btn btn-secondary">
                                <i class="fas fa-arrow-left fa-fw"></i> Volver a Mi Perfil
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recomendaciones de Seguridad</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Use una contraseña con al menos 8 caracteres.</li>
                        <li>Combine letras mayúsculas, minúsculas, números y símbolos.</li>
                        <li>Evite usar información personal como nombres o fechas.</li>
                        <li>No reutilice contraseñas que usa en otros sitios.</li>
                        <li>Cambie su contraseña regularmente para mayor seguridad.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const form = document.getElementById('formCambiarPassword');
    
    form.addEventListener('submit', function(event) {
        const passwordActual = document.getElementById('password_actual').value;
        const passwordNuevo = document.getElementById('password_nuevo').value;
        const passwordConfirmar = document.getElementById('password_confirmar').value;
        
        if (passwordActual === '' || passwordNuevo === '' || passwordConfirmar === '') {
            event.preventDefault();
            alert('Todos los campos son obligatorios.');
            return;
        }
        
        if (passwordNuevo.length < 8) {
            event.preventDefault();
            alert('La nueva contraseña debe tener al menos 8 caracteres.');
            return;
        }
        
        if (passwordNuevo !== passwordConfirmar) {
            event.preventDefault();
            alert('La nueva contraseña y la confirmación no coinciden.');
            return;
        }
    });
    
    // Mostrar/ocultar contraseña
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
    
    // Verificar fortaleza de la contraseña
    const passwordInput = document.getElementById('password_nuevo');
    const passwordStrength = document.getElementById('password-strength');
    const passwordStrengthText = document.getElementById('password-strength-text');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        let tips = [];
        
        // Longitud
        if (password.length >= 8) {
            strength += 25;
        } else {
            tips.push('Añada más caracteres (mínimo 8).');
        }
        
        // Mayúsculas y minúsculas
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
            strength += 25;
        } else {
            tips.push('Añada letras mayúsculas y minúsculas.');
        }
        
        // Números
        if (password.match(/\d/)) {
            strength += 25;
        } else {
            tips.push('Añada números.');
        }
        
        // Símbolos
        if (password.match(/[^a-zA-Z\d]/)) {
            strength += 25;
        } else {
            tips.push('Añada símbolos especiales.');
        }
        
        // Actualizar la barra de progreso
        passwordStrength.style.width = strength + '%';
        
        // Actualizar el color de la barra según la fortaleza
        if (strength <= 25) {
            passwordStrength.className = 'progress-bar bg-danger';
            passwordStrengthText.textContent = 'Contraseña débil. ' + tips.join(' ');
        } else if (strength <= 50) {
            passwordStrength.className = 'progress-bar bg-warning';
            passwordStrengthText.textContent = 'Contraseña media. ' + tips.join(' ');
        } else if (strength <= 75) {
            passwordStrength.className = 'progress-bar bg-info';
            passwordStrengthText.textContent = 'Contraseña buena. ' + tips.join(' ');
        } else {
            passwordStrength.className = 'progress-bar bg-success';
            passwordStrengthText.textContent = 'Contraseña fuerte.';
        }
    });
});
</script> 