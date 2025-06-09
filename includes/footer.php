    </main>
    <!-- Footer -->
    <footer class="bg-light py-3 mt-4 border-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> <?php echo EMPRESA_NOMBRE; ?> - Sistema de Planillas</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">v1.0.0</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Moment.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
    
    <script>
        // Inicializar todos los DataTables
        $(document).ready(function() {
            $('.datatable').DataTable({
                language: {
                    url: '<?php echo BASE_URL; ?>assets/js/es-ES.json'
                },
                responsive: true
            });
            
            // Inicializar todos los tooltips
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltips].map(tooltip => new bootstrap.Tooltip(tooltip));
            
            // Confirmar eliminación con SweetAlert2
            $('.btn-delete').on('click', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                
                Swal.fire({
                    title: '¿Está seguro?',
                    text: "Esta acción no se puede revertir",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
        
        // La funcionalidad del reloj se ha movido a assets/js/reloj.js
    </script>
</body>
</html> 