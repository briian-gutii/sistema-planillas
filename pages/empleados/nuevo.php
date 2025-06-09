<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Nuevo Empleado';
$activeMenu = 'empleados';

// Obtener los departamentos
$db = getDB();
$query = "SELECT id_departamento, nombre FROM Departamentos ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener los puestos
$query = "SELECT id_puesto, nombre FROM Puestos ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$puestos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos
        $campos_requeridos = ['codigo', 'nombres', 'apellidos', 'dpi', 'nit', 'id_departamento', 'id_puesto', 'salario_base', 'fecha_inicio'];
        
        foreach ($campos_requeridos as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("Todos los campos marcados con * son obligatorios.");
            }
        }
        
        // Validar formato de DPI
        if (!preg_match('/^\d{13}$/', $_POST['dpi'])) {
            throw new Exception("El DPI debe contener exactamente 13 dígitos numéricos.");
        }
        
        // Generar número de afiliación IGSS si está marcado
        $igss = !empty($_POST['afiliado_igss']) ? generarNumeroIGSS() : null;
        
        // Preparar la consulta
        $query = "INSERT INTO empleados (
                    codigo, nombres, apellidos, dpi, nit, fecha_nacimiento, direccion, 
                    telefono, email, id_departamento, id_puesto, salario_base, 
                    fecha_inicio, cuenta_bancaria, afiliado_igss, numero_igss, 
                    estado, created_at
                ) VALUES (
                    :codigo, :nombres, :apellidos, :dpi, :nit, :fecha_nacimiento, :direccion, 
                    :telefono, :email, :id_departamento, :id_puesto, :salario_base, 
                    :fecha_inicio, :cuenta_bancaria, :afiliado_igss, :numero_igss, 
                    1, NOW()
                )";
                
        $stmt = $db->prepare($query);
        $stmt->bindParam(':codigo', $_POST['codigo']);
        $stmt->bindParam(':nombres', $_POST['nombres']);
        $stmt->bindParam(':apellidos', $_POST['apellidos']);
        $stmt->bindParam(':dpi', $_POST['dpi']);
        $stmt->bindParam(':nit', $_POST['nit']);
        $stmt->bindParam(':fecha_nacimiento', $_POST['fecha_nacimiento']);
        $stmt->bindParam(':direccion', $_POST['direccion']);
        $stmt->bindParam(':telefono', $_POST['telefono']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':id_departamento', $_POST['id_departamento']);
        $stmt->bindParam(':id_puesto', $_POST['id_puesto']);
        $stmt->bindParam(':salario_base', $_POST['salario_base']);
        $stmt->bindParam(':fecha_inicio', $_POST['fecha_inicio']);
        $stmt->bindParam(':cuenta_bancaria', $_POST['cuenta_bancaria']);
        $stmt->bindParam(':afiliado_igss', $_POST['afiliado_igss']);
        $stmt->bindParam(':numero_igss', $igss);
        
        if ($stmt->execute()) {
            $id_empleado = $db->lastInsertId();
            
            // Crear registro en tabla contratos
            $query = "INSERT INTO contratos (
                        id_empleado, fecha_inicio, salario, estado, created_at
                      ) VALUES (
                        :id_empleado, :fecha_inicio, :salario, 1, NOW()
                      )";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_empleado', $id_empleado);
            $stmt->bindParam(':fecha_inicio', $_POST['fecha_inicio']);
            $stmt->bindParam(':salario', $_POST['salario_base']);
            $stmt->execute();
            
            setFlashMessage('Empleado registrado correctamente', 'success');
            header('Location: ' . BASE_URL . '?page=empleados/lista');
            exit;
        } else {
            throw new Exception("Error al registrar el empleado.");
        }
    } catch (Exception $e) {
        setFlashMessage($e->getMessage(), 'danger');
    }
}

// Función para generar número de afiliación IGSS
function generarNumeroIGSS() {
    // Formato: XX-XXXXXXX (2 dígitos, guion, 7 dígitos)
    $prefijo = rand(10, 99);
    $sufijo = rand(1000000, 9999999);
    return $prefijo . '-' . $sufijo;
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-user-plus fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Complete el formulario para registrar un nuevo empleado en el sistema</p>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulario de Registro</h6>
        </div>
        <div class="card-body">
            <form method="post" id="formNuevoEmpleado">
                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <h5><i class="fas fa-id-card fa-fw"></i> Información Personal</h5>
                        <hr>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="codigo" class="form-label">Código de Empleado *</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" required>
                    </div>
                    
                    <div class="col-md-5 mb-3">
                        <label for="nombres" class="form-label">Nombres *</label>
                        <input type="text" class="form-control" id="nombres" name="nombres" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="apellidos" class="form-label">Apellidos *</label>
                        <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="dpi" class="form-label">DPI *</label>
                        <input type="text" class="form-control" id="dpi" name="dpi" required maxlength="13">
                        <small class="text-muted">13 dígitos sin espacios ni guiones</small>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="nit" class="form-label">NIT *</label>
                        <input type="text" class="form-control" id="nit" name="nit" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                    </div>
                    
                    <div class="col-md-8 mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <h5><i class="fas fa-briefcase fa-fw"></i> Información Laboral</h5>
                        <hr>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="id_departamento" class="form-label">Departamento *</label>
                        <select class="form-select" id="id_departamento" name="id_departamento" required>
                            <option value="">Seleccione un departamento</option>
                            <?php foreach ($departamentos as $departamento): ?>
                                <option value="<?php echo $departamento['id_departamento']; ?>"><?php echo $departamento['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="id_puesto" class="form-label">Puesto *</label>
                        <select class="form-select" id="id_puesto" name="id_puesto" required>
                            <option value="">Seleccione un puesto</option>
                            <?php foreach ($puestos as $puesto): ?>
                                <option value="<?php echo $puesto['id_puesto']; ?>"><?php echo $puesto['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="salario_base" class="form-label">Salario Base *</label>
                        <div class="input-group">
                            <span class="input-group-text">Q</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="salario_base" name="salario_base" required>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="cuenta_bancaria" class="form-label">Cuenta Bancaria</label>
                        <input type="text" class="form-control" id="cuenta_bancaria" name="cuenta_bancaria">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" value="1" id="afiliado_igss" name="afiliado_igss">
                            <label class="form-check-label" for="afiliado_igss">
                                Afiliado al IGSS
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>?page=empleados/lista" class="btn btn-secondary">
                                <i class="fas fa-arrow-left fa-fw"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save fa-fw"></i> Guardar Empleado
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const formNuevoEmpleado = document.getElementById('formNuevoEmpleado');
    
    formNuevoEmpleado.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validar DPI
        const dpiInput = document.getElementById('dpi');
        if (!/^\d{13}$/.test(dpiInput.value)) {
            alert('El DPI debe contener exactamente 13 dígitos numéricos.');
            dpiInput.focus();
            isValid = false;
        }
        
        // Validar email si está presente
        const emailInput = document.getElementById('email');
        if (emailInput.value !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
            alert('El formato del correo electrónico no es válido.');
            emailInput.focus();
            isValid = false;
        }
        
        if (!isValid) {
            event.preventDefault();
        }
    });
});
</script> 