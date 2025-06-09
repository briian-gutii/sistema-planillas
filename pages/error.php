<?php
// Esta página se muestra cuando el usuario intenta acceder a una página que no existe
$titulo = "Página no encontrada";
?>

<div class="container-fluid p-0">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Error 404</h5>
                    <h6 class="card-subtitle text-muted">La página solicitada no existe</h6>
                </div>
                <div class="card-body">
                    <div class="text-center my-5">
                        <h1 class="display-1 fw-bold text-danger">404</h1>
                        <p class="fs-3">
                            <span class="text-danger">¡Ups!</span> Página no encontrada
                        </p>
                        <p class="lead">
                            La página que está buscando no existe o ha sido movida.
                        </p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="align-middle" data-feather="home"></i> Volver al inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Actualizar el título de la página
        document.title = "<?php echo SITE_NAME . " - " . $titulo; ?>";
    });
</script> 