<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || !hasPermission(ROL_ADMIN)) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Configuración de Departamentos';
$activeMenu = 'configuracion';

// Obtener la lista de departamentos de la base de datos
$db = getDB();
$query = "SELECT * FROM departamentos ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario de nuevo departamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    try {
        $db->beginTransaction();
        
        // Acción para agregar nuevo departamento
        if ($_POST['accion'] === 'nuevo') {
            // Validar campos requeridos
            if (empty($_POST['nombre'])) {
                throw new Exception("El nombre del departamento es requerido.");
            }
            
            // Verificar que no exista un departamento con el mismo nombre
            $query = "SELECT id_departamento FROM departamentos WHERE nombre = :nombre";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $_POST['nombre']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Ya existe un departamento con este nombre.");
            }
            
            // Insertar el nuevo departamento
            $query = "INSERT INTO departamentos (nombre, descripcion, estado, created_at) 
                      VALUES (:nombre, :descripcion, :estado, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $_POST['nombre']);
            $stmt->bindParam(':descripcion', $_POST['descripcion']);
            $estado = isset($_POST['estado']) ? 1 : 0;
            $stmt->bindParam(':estado', $estado);
            
            if ($stmt->execute()) {
                $db->commit();
                setFlashMessage('Departamento agregado correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=configuracion/departamentos');
                exit;
            } else {
                throw new Exception("Error al agregar el departamento.");
            }
        }
        
        // Acción para editar departamento
        elseif ($_POST['accion'] === 'editar') {
            // Validar campos requeridos
            if (empty($_POST['nombre']) || empty($_POST['id_departamento'])) {
                throw new Exception("El nombre del departamento es requerido.");
            }
            
            // Verificar que no exista otro departamento con el mismo nombre
            $query = "SELECT id_departamento FROM departamentos WHERE nombre = :nombre AND id_departamento != :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $_POST['nombre']);
            $stmt->bindParam(':id', $_POST['id_departamento']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Ya existe otro departamento con este nombre.");
            }
            
            // Actualizar el departamento
            $query = "UPDATE departamentos 
                      SET nombre = :nombre, descripcion = :descripcion, estado = :estado, updated_at = NOW() 
                      WHERE id_departamento = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $_POST['nombre']);
            $stmt->bindParam(':descripcion', $_POST['descripcion']);
            $estado = isset($_POST['estado']) ? 1 : 0;
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':id', $_POST['id_departamento']);
            
            if ($stmt->execute()) {
                $db->commit();
                setFlashMessage('Departamento actualizado correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=configuracion/departamentos');
                exit;
            } else {
                throw new Exception("Error al actualizar el departamento.");
            }
        }
        
        // Acción para eliminar departamento
        elseif ($_POST['accion'] === 'eliminar') {
            if (empty($_POST['id_departamento'])) {
                throw new Exception("ID de departamento no válido.");
            }
            
            // Verificar si el departamento está en uso
            $query = "SELECT COUNT(*) AS total FROM empleados WHERE id_departamento = :id_departamento";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_departamento', $_POST['id_departamento']);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['total'] > 0) {
                throw new Exception("No se puede eliminar el departamento porque está asignado a uno o más empleados.");
            }
            
            // Eliminar el departamento
            $query = "DELETE FROM departamentos WHERE id_departamento = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['id_departamento']);
            
            if ($stmt->execute()) {
                $db->commit();
                setFlashMessage('Departamento eliminado correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=configuracion/departamentos');
                exit;
            } else {
                throw new Exception("Error al eliminar el departamento.");
            }
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-building fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Administración de departamentos de la empresa</p>

    <div class="row">
        <!-- Tabla de Departamentos -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Departamentos Registrados</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($departamentos) > 0): ?>
                                    <?php foreach ($departamentos as $departamento): ?>
                                        <tr>
                                            <td><?php echo $departamento['id_departamento']; ?></td>
                                            <td><?php echo $departamento['nombre']; ?></td>
                                            <td><?php echo $departamento['descripcion']; ?></td>
                                            <td>
                                                <?php if ($departamento['estado'] == 1): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-primary btn-sm btn-editar-departamento" 
                                                        data-id="<?php echo $departamento['id_departamento']; ?>" 
                                                        data-nombre="<?php echo $departamento['nombre']; ?>"
                                                        data-descripcion="<?php echo $departamento['descripcion']; ?>"
                                                        data-estado="<?php echo $departamento['estado']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm btn-eliminar-departamento" 
                                                        data-id="<?php echo $departamento['id_departamento']; ?>" 
                                                        data-nombre="<?php echo $departamento['nombre']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No hay departamentos registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Departamento -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary" id="formTitle">Nuevo Departamento</h6>
                </div>
                <div class="card-body">
                    <form id="formDepartamento" method="post">
                        <input type="hidden" name="accion" id="accion" value="nuevo">
                        <input type="hidden" name="id_departamento" id="id_departamento" value="">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                            <div class="form-text">Nombre del departamento (Ej: Recursos Humanos, Contabilidad)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="estado" name="estado" checked>
                            <label class="form-check-label" for="estado">Activo</label>
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

<!-- Modal Eliminar Departamento -->
<div class="modal fade" id="modalEliminarDepartamento" tabindex="-1" aria-labelledby="modalEliminarDepartamentoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarDepartamentoLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar el departamento <strong id="nombreDepartamentoEliminar"></strong>? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminarDepartamento" method="post">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_departamento" id="idDepartamentoEliminar" value="">
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
    const botonesEditar = document.querySelectorAll('.btn-editar-departamento');
    const formDepartamento = document.getElementById('formDepartamento');
    const btnCancelar = document.getElementById('btnCancelar');
    
    botonesEditar.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idDepartamento = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const descripcion = this.getAttribute('data-descripcion');
            const estado = this.getAttribute('data-estado') == '1';
            
            document.getElementById('formTitle').textContent = 'Editar Departamento';
            document.getElementById('accion').value = 'editar';
            document.getElementById('id_departamento').value = idDepartamento;
            document.getElementById('nombre').value = nombre;
            document.getElementById('descripcion').value = descripcion;
            document.getElementById('estado').checked = estado;
            
            btnCancelar.style.display = 'block';
            
            // Hacer scroll al formulario
            formDepartamento.scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Manejar el botón de cancelar edición
    btnCancelar.addEventListener('click', function() {
        document.getElementById('formTitle').textContent = 'Nuevo Departamento';
        document.getElementById('accion').value = 'nuevo';
        formDepartamento.reset();
        btnCancelar.style.display = 'none';
    });
    
    // Manejar el modal de eliminar departamento
    const botonesEliminar = document.querySelectorAll('.btn-eliminar-departamento');
    const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminarDepartamento'));
    
    botonesEliminar.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idDepartamento = this.getAttribute('data-id');
            const nombreDepartamento = this.getAttribute('data-nombre');
            
            document.getElementById('idDepartamentoEliminar').value = idDepartamento;
            document.getElementById('nombreDepartamentoEliminar').textContent = nombreDepartamento;
            
            modalEliminar.show();
        });
    });
});
</script> 