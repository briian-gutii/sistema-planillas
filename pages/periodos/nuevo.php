<?php

// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

$pageTitle = 'Nuevo Período';
$activeMenu = 'configuracion';

// Verificar si la tabla existe
$db = getDB();
$tablaExiste = false;
try {
    $checkTable = $db->query("SHOW TABLES LIKE 'periodos'");
    $tablaExiste = ($checkTable->rowCount() > 0);
} catch (Exception $e) {
    // Si hay un error, asumimos que la tabla no existe
    $tablaExiste = false;
}

// Si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tablaExiste) {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'Quincenal';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    
    // Validación básica
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = 'El nombre del período es obligatorio';
    }
    
    if (empty($fecha_inicio)) {
        $errores[] = 'La fecha de inicio es obligatoria';
    }
    
    if (empty($fecha_fin)) {
        $errores[] = 'La fecha de fin es obligatoria';
    }
    
    // Validar que la fecha de fin sea posterior a la fecha de inicio
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);
        
        if ($fin < $inicio) {
            $errores[] = 'La fecha de fin debe ser posterior a la fecha de inicio';
        }
    }
    
    // Si no hay errores, guardar el período
    if (empty($errores)) {
        try {
            $query = "INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, tipo, descripcion, estado, creado_por)
                      VALUES (:nombre, :fecha_inicio, :fecha_fin, :tipo, :descripcion, 'Activo', :creado_por)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_fin);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':creado_por', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                setFlashMessage('Período creado correctamente', 'success');
                header('Location: ' . BASE_URL . '?page=periodos/lista');
                exit;
            } else {
                $errores[] = 'Error al guardar el período';
            }
        } catch (Exception $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
    
    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        foreach ($errores as $error) {
            setFlashMessage($error, 'danger');
        }
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-calendar-plus fa-fw"></i> <?php echo $pageTitle; ?></h1>
    <p class="mb-4">Complete el formulario para registrar un nuevo período de nómina</p>

    <?php if (!$tablaExiste): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> La tabla de períodos no existe todavía. Debe crearla antes de usar esta funcionalidad.
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Crear tabla de períodos</h6>
        </div>
        <div class="card-body">
            <p>Para crear la tabla de períodos, puede ejecutar el siguiente SQL en su base de datos:</p>
            <pre class="bg-light p-3">
CREATE TABLE `periodos` (
  `id_periodo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `tipo` enum('Mensual','Quincenal','Semanal') NOT NULL DEFAULT 'Quincenal',
  `descripcion` text,
  `estado` enum('Activo','Cerrado') NOT NULL DEFAULT 'Activo',
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            </pre>
            <p>Una vez creada la tabla, podrá registrar nuevos períodos.</p>
            <a href="<?php echo BASE_URL; ?>?page=periodos/lista" class="btn btn-secondary">
                <i class="fas fa-arrow-left fa-fw"></i> Volver a la Lista
            </a>
        </div>
    </div>
    <?php else: ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Datos del Período</h6>
        </div>
        <div class="card-body">
            <form method="post" id="formNuevoPeriodo">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre del Período *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required 
                               value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
                               placeholder="Ej: Mayo 2023, Quincena 1 Junio 2023">
                        <small class="form-text text-muted">Nombre descriptivo del período de nómina</small>
                    </div>
                    <div class="col-md-6">
                        <label for="tipo" class="form-label">Tipo de Período *</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="Quincenal" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Quincenal') ? 'selected' : ''; ?>>Quincenal</option>
                            <option value="Mensual" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Mensual') ? 'selected' : ''; ?>>Mensual</option>
                            <option value="Semanal" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Semanal') ? 'selected' : ''; ?>>Semanal</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required
                               value="<?php echo isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_fin" class="form-label">Fecha de Fin *</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required
                               value="<?php echo isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                 placeholder="Información adicional sobre este período"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <a href="<?php echo BASE_URL; ?>?page=periodos/lista" class="btn btn-secondary">
                            <i class="fas fa-arrow-left fa-fw"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save fa-fw"></i> Guardar Período
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?> <!-- Fin del if tablaExiste -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formNuevoPeriodo = document.getElementById('formNuevoPeriodo');
    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaFinInput = document.getElementById('fecha_fin');
    const tipoSelect = document.getElementById('tipo');
    const nombreInput = document.getElementById('nombre');
    
    // Establecer fecha actual como fecha por defecto para inicio
    if (fechaInicioInput && !fechaInicioInput.value) {
        const hoy = new Date();
        const fechaHoy = hoy.getFullYear() + '-' + 
                        String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                        String(hoy.getDate()).padStart(2, '0');
        fechaInicioInput.value = fechaHoy;
    }
    
    // Función para sugerir fecha fin basada en tipo de período
    function sugerirFechaFin() {
        if (!fechaInicioInput.value) return;
        
        const inicio = new Date(fechaInicioInput.value);
        let fin = new Date(inicio);
        
        // Ajustar fecha fin según tipo de período
        switch (tipoSelect.value) {
            case 'Quincenal':
                fin.setDate(fin.getDate() + 14); // 15 días (incluye el día inicial)
                break;
            case 'Mensual':
                fin.setMonth(fin.getMonth() + 1);
                fin.setDate(fin.getDate() - 1); // Último día del mes
                break;
            case 'Semanal':
                fin.setDate(fin.getDate() + 6); // 7 días (incluye el día inicial)
                break;
        }
        
        const fechaFinStr = fin.getFullYear() + '-' + 
                           String(fin.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(fin.getDate()).padStart(2, '0');
        fechaFinInput.value = fechaFinStr;
        
        // Sugerir nombre de período
        if (!nombreInput.value) {
            let nombreSugerido = '';
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            
            switch (tipoSelect.value) {
                case 'Quincenal':
                    // Determinar si es primera o segunda quincena
                    const dia = inicio.getDate();
                    if (dia <= 15) {
                        nombreSugerido = '1ª Quincena ' + meses[inicio.getMonth()] + ' ' + inicio.getFullYear();
                    } else {
                        nombreSugerido = '2ª Quincena ' + meses[inicio.getMonth()] + ' ' + inicio.getFullYear();
                    }
                    break;
                case 'Mensual':
                    nombreSugerido = meses[inicio.getMonth()] + ' ' + inicio.getFullYear();
                    break;
                case 'Semanal':
                    nombreSugerido = 'Semana ' + obtenerNumeroSemana(inicio) + ' - ' + inicio.getFullYear();
                    break;
            }
            
            nombreInput.value = nombreSugerido;
        }
    }
    
    // Obtener número de semana del año
    function obtenerNumeroSemana(fecha) {
        const primerDia = new Date(fecha.getFullYear(), 0, 1);
        const dias = Math.floor((fecha - primerDia) / (24 * 60 * 60 * 1000));
        return Math.ceil((dias + primerDia.getDay() + 1) / 7);
    }
    
    // Eventos
    if (fechaInicioInput && fechaFinInput && tipoSelect) {
        fechaInicioInput.addEventListener('change', sugerirFechaFin);
        tipoSelect.addEventListener('change', sugerirFechaFin);
        
        // Inicializar con valores actuales
        sugerirFechaFin();
    }
    
    // Validación del formulario
    if (formNuevoPeriodo) {
        formNuevoPeriodo.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validar fechas
            if (fechaInicioInput.value && fechaFinInput.value) {
                const inicio = new Date(fechaInicioInput.value);
                const fin = new Date(fechaFinInput.value);
                
                if (fin < inicio) {
                    isValid = false;
                    alert('La fecha de fin debe ser posterior a la fecha de inicio');
                }
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    }
});
</script> 