<?php

// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

// Obtener el ID de la planilla
$id_planilla = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_planilla <= 0) {
    setFlashMessage('Planilla no especificada', 'danger');
    header('Location: ' . BASE_URL . '?page=planillas/lista');
    exit;
}

// Obtener datos de la planilla
$db = getDB();
$planilla = [];
$detalles = [];
$totales = [
    'salario_base' => 0,
    'bonificaciones' => 0,
    'horas_extra' => 0,
    'otras_percepciones' => 0,
    'igss' => 0,
    'isr' => 0,
    'otras_deducciones' => 0,
    'salario_liquido' => 0
];

try {
    // Verificar si existe la planilla
    $query = "SELECT p.*, 
             DATE_FORMAT(p.fecha_generacion, '%d/%m/%Y') as fecha_formateada,
             CONCAT('ID Periodo: ', p.id_periodo) as nombre_periodo,
             'Todos' as nombre_departamento
             FROM Planillas p 
             WHERE p.id_planilla = :id_planilla";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('La planilla especificada no existe', 'danger');
        header('Location: ' . BASE_URL . '?page=planillas/lista');
        exit;
    }
    
    $planilla = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener los detalles de la planilla
    $queryDetalles = "SELECT pd.*, 
                    e.*,
                    d.nombre as departamento, p.nombre as puesto
                    FROM Detalle_Planilla pd
                    LEFT JOIN Empleados e ON pd.id_empleado = e.id_empleado
                    LEFT JOIN Departamentos d ON e.id_departamento = d.id_departamento
                    LEFT JOIN Puestos p ON e.id_puesto = p.id_puesto
                    WHERE pd.id_planilla = :id_planilla
                    ORDER BY e.primer_apellido, e.primer_nombre";
    
    $stmtDetalles = $db->prepare($queryDetalles);
    $stmtDetalles->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmtDetalles->execute();
    $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular totales
    foreach ($detalles as $detalle) {
        $totales['salario_base'] += $detalle['salario_base'] ?? 0;
        $totales['bonificaciones'] += $detalle['bonificacion_incentivo'] ?? 0;
        $totales['horas_extra'] += $detalle['monto_horas_extra'] ?? 0;
        $totales['otras_percepciones'] += $detalle['comisiones'] ?? 0;
        $totales['igss'] += $detalle['igss_laboral'] ?? 0;
        $totales['isr'] += $detalle['isr_retenido'] ?? 0;
        $totales['otras_deducciones'] += $detalle['otras_deducciones'] ?? 0;
        $totales['salario_liquido'] += $detalle['liquido_recibir'] ?? 0;
    }
    
} catch (Exception $e) {
    setFlashMessage('Error al cargar los datos: ' . $e->getMessage(), 'danger');
    // Para desarrollo: Mostrar error SQL directamente
    echo '<div class="alert alert-danger">Error SQL: ' . $e->getMessage() . '</div>';
}

// Obtener datos de la empresa
$empresa = [
    'nombre' => 'EMPRESA EJEMPLOS, S.A.',
    'direccion' => '12 Calle 2-58 Zona 10, Guatemala Ciudad',
    'telefono' => '(502) 2222-3333',
    'nit' => '123456-7'
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla <?php echo $planilla['id_planilla']; ?> - <?php echo $planilla['nombre_periodo']; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body {
            font-size: 12px;
            font-family: Arial, Helvetica, sans-serif;
        }
        .page-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header-info {
            font-size: 10px;
        }
        table {
            font-size: 11px;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #000;
        }
        .firma {
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 50px;
            text-align: center;
            width: 200px;
            display: inline-block;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
        @media print {
            .no-print {
                display: none;
            }
            .page-break {
                page-break-before: always;
            }
            body {
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="no-print mb-3 d-flex justify-content-between">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <a href="<?php echo BASE_URL; ?>?page=planillas/ver&id=<?php echo $id_planilla; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        
        <div class="page-header">
            <div class="row">
                <div class="col-6">
                    <h4><?php echo $empresa['nombre']; ?></h4>
                    <div class="header-info">
                        <p>
                            Dirección: <?php echo $empresa['direccion']; ?><br>
                            Teléfono: <?php echo $empresa['telefono']; ?><br>
                            NIT: <?php echo $empresa['nit']; ?>
                        </p>
                    </div>
                </div>
                <div class="col-6 text-end">
                    <h2>PLANILLA DE PAGO</h2>
                    <div class="header-info">
                        <p>
                            No. Planilla: <?php echo htmlspecialchars($planilla['id_planilla'] ?? 'N/A'); ?><br>
                            Período: <?php echo htmlspecialchars($planilla['nombre_periodo'] ?? 'N/A'); ?><br>
                            Departamento: <?php echo htmlspecialchars($planilla['nombre_departamento'] ?? 'N/A'); ?><br>
                            Fecha Generación: <?php echo htmlspecialchars($planilla['fecha_formateada'] ?? 'N/A'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr class="bg-light">
                        <th>Código</th>
                        <th>Empleado</th>
                        <th>Puesto</th>
                        <th class="text-end">Salario Base</th>
                        <th class="text-end">Bonificación</th>
                        <th class="text-end">Horas Extra</th>
                        <th class="text-end">Otras Percep.</th>
                        <th class="text-end">IGSS</th>
                        <th class="text-end">ISR</th>
                        <th class="text-end">Otras Deduc.</th>
                        <th class="text-end">Total a Recibir</th>
                        <th>Firma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($detalle['DPI'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars(($detalle['primer_apellido'] ?? '') . ', ' . ($detalle['primer_nombre'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($detalle['puesto'] ?? '-'); ?></td>
                        <td class="text-end"><?php echo number_format($detalle['salario_base'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($detalle['bonificacion_incentivo'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($detalle['monto_horas_extra'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($detalle['comisiones'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($detalle['igss_laboral'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($detalle['isr_retenido'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($detalle['otras_deducciones'] ?? 0, 2); ?></td>
                        <td class="text-end fw-bold"><?php echo number_format($detalle['liquido_recibir'] ?? 0, 2); ?></td>
                        <td>_________________</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light fw-bold">
                        <td colspan="3" class="text-end">TOTALES:</td>
                        <td class="text-end"><?php echo number_format($totales['salario_base'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($totales['bonificaciones'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($totales['horas_extra'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($totales['otras_percepciones'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($totales['igss'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($totales['isr'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($totales['otras_deducciones'] ?? 0, 2); ?></td>
                        <td class="text-end"><?php echo number_format($totales['salario_liquido'] ?? 0, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if (!empty($planilla['observaciones'])): ?>
        <div class="mt-3">
            <h6>Observaciones:</h6>
            <p><?php echo nl2br(htmlspecialchars($planilla['observaciones'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="mt-5 d-flex justify-content-between">
            <div>
                <div class="firma">
                    Elaborado por
                </div>
            </div>
            <div>
                <div class="firma">
                    Revisado por
                </div>
            </div>
            <div>
                <div class="firma">
                    Autorizado por
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Documento generado el <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            // Auto-print cuando la página carga
            if (location.search.includes('autoprint=true')) {
                window.print();
            }
        };
    </script>
</body>
</html> 