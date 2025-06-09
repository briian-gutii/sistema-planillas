<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta id="base-url" content="<?php echo BASE_URL; ?>">
    <title><?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <!-- Reloj JavaScript -->
    <script src="<?php echo BASE_URL; ?>assets/js/reloj.js"></script>
    <style>
        :root {
            --primary: #7c3aed;
            --primary-light: rgba(124, 58, 237, 0.1);
            --blue: #0284c7;
            --blue-light: rgba(2, 132, 199, 0.1);
            --pink: #dc3545;
            --pink-light: rgba(220, 53, 69, 0.1);
            --teal: #198754;
            --teal-light: rgba(25, 135, 84, 0.1);
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
        }
        
        body {
            background-color: var(--slate-50);
            color: var(--slate-800);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Header styles */
        .app-header {
            background-color: white;
            border-bottom: 1px solid var(--slate-200);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .app-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        
        .navbar-nav .nav-link {
            color: var(--slate-600);
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        
        .navbar-nav .nav-link:hover {
            background-color: var(--slate-100);
            color: var(--slate-800);
        }
        
        .navbar-nav .nav-link.active {
            background-color: var(--slate-100);
            color: var(--slate-800);
        }
        
        .dropdown-menu {
            border: 1px solid var(--slate-200);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            padding: 0.5rem;
            min-width: 12rem;
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
        }
        
        .dropdown-menu.show {
            display: block !important;
        }
        
        .dropdown-item {
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: var(--slate-100);
        }
        
        .user-avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .notification-badge {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            width: 0.5rem;
            height: 0.5rem;
            background-color: #ef4444;
            border-radius: 50%;
        }

        /* Card styles */
        .card {
            border: 1px solid var(--slate-200);
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--slate-200);
            padding: 1rem 1.5rem;
            font-weight: 500;
            color: var(--slate-800);
        }
        
        /* Stats cards */
        .stat-card .icon-wrapper {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--slate-800);
        }
        
        .stat-card .stat-label {
            font-size: 0.875rem;
            color: var(--slate-500);
        }
        
        .stat-badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
        }
        
        /* Color variations */
        .bg-primary-light {
            background-color: var(--primary-light);
        }
        
        .text-primary {
            color: var(--primary) !important;
        }
        
        .bg-primary-badge {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        
        .bg-blue-light {
            background-color: var(--blue-light);
        }
        
        .text-blue {
            color: var(--blue) !important;
        }
        
        .bg-blue-badge {
            background-color: var(--blue-light);
            color: var(--blue);
        }
        
        .bg-pink-light {
            background-color: var(--pink-light);
        }
        
        .text-pink {
            color: var(--pink) !important;
        }
        
        .bg-pink-badge {
            background-color: var(--pink-light);
            color: var(--pink);
        }
        
        .bg-teal-light {
            background-color: var(--teal-light);
        }
        
        .text-teal {
            color: var(--teal) !important;
        }
        
        .bg-teal-badge {
            background-color: var(--teal-light);
            color: var(--teal);
        }
    </style>
</head>
<body>
    <?php if(isset($_SESSION['user_id'])): ?>
    <!-- Navbar estándar de Bootstrap -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom app-header">
        <div class="container-fluid px-4">
            <a class="app-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-file-invoice-dollar me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>?page=dashboard">
                            <i class="bi bi-house me-1"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if(hasPermission(ROL_RRHH) || hasPermission(ROL_ADMIN)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="empleadosDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-people me-1"></i> Empleados
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="empleadosDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=empleados/lista">Lista de Empleados</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=empleados/nuevo">Nuevo Empleado</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=contratos/lista">Contratos</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=ausencias/lista">Ausencias</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=vacaciones/lista">Vacaciones</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if(hasPermission(ROL_CONTABILIDAD) || hasPermission(ROL_ADMIN)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="planillasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-text me-1"></i> Planillas
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="planillasDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=planillas/lista">Planillas</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=planillas/generar">Generar Planilla</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=periodos/lista">Periodos de Pago</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=horas_extra/lista">Horas Extra</a></li>
                            <li><div class="dropdown-divider"></div></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=prestaciones/index"><i class="bi bi-gift me-2"></i> Prestaciones</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=prestaciones/aguinaldo">Aguinaldo</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=prestaciones/bono14">Bono 14</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=prestaciones/vacaciones">Vacaciones</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if(hasPermission(ROL_CONTABILIDAD) || hasPermission(ROL_ADMIN)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="finanzasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-cash-coin me-1"></i> Finanzas
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="finanzasDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=prestamos/lista">Préstamos</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=bancos/lista">Bancos</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=conceptos/lista">Conceptos Nómina</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if(hasPermission(ROL_GERENCIA) || hasPermission(ROL_ADMIN) || hasPermission(ROL_CONTABILIDAD)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-bar-graph me-1"></i> Reportes
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="reportesDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=reportes/index"><i class="bi bi-bar-chart me-2"></i> Centro de Reportes</a></li>
                            <li><div class="dropdown-divider"></div></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=reportes/planillas"><i class="bi bi-file-spreadsheet me-2"></i> Planillas</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=reportes/boletas"><i class="bi bi-receipt me-2"></i> Boletas de Pago</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=reportes/tributarios"><i class="bi bi-file-earmark-text me-2"></i> Tributarios (ISR)</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=reportes/igss"><i class="bi bi-hospital me-2"></i> IGSS</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=reportes/prestaciones"><i class="bi bi-gift me-2"></i> Prestaciones</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=reportes/empleados"><i class="bi bi-people me-2"></i> Empleados</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if(hasPermission(ROL_ADMIN)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="configDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear me-1"></i> Configuración
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="configDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=configuracion/departamentos">Departamentos</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=configuracion/puestos">Puestos</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=configuracion/usuarios">Usuarios</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=configuracion/igss">Config. IGSS</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=configuracion/isr">Config. ISR</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=configuracion/bitacora">Bitácora</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="d-none d-md-block text-end me-3">
                        <div class="text-slate-500 small"><?php echo date('d/m/Y'); ?></div>
                        <div class="fw-medium" id="reloj-tiempo-real"><?php echo date('H:i:s'); ?></div>
                    </div>
                    
                    <div class="position-relative">
                        <a href="#" class="btn btn-sm btn-link text-slate-600 position-relative">
                            <i class="fas fa-bell fs-5"></i>
                            <span class="notification-badge"></span>
                        </a>
                    </div>
                    
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-decoration-none text-slate-700" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-2">
                                    <?php 
                                        $username = $_SESSION['user_name'] ?? 'Usuario';
                                        echo substr($username, 0, 2);
                                    ?>
                                </div>
                                <span class="d-none d-md-inline"><?php echo $_SESSION['user_name']; ?></span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=perfil">Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=cambiar_password">Cambiar Contraseña</a></li>
                            <li><div class="dropdown-divider"></div></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Container -->
    <main class="container-fluid px-4 py-4">
        <?php 
        // Mostrar mensajes flash
        $flash = getFlashMessage();
        if ($flash) {
            echo '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">';
            echo $flash['message'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
        ?>
    </main>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Función para actualizar el reloj en tiempo real
        function actualizarReloj() {
            var ahora = new Date();
            var horas = ahora.getHours();
            var minutos = ahora.getMinutes();
            var segundos = ahora.getSeconds();
            
            // Formatear para mostrar siempre dos dígitos
            if (horas < 10) horas = "0" + horas;
            if (minutos < 10) minutos = "0" + minutos;
            if (segundos < 10) segundos = "0" + segundos;
            
            document.getElementById("reloj-tiempo-real").textContent = horas + ":" + minutos + ":" + segundos;
            
            // Actualizar cada segundo
            setTimeout(actualizarReloj, 1000);
        }
        
        // Script para manejar los menús desplegables
        document.addEventListener("DOMContentLoaded", function() {
            // Iniciar el reloj
            actualizarReloj();
            
            // Seleccionar todos los botones de menús desplegables
            var dropdownToggles = document.querySelectorAll('.dropdown-toggle');
            
            // Añadir manejador de eventos a cada botón
            dropdownToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Obtener el menú asociado a este botón
                    var menu = this.nextElementSibling;
                    
                    // Si el menú está visible, ocultarlo
                    if (menu.classList.contains('show')) {
                        menu.classList.remove('show');
                        this.setAttribute('aria-expanded', 'false');
                    } else {
                        // Cerrar todos los otros menús abiertos
                        document.querySelectorAll('.dropdown-menu.show').forEach(function(openMenu) {
                            openMenu.classList.remove('show');
                            var toggler = openMenu.previousElementSibling;
                            if (toggler) {
                                toggler.setAttribute('aria-expanded', 'false');
                            }
                        });
                        
                        // Mostrar este menú
                        menu.classList.add('show');
                        this.setAttribute('aria-expanded', 'true');
                    }
                });
            });
            
            // Cerrar menús cuando se hace clic fuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                        menu.classList.remove('show');
                        var toggler = menu.previousElementSibling;
                        if (toggler) {
                            toggler.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html> 