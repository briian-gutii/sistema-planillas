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
$titulo = "Cálculo de Pago de Vacaciones";
$estilo_tabla = "display: none;"; // Inicialmente oculta la tabla de resultados

// Procesamiento del formulario
if (isset($_POST['calcular'])) {
    $mes = isset($_POST['mes']) ? intval($_POST['mes']) : date("m");
    $anio = isset($_POST['anio']) ? intval($_POST['anio']) : date("Y");
    $estilo_tabla = ""; // Muestra la tabla cuando se envía el formulario
    
    // Aquí iría la lógica para calcular el pago de vacaciones
    $empleados = array();
    
    // Consulta para obtener empleados que tienen vacaciones en el período seleccionado
    $sql = "SELECT e.id, e.codigo, e.nombre, e.apellido, c.salario_base, 
            v.fecha_inicio, v.fecha_fin, v.dias_aprobados,
            DATEDIFF(v.fecha_fin, v.fecha_inicio) + 1 as dias_totales
            FROM empleados e
            INNER JOIN contratos c ON e.id = c.id_empleado
            INNER JOIN vacaciones v ON e.id = v.id_empleado
            WHERE e.estado = 'Activo'
            AND c.estado = 'Activo'
            AND v.estado = 'Aprobado'
            AND MONTH(v.fecha_inicio) = $mes
            AND YEAR(v.fecha_inicio) = $anio";
    
    $resultado = mysqli_query($conn, $sql);
    
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            // Cálculo del pago de vacaciones
            $salario_diario = $fila['salario_base'] / 30; // Salario diario aproximado
            $dias_vacaciones = intval($fila['dias_aprobados']);
            
            // En Guatemala, el pago de vacaciones suele incluir un 30% adicional
            $factor_vacaciones = 1.30;
            $monto_vacaciones = $salario_diario * $dias_vacaciones * $factor_vacaciones;
            
            // Agregar a la lista de empleados
            $empleados[] = array(
                'id' => $fila['id'],
                'codigo' => $fila['codigo'],
                'nombre' => $fila['nombre'] . ' ' . $fila['apellido'],
                'salario_base' => $fila['salario_base'],
                'salario_diario' => $salario_diario,
                'fecha_inicio' => $fila['fecha_inicio'],
                'fecha_fin' => $fila['fecha_fin'],
                'dias_vacaciones' => $dias_vacaciones,
                'monto_vacaciones' => $monto_vacaciones
            );
        }
    }
}

// Obtener lista de nombres de meses
$nombres_meses = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);
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
                                    <h6 class="card-subtitle text-muted">Cálculo del pago de vacaciones para los empleados</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Formulario para seleccionar período de cálculo -->
                                    <form method="POST" class="mb-4">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Mes:</label>
                                                    <select name="mes" class="form-select">
                                                        <?php foreach ($nombres_meses as $num => $nombre): ?>
                                                            <?php $selected = ($num == date("m")) ? 'selected' : ''; ?>
                                                            <option value="<?php echo $num; ?>" <?php echo $selected; ?>><?php echo $nombre; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Año:</label>
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
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="submit" name="calcular" class="btn btn-primary">
                                                    <i class="align-middle" data-feather="calculator"></i> Calcular Pago
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
                                            <table class="table table-striped table-hover" id="tablaVacaciones">
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Nombre</th>
                                                        <th>Fecha Inicio</th>
                                                        <th>Fecha Fin</th>
                                                        <th>Días</th>
                                                        <th>Salario Diario</th>
                                                        <th>Monto Total</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (isset($empleados) && count($empleados) > 0): ?>
                                                        <?php foreach ($empleados as $empleado): ?>
                                                            <tr>
                                                                <td><?php echo $empleado['codigo']; ?></td>
                                                                <td><?php echo $empleado['nombre']; ?></td>
                                                                <td><?php echo date('d/m/Y', strtotime($empleado['fecha_inicio'])); ?></td>
                                                                <td><?php echo date('d/m/Y', strtotime($empleado['fecha_fin'])); ?></td>
                                                                <td><?php echo $empleado['dias_vacaciones']; ?></td>
                                                                <td>Q<?php echo number_format($empleado['salario_diario'], 2); ?></td>
                                                                <td>Q<?php echo number_format($empleado['monto_vacaciones'], 2); ?></td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-info" onclick="verDetalle(<?php echo $empleado['id']; ?>)">
                                                                        <i class="align-middle" data-feather="eye"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">No se encontraron registros para el período seleccionado</td>
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
                window.location.href = 'exportar_vacaciones.php?tipo=excel&mes=<?php echo $mes ?? date("m"); ?>&anio=<?php echo $anio ?? date("Y"); ?>';
            });
            
            // Función para exportar a PDF
            document.getElementById('btnExportarPDF').addEventListener('click', function() {
                window.location.href = 'exportar_vacaciones.php?tipo=pdf&mes=<?php echo $mes ?? date("m"); ?>&anio=<?php echo $anio ?? date("Y"); ?>';
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