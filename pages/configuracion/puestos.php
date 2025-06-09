<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || !hasPermission(ROL_ADMIN)) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Configuración de Puestos';
$activeMenu = 'configuracion';

// Obtener la lista de puestos de la base de datos
$db = getDB();
$query = "SELECT p.*, d.nombre AS departamento 
          FROM puestos p
          LEFT JOIN departamentos d ON p.id_departamento = d.id_departamento
          ORDER BY p.nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$puestos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de departamentos para el formulario
$query = "SELECT id_departamento, nombre FROM departamentos WHERE estado = 1 ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario de puesto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    try {
        $db->beginTransaction();
        
        // Acción para agregar nuevo puesto
        if ($_POST['accion'] === 'nuevo') {
            // Validar campos requeridos
            if (empty($_POST['nombre']) || empty($_POST['id_departamento'])) {
                throw new Exception("El nombre del puesto y el departamento son requeridos.");
            }
            
            // Verificar que no exista un puesto con el mismo nombre en el mismo departamento
            $query = "SELECT id FROM puestos WHERE nombre = :nombre AND id_departamento = :id_departamento";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $_POST['nombre']);
            $stmt->bindParam(':id_departamento', $_POST['id_departamento']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Ya existe un puesto con este nombre en el departamento seleccionado.");
            }
            
            // Insertar el nuevo puesto
            $query = "INSERT INTO puestos (nombre, descripcion, id_departamento, salario_minimo, salario_maximo, created_at) 
                      VALUES (:nombre, :descripcion, :id_departamento, :salario_minimo, :salario_maximo, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $_POST['nombre']);
            $stmt->bindParam(':descripcion', $_POST['descripcion']);
            $stmt->bindParam(':id_departamento', $_POST['id_departamento']);
            $stmt->bindParam(':salario_minimo', $_POST['salario_minimo']);
            $stmt->bindParam(':salario_maximo', $_POST['salario_maximo']);
            
            if ($stmt->execute()) {
                $db->commit();
                setFlashMessage('Puesto agregado correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=configuracion/puestos');
                exit;
            } else {
                throw new Exception("Error al agregar el puesto.");
            }
        }
        
        // Acción para editar puesto
        elseif ($_POST['accion'] === 'editar') {
            // Validar campos requeridos
            if (empty($_POST['nombre']) || empty($_POST['id_departamento']) || empty($_POST['id_puesto'])) {
                throw new Exception("El nombre del puesto y el departamento son requeridos.");
            }
            
            // Verificar que no exista otro puesto con el mismo nombre en el mismo departamento
            $query = "SELECT id FROM puestos WHERE nombre = :nombre AND id_departamento = :id_departamento AND id != :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $_POST['nombre']);
            $stmt->bindParam(':id_departamento', $_POST['id_departamento']);
            $stmt->bindParam(':id', $_POST['id_puesto']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Ya existe otro puesto con este nombre en el departamento seleccionado.");
            }
            
            // Actualizar el puesto
            $query = "UPDATE puestos 
                      SET nombre = :nombre, descripcion = :descripcion, id_departamento = :id_departamento, 
                          salario_minimo = :salario_minimo, salario_maximo = :salario_maximo, 
                          updated_at = NOW() 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $_POST['nombre']);
            $stmt->bindParam(':descripcion', $_POST['descripcion']);
            $stmt->bindParam(':id_departamento', $_POST['id_departamento']);
            $stmt->bindParam(':salario_minimo', $_POST['salario_minimo']);
            $stmt->bindParam(':salario_maximo', $_POST['salario_maximo']);
            $stmt->bindParam(':id', $_POST['id_puesto']);
            
            if ($stmt->execute()) {
                $db->commit();
                setFlashMessage('Puesto actualizado correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=configuracion/puestos');
                exit;
            } else {
                throw new Exception("Error al actualizar el puesto.");
            }
        }
        
        // Acción para eliminar puesto
        elseif ($_POST['accion'] === 'eliminar') {
            if (empty($_POST['id_puesto'])) {
                throw new Exception("ID de puesto no válido.");
            }
            
            // Verificar si el puesto está en uso
            $query = "SELECT COUNT(*) AS total FROM empleados WHERE id_puesto = :id_puesto";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_puesto', $_POST['id_puesto']);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['total'] > 0) {
                throw new Exception("No se puede eliminar el puesto porque está asignado a uno o más empleados.");
            }
            
            // Eliminar el puesto
            $query = "DELETE FROM puestos WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['id_puesto']);
            
            if ($stmt->execute()) {
                $db->commit();
                setFlashMessage('Puesto eliminado correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=configuracion/puestos');
                exit;
            } else {
                throw new Exception("Error al eliminar el puesto.");
            }
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-briefcase fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Administración de puestos de trabajo de la empresa</p>

    <div class="row">
        <!-- Tabla de Puestos -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Puestos Registrados</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Departamento</th>
                                    <th>Rango Salarial</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($puestos) > 0): ?>
                                    <?php foreach ($puestos as $puesto): ?>
                                        <tr>
                                            <td><?php echo $puesto['id']; ?></td>
                                            <td><?php echo $puesto['nombre']; ?></td>
                                            <td><?php echo $puesto['departamento']; ?></td>
                                            <td>
                                                Q <?php echo number_format($puesto['salario_minimo'], 2); ?> - 
                                                Q <?php echo number_format($puesto['salario_maximo'], 2); ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-primary btn-sm btn-editar-puesto" 
                                                        data-id="<?php echo $puesto['id']; ?>" 
                                                        data-nombre="<?php echo $puesto['nombre']; ?>"
                                                        data-descripcion="<?php echo $puesto['descripcion']; ?>"
                                                        data-id-departamento="<?php echo $puesto['id_departamento']; ?>"
                                                        data-salario-minimo="<?php echo $puesto['salario_minimo']; ?>"
                                                        data-salario-maximo="<?php echo $puesto['salario_maximo']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm btn-eliminar-puesto" 
                                                        data-id="<?php echo $puesto['id']; ?>" 
                                                        data-nombre="<?php echo $puesto['nombre']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No hay puestos registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Puesto -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary" id="formTitle">Nuevo Puesto</h6>
                </div>
                <div class="card-body">
                    <form id="formPuesto" method="post">
                        <input type="hidden" name="accion" id="accion" value="nuevo">
                        <input type="hidden" name="id_puesto" id="id_puesto" value="">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="id_departamento" class="form-label">Departamento *</label>
                            <select class="form-select" id="id_departamento" name="id_departamento" required>
                                <option value="">Seleccione un departamento</option>
                                <?php foreach ($departamentos as $departamento): ?>
                                    <option value="<?php echo $departamento['id_departamento']; ?>"><?php echo $departamento['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="salario_minimo" class="form-label">Salario Mínimo *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Q</span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="salario_minimo" name="salario_minimo" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="salario_maximo" class="form-label">Salario Máximo *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Q</span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="salario_maximo" name="salario_maximo" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save fa-fw"></i> Guardar
                            </button>
                            <button type="button" class="btn btn-secondary" id="btnCancelar" style="display:none;">
                                <i class="fas fa-times fa-fw"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar Puesto -->
<div class="modal fade" id="modalEliminarPuesto" tabindex="-1" aria-labelledby="modalEliminarPuestoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarPuestoLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar el puesto <strong id="nombrePuestoEliminar"></strong>? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminarPuesto" method="post">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_puesto" id="idPuestoEliminar" value="">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#dataTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[1, 'asc']] // Ordenar por nombre
    });
    
    // Manejar el formulario de edición
    const botonesEditar = document.querySelectorAll('.btn-editar-puesto');
    const formPuesto = document.getElementById('formPuesto');
    const btnCancelar = document.getElementById('btnCancelar');
    
    botonesEditar.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idPuesto = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const descripcion = this.getAttribute('data-descripcion');
            const idDepartamento = this.getAttribute('data-id-departamento');
            const salarioMinimo = this.getAttribute('data-salario-minimo');
            const salarioMaximo = this.getAttribute('data-salario-maximo');
            
            document.getElementById('formTitle').textContent = 'Editar Puesto';
            document.getElementById('accion').value = 'editar';
            document.getElementById('id_puesto').value = idPuesto;
            document.getElementById('nombre').value = nombre;
            document.getElementById('descripcion').value = descripcion;
            document.getElementById('id_departamento').value = idDepartamento;
            document.getElementById('salario_minimo').value = salarioMinimo;
            document.getElementById('salario_maximo').value = salarioMaximo;
            
            btnCancelar.style.display = 'block';
            
            // Hacer scroll al formulario
            formPuesto.scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Manejar el botón de cancelar edición
    btnCancelar.addEventListener('click', function() {
        document.getElementById('formTitle').textContent = 'Nuevo Puesto';
        document.getElementById('accion').value = 'nuevo';
        formPuesto.reset();
        btnCancelar.style.display = 'none';
    });
    
    // Manejar el modal de eliminar puesto
    const botonesEliminar = document.querySelectorAll('.btn-eliminar-puesto');
    const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminarPuesto'));
    
    botonesEliminar.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idPuesto = this.getAttribute('data-id');
            const nombrePuesto = this.getAttribute('data-nombre');
            
            document.getElementById('idPuestoEliminar').value = idPuesto;
            document.getElementById('nombrePuestoEliminar').textContent = nombrePuesto;
            
            modalEliminar.show();
        });
    });
    
    // Validación del formulario
    formPuesto.addEventListener('submit', function(event) {
        const salarioMinimo = parseFloat(document.getElementById('salario_minimo').value);
        const salarioMaximo = parseFloat(document.getElementById('salario_maximo').value);
        
        if (salarioMaximo < salarioMinimo) {
            event.preventDefault();
            alert('El salario máximo debe ser mayor o igual al salario mínimo.');
        }
    });
});
</script> 