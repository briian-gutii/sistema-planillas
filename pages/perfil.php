<?php
// Verificar si hay sesión activa
if (!isset($_SESSION['user_id'])) {
    setFlashMessage('Debe iniciar sesión para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pageTitle = 'Mi Perfil';
$activeMenu = 'perfil';

// Obtener los datos del usuario actual
$db = getDB();
$query = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar el formulario de actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Validar datos
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        
        if (empty($nombres) || empty($apellidos) || empty($email)) {
            throw new Exception("Los campos nombre, apellidos y correo electrónico son obligatorios.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del correo electrónico no es válido.");
        }
        
        // Verificar que el email no exista para otro usuario
        $query = "SELECT id FROM usuarios WHERE email = :email AND id != :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Ya existe otro usuario con este correo electrónico.");
        }
        
        // Procesar foto de perfil si se ha subido
        $foto_perfil = $usuario['foto_perfil']; // Mantener la foto actual por defecto
        
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto_perfil'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            // Validar tipo de archivo
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception("El archivo debe ser una imagen (JPG, PNG o GIF).");
            }
            
            // Validar tamaño
            if ($file['size'] > $maxSize) {
                throw new Exception("La imagen no debe exceder los 2MB.");
            }
            
            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nombreArchivo = 'usuario_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $rutaDestino = 'uploads/profiles/' . $nombreArchivo;
            
            // Crear directorio si no existe
            if (!file_exists('uploads/profiles/')) {
                mkdir('uploads/profiles/', 0777, true);
            }
            
            // Mover el archivo
            if (move_uploaded_file($file['tmp_name'], $rutaDestino)) {
                // Eliminar foto anterior si existe y no es la default
                if (!empty($foto_perfil) && $foto_perfil != 'uploads/profiles/default.png' && file_exists($foto_perfil)) {
                    unlink($foto_perfil);
                }
                
                $foto_perfil = $rutaDestino;
            } else {
                throw new Exception("Error al subir la imagen. Inténtelo de nuevo.");
            }
        }
        
        // Actualizar los datos del usuario
        $query = "UPDATE usuarios 
                  SET nombres = :nombres, apellidos = :apellidos, email = :email, 
                      telefono = :telefono, foto_perfil = :foto_perfil, updated_at = NOW() 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombres', $nombres);
        $stmt->bindParam(':apellidos', $apellidos);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':foto_perfil', $foto_perfil);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Registrar en la bitácora
            $accion = "Actualización de perfil";
            $detalles = "Usuario actualizó su información de perfil";
            
            $query = "INSERT INTO bitacora (id_usuario, accion, detalles, created_at)
                     VALUES (:id_usuario, :accion, :detalles, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':detalles', $detalles);
            $stmt->execute();
            
            // Actualizar datos en la sesión
            $_SESSION['user_name'] = $nombres . ' ' . $apellidos;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_photo'] = $foto_perfil;
            
            $db->commit();
            setFlashMessage('Perfil actualizado correctamente', 'success');
            header('Location: ' . BASE_URL . '?page=perfil');
            exit;
        } else {
            throw new Exception("Error al actualizar el perfil.");
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-user-circle fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Administre su información personal en el sistema</p>

    <div class="row">
        <!-- Formulario de Perfil -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información Personal</h6>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombres" class="form-label">Nombres *</label>
                                <input type="text" class="form-control" id="nombres" name="nombres" value="<?php echo $usuario['nombres']; ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="apellidos" class="form-label">Apellidos *</label>
                                <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?php echo $usuario['apellidos']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Correo Electrónico *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $usuario['email']; ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo $usuario['telefono']; ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="foto_perfil" class="form-label">Foto de Perfil</label>
                                <input type="file" class="form-control" id="foto_perfil" name="foto_perfil" accept="image/jpeg, image/png, image/gif">
                                <div class="form-text">Sube una imagen de hasta 2MB. Formatos permitidos: JPG, PNG, GIF.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Rol</label>
                                <input type="text" class="form-control" value="<?php echo $usuario['rol']; ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Último Acceso</label>
                                <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i:s', strtotime($usuario['last_login'])); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save fa-fw"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Información de Cuenta -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Mi Cuenta</h6>
                </div>
                <div class="card-body text-center">
                    <img src="<?php echo !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'uploads/profiles/default.png'; ?>" 
                         class="img-profile rounded-circle mb-3" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                    <h4><?php echo $usuario['nombres'] . ' ' . $usuario['apellidos']; ?></h4>
                    <p class="text-muted"><?php echo $usuario['rol']; ?></p>
                    <hr>
                    <div class="text-start">
                        <p><strong>Correo Electrónico:</strong> <?php echo $usuario['email']; ?></p>
                        <p><strong>Teléfono:</strong> <?php echo !empty($usuario['telefono']) ? $usuario['telefono'] : 'No especificado'; ?></p>
                        <p><strong>Estado:</strong> 
                            <?php if ($usuario['estado'] == 1): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="mt-3">
                        <a href="<?php echo BASE_URL; ?>?page=cambiar_password" class="btn btn-warning btn-sm">
                            <i class="fas fa-key fa-fw"></i> Cambiar Contraseña
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(event) {
        const email = document.getElementById('email').value;
        
        if (!isValidEmail(email)) {
            event.preventDefault();
            alert('El formato del correo electrónico no es válido.');
        }
    });
    
    // Función para validar email con expresión regular
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    // Previsualizar imagen seleccionada
    const fotoPerfil = document.getElementById('foto_perfil');
    
    fotoPerfil.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                document.querySelector('.img-profile').src = e.target.result;
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script> 