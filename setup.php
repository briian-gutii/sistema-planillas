<?php
// Verificar si se está ejecutando desde un servidor web
$isWeb = isset($_SERVER['HTTP_HOST']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema de Planillas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h1 class="h3 mb-0">Configuración del Sistema de Planillas</h1>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h4 class="alert-heading">Bienvenido al asistente de configuración</h4>
                    <p>Este asistente le ayudará a configurar la base de datos del sistema de planillas y cargar datos de prueba.</p>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Reiniciar Base de Datos</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>¡ADVERTENCIA!</strong> Esta opción eliminará la base de datos actual y creará una nueva con la estructura básica.</p>
                                <p>Use esta opción solo si desea comenzar desde cero.</p>
                                <div class="d-grid">
                                    <a href="reset_database.php" class="btn btn-outline-danger">Reiniciar Base de Datos</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">Completar Estructura</h5>
                            </div>
                            <div class="card-body">
                                <p>Esta opción agregará todas las tablas adicionales necesarias para el sistema completo de planillas.</p>
                                <p>Use esta opción después de reiniciar la base de datos para tener la estructura completa.</p>
                                <div class="d-grid">
                                    <a href="completar_estructura.php" class="btn btn-outline-warning">Completar Estructura</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Generar Datos de Prueba</h5>
                            </div>
                            <div class="card-body">
                                <p>Esta opción generará datos de prueba en la base de datos, incluyendo usuarios, empleados, departamentos, periodos, etc.</p>
                                <p>Es útil para realizar pruebas del sistema.</p>
                                <div class="d-grid">
                                    <a href="datos_prueba.php" class="btn btn-outline-success">Generar Datos de Prueba</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Ingresar al Sistema</h5>
                            </div>
                            <div class="card-body">
                                <p>Si la base de datos ya está configurada, puede ingresar al sistema directamente.</p>
                                <div class="d-grid">
                                    <a href="index.php" class="btn btn-primary">Ingresar al Sistema</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <h4 class="alert-heading">Credenciales por defecto</h4>
                    <p class="mb-0">Una vez generados los datos de prueba, puede ingresar con las siguientes credenciales:</p>
                    <ul class="mb-0 mt-2">
                        <li><strong>Usuario:</strong> admin</li>
                        <li><strong>Contraseña:</strong> 123456</li>
                    </ul>
                </div>
                
                <div class="alert alert-secondary mt-3">
                    <h5 class="alert-heading">Proceso recomendado para la configuración:</h5>
                    <ol class="mb-0">
                        <li>Reiniciar la base de datos (crea estructura básica)</li>
                        <li>Completar la estructura (agrega todas las tablas adicionales)</li>
                        <li>Generar datos de prueba (crea registros de ejemplo)</li>
                        <li>Ingresar al sistema</li>
                    </ol>
                </div>
            </div>
            <div class="card-footer text-center text-muted">
                Sistema de Planillas - Guatemala
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
</body>
</html> 