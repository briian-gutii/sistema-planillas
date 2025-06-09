<?php
// Verificar permisos
if (!hasPermission(ROL_ADMIN)) {
    setFlashMessage('danger', ERROR_ACCESS);
    header('Location: index.php');
    exit;
}

// Procesar formulario de creación/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $correo = trim($_POST['correo']);
    $id_empleado = !empty($_POST['id_empleado']) ? intval($_POST['id_empleado']) : null;
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];
    $password = trim($_POST['password'] ?? '');
    
    // Validar datos
    $errores = [];
    
    if (empty($nombre_usuario)) {
        $errores[] = 'El nombre de usuario es requerido';
    }
    
    if (empty($correo)) {
        $errores[] = 'El correo electrónico es requerido';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido';
    }
    
    // Verificar si el usuario ya existe
    $sql = "SELECT id_usuario FROM usuarios_sistema WHERE nombre_usuario = :nombre_usuario AND id_usuario != :id_usuario";
    $usuarioExistente = fetchRow($sql, [':nombre_usuario' => $nombre_usuario, ':id_usuario' => $id_usuario]);
    
    if ($usuarioExistente) {
        $errores[] = 'El nombre de usuario ya está en uso';
    }
    
    // Si no hay errores, proceder con la creación/actualización
    if (empty($errores)) {
        try {
            beginTransaction();
            
            if ($id_usuario > 0) {
                // Actualizar usuario existente
                $sql = "UPDATE usuarios_sistema SET 
                        nombre_usuario = :nombre_usuario, 
                        correo = :correo, 
                        id_empleado = :id_empleado, 
                        rol = :rol, 
                        estado = :estado";
                
                $params = [
                    ':id_usuario' => $id_usuario,
                    ':nombre_usuario' => $nombre_usuario,
                    ':correo' => $correo,
                    ':id_empleado' => $id_empleado,
                    ':rol' => $rol,
                    ':estado' => $estado
                ];
                
                // Si se proporcionó una nueva contraseña, actualizarla
                if (!empty($password)) {
                    $sql .= ", contrasena = :contrasena";
                    $params[':contrasena'] = password_hash($password, PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id_usuario = :id_usuario";
                
                query($sql, $params);
                
                $mensaje = 'Usuario actualizado correctamente';
            } else {
                // Crear nuevo usuario
                if (empty($password)) {
                    $errores[] = 'La contraseña es requerida para nuevos usuarios';
                    throw new Exception('Datos incompletos');
                }
                
                $sql = "INSERT INTO usuarios_sistema (nombre_usuario, contrasena, correo, id_empleado, rol, fecha_creacion, estado) 
                        VALUES (:nombre_usuario, :contrasena, :correo, :id_empleado, :rol, NOW(), :estado)";
                
                query($sql, [
                    ':nombre_usuario' => $nombre_usuario,
                    ':contrasena' => password_hash($password, PASSWORD_DEFAULT),
                    ':correo' => $correo,
                    ':id_empleado' => $id_empleado,
                    ':rol' => $rol,
                    ':estado' => $estado
                ]);
                
                $mensaje = 'Usuario creado correctamente';
            }
            
            // Registrar en bitácora
            $accion = $id_usuario > 0 ? 'Actualización de usuario' : 'Creación de usuario';
            registrarBitacora($accion, 'usuarios_sistema', $id_usuario ?: lastInsertId(), 'Nombre: ' . $nombre_usuario);
            
            commitTransaction();
            setFlashMessage('success', $mensaje);
            header('Location: index.php?page=configuracion/usuarios');
            exit;
            
        } catch (Exception $e) {
            rollbackTransaction();
            setFlashMessage('danger', 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    } else {
        // Mostrar errores
        setFlashMessage('danger', implode('<br>', $errores));
    }
}

// Obtener lista de usuarios
$sql = "SELECT u.*, e.primer_nombre, e.primer_apellido 
        FROM usuarios_sistema u 
        LEFT JOIN empleados e ON u.id_empleado = e.id_empleado 
        ORDER BY u.nombre_usuario";
$usuarios = fetchAll($sql);

// Obtener lista de empleados para el formulario
$sql = "SELECT id_empleado, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido 
        FROM empleados 
        WHERE estado = 'Activo' 
        ORDER BY primer_apellido, primer_nombre";
$empleados = fetchAll($sql);
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="fas fa-users-cog me-2"></i> Administración de Usuarios</h2>
    </div>
    <div class="col-md-6 text-md-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario">
            <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">Lista de Usuarios del Sistema</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Empleado</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Último Acceso</th>
                        <th>Estado</th>
                        <th width="120">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo $usuario['id_usuario']; ?></td>
                        <td><?php echo $usuario['nombre_usuario']; ?></td>
                        <td>
                            <?php 
                            echo $usuario['primer_nombre'] && $usuario['primer_apellido'] ? 
                                 $usuario['primer_nombre'] . ' ' . $usuario['primer_apellido'] : 
                                 'No asignado';
                            ?>
                        </td>
                        <td><?php echo $usuario['correo']; ?></td>
                        <td><?php echo $usuario['rol']; ?></td>
                        <td><?php echo $usuario['ultimo_acceso'] ? formatDate($usuario['ultimo_acceso'], 'd/m/Y H:i') : 'Nunca'; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $usuario['estado'] == 'Activo' ? 'success' : 'secondary'; ?>">
                                <?php echo $usuario['estado']; ?>
                            </span>
                        </td>
                        <td class="datatable-actions">
                            <button type="button" class="btn btn-sm btn-warning btn-editar" data-bs-toggle="modal" data-bs-target="#modalUsuario" 
                                    data-id="<?php echo $usuario['id_usuario']; ?>"
                                    data-username="<?php echo $usuario['nombre_usuario']; ?>"
                                    data-email="<?php echo $usuario['correo']; ?>"
                                    data-empleado="<?php echo $usuario['id_empleado']; ?>"
                                    data-rol="<?php echo $usuario['rol']; ?>"
                                    data-estado="<?php echo $usuario['estado']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="<?php echo BASE_URL; ?>?page=configuracion/reset_password&id=<?php echo $usuario['id_usuario']; ?>" 
                               class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Resetear Contraseña">
                                <i class="fas fa-key"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="" class="needs-validation" novalidate>
                <input type="hidden" name="id_usuario" id="id_usuario" value="0">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioLabel">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre_usuario" class="form-label required-field">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
                        <div class="invalid-feedback">Por favor ingrese un nombre de usuario</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label"><span id="password_required" class="required-field">Contraseña</span></label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                        <div class="form-text" id="password_help">Dejar en blanco para mantener la contraseña actual (al editar).</div>
                        <div class="invalid-feedback">Por favor ingrese una contraseña</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="correo" class="form-label required-field">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" required>
                        <div class="invalid-feedback">Por favor ingrese un correo electrónico válido</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_empleado" class="form-label">Empleado Asociado</label>
                        <select class="form-select" id="id_empleado" name="id_empleado">
                            <option value="">-- Ninguno --</option>
                            <?php foreach ($empleados as $empleado): ?>
                            <option value="<?php echo $empleado['id_empleado']; ?>">
                                <?php 
                                echo $empleado['primer_apellido'] . ' ' . 
                                     ($empleado['segundo_apellido'] ? $empleado['segundo_apellido'] . ', ' : ', ') . 
                                     $empleado['primer_nombre'] . ' ' . 
                                     ($empleado['segundo_nombre'] ?? '');
                                ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rol" class="form-label required-field">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="<?php echo ROL_ADMIN; ?>"><?php echo ROL_ADMIN; ?></option>
                            <option value="<?php echo ROL_GERENCIA; ?>"><?php echo ROL_GERENCIA; ?></option>
                            <option value="<?php echo ROL_CONTABILIDAD; ?>"><?php echo ROL_CONTABILIDAD; ?></option>
                            <option value="<?php echo ROL_RRHH; ?>"><?php echo ROL_RRHH; ?></option>
                            <option value="<?php echo ROL_CONSULTA; ?>"><?php echo ROL_CONSULTA; ?></option>
                        </select>
                        <div class="invalid-feedback">Por favor seleccione un rol</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="estado" class="form-label required-field">Estado</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                        <div class="invalid-feedback">Por favor seleccione un estado</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar DataTable
    $('.datatable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });
    
    // Inicializar validación de formulario
    Forms.initValidation();
    
    // Configurar modal para edición
    $('.btn-editar').on('click', function() {
        const id = $(this).data('id');
        const username = $(this).data('username');
        const email = $(this).data('email');
        const empleado = $(this).data('empleado');
        const rol = $(this).data('rol');
        const estado = $(this).data('estado');
        
        $('#modalUsuarioLabel').text('Editar Usuario');
        $('#id_usuario').val(id);
        $('#nombre_usuario').val(username);
        $('#correo').val(email);
        $('#id_empleado').val(empleado);
        $('#rol').val(rol);
        $('#estado').val(estado);
        
        // La contraseña no es requerida al editar
        $('#password').prop('required', false);
        $('#password_required').removeClass('required-field');
    });
    
    // Resetear modal para nuevo usuario
    $('#modalUsuario').on('hidden.bs.modal', function() {
        $('#modalUsuarioLabel').text('Nuevo Usuario');
        $('#id_usuario').val(0);
        $('#nombre_usuario').val('');
        $('#password').val('');
        $('#correo').val('');
        $('#id_empleado').val('');
        $('#rol').val('<?php echo ROL_ADMIN; ?>');
        $('#estado').val('Activo');
        
        // La contraseña es requerida para nuevos usuarios
        $('#password').prop('required', true);
        $('#password_required').addClass('required-field');
    });
});
</script> 