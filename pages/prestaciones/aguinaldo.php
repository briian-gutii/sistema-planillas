<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
require_once '../../includes/menu.php';
require_once '../../includes/footer.php';
require_once '../../includes/conexion.php';
require_once '../../includes/funciones.php';

verificarAcceso();

// Configuración de la página
$titulo = "Cálculo de Aguinaldo";
$estilo_tabla = "display: none;"; // Inicialmente oculta la tabla de resultados

// Procesamiento del formulario
if (isset($_POST['calcular'])) {
    $anio = isset($_POST['anio']) ? intval($_POST['anio']) : date("Y");
    $estilo_tabla = ""; // Muestra la tabla cuando se envía el formulario
    
    // Aquí iría la lógica para calcular el aguinaldo
    $empleados = array();
    
    // Consulta para obtener empleados activos
    $sql = "SELECT e.id, e.codigo, e.nombre, e.apellido, c.salario_base, 
            DATEDIFF(LEAST(CURDATE(), DATE_ADD(c.fecha_inicio, INTERVAL 1 YEAR)), c.fecha_inicio) as dias_laborados
            FROM empleados e
            INNER JOIN contratos c ON e.id = c.id_empleado
            WHERE e.estado = 'Activo'
            AND c.estado = 'Activo'";
    
    $resultado = mysqli_query($conn, $sql);
    
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            // Cálculo del aguinaldo proporcional
            $dias_completos = 365; // Días para un año completo
            $dias_laborados = intval($fila['dias_laborados']);
            $dias_factor = min($dias_laborados, $dias_completos);
            $factor = $dias_factor / $dias_completos;
            
            // Monto de aguinaldo (normalmente equivale a un mes de salario)
            $monto_aguinaldo = $fila['salario_base'] * $factor;
            
            // Agregar a la lista de empleados
            $empleados[] = array(
                'id' => $fila['id'],
                'codigo' => $fila['codigo'],
                'nombre' => $fila['nombre'] . ' ' . $fila['apellido'],
                'salario_base' => $fila['salario_base'],
                'dias_laborados' => $dias_laborados,
                'factor' => $factor,
                'monto_aguinaldo' => $monto_aguinaldo
            );
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME . " - " . $titulo; ?></title>
    <?php mostrarHeader(); ?>
</head>
<body>
    <div class="wrapper">
        <?php mostrarMenu(); ?>

        <div class="main">
            <?php include_once '../../includes/nav.php'; ?>

            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><?php echo $titulo; ?></h5>
                                    <h6 class="card-subtitle text-muted">Cálculo del aguinaldo para los empleados según el tiempo laborado</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Formulario para seleccionar año de cálculo -->
                                    <form method="POST" class="mb-4">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Año para cálculo:</label>
                                                    <select name="anio" class="form-select">
                                                        <?php
                                                        $anio_actual = date("Y");
                                                        for ($i = $anio_actual - 5; $i <= $anio_actual + 1; $i++) {
                                                            $selected = ($i == $anio_actual) ? 'selected' : '';
                                                            echo "<option value=\"$i\" $selected>$i</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <button type="submit" name="calcular" class="btn btn-primary">
                                                    <i class="align-middle" data-feather="calculator"></i> Calcular Aguinaldo
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <!-- Tabla de resultados del cálculo -->
                                    <div style="<?php echo $estilo_tabla; ?>">
                                        <div class="mb-3">
                                            <button class="btn btn-success" id="btnExportarExcel">
                                                <i class="align-middle" data-feather="file-text"></i> Exportar a Excel
                                            </button>
                                            <button class="btn btn-danger" id="btnExportarPDF">
                                                <i class="align-middle" data-feather="file"></i> Exportar a PDF
                                            </button>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="tablaAguinaldo">
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Nombre</th>
                                                        <th>Salario Base</th>
                                                        <th>Días Laborados</th>
                                                        <th>Factor</th>
                                                        <th>Monto Aguinaldo</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (isset($empleados) && count($empleados) > 0): ?>
                                                        <?php foreach ($empleados as $empleado): ?>
                                                            <tr>
                                                                <td><?php echo $empleado['codigo']; ?></td>
                                                                <td><?php echo $empleado['nombre']; ?></td>
                                                                <td>Q<?php echo number_format($empleado['salario_base'], 2); ?></td>
                                                                <td><?php echo $empleado['dias_laborados']; ?></td>
                                                                <td><?php echo number_format($empleado['factor'], 2); ?></td>
                                                                <td>Q<?php echo number_format($empleado['monto_aguinaldo'], 2); ?></td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-info" onclick="verDetalle(<?php echo $empleado['id']; ?>)">
                                                                        <i class="align-middle" data-feather="eye"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="7" class="text-center">No se encontraron registros</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php mostrarFooter(); ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Función para exportar a Excel
            document.getElementById('btnExportarExcel').addEventListener('click', function() {
                window.location.href = 'exportar_aguinaldo.php?tipo=excel&anio=<?php echo $anio ?? date("Y"); ?>';
            });
            
            // Función para exportar a PDF
            document.getElementById('btnExportarPDF').addEventListener('click', function() {
                window.location.href = 'exportar_aguinaldo.php?tipo=pdf&anio=<?php echo $anio ?? date("Y"); ?>';
            });
        });
        
        // Función para ver detalle de un empleado
        function verDetalle(idEmpleado) {
            // Aquí podría abrirse un modal o redirigir a una página de detalle
            console.log("Ver detalle del empleado ID: " + idEmpleado);
        }
    </script>
</body>
</html> 