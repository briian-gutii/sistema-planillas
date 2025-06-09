/**
 * Script principal para el Sistema de Planillas Guatemala
 */

// Configuración global para DataTables
const DataTablesConfig = {
    /**
     * Inicializa todas las tablas DataTables de forma segura
     */
    init: function() {
        // Deshabilitar errores de DataTables a nivel global
        if ($.fn.dataTable) {
            $.fn.dataTable.ext.errMode = 'none';
            
            // Obtener la URL base a partir de la meta tag o un método alternativo
            const baseUrl = (function() {
                // Intentar obtener de un elemento con ID 'base-url' (que podríamos agregar en el header)
                const baseUrlElement = document.getElementById('base-url');
                if (baseUrlElement) return baseUrlElement.getAttribute('content');
                
                // Alternativa: usar la URL del script actual como referencia
                const scripts = document.getElementsByTagName('script');
                for (let i = 0; i < scripts.length; i++) {
                    const src = scripts[i].src;
                    if (src.indexOf('assets/js/script.js') !== -1) {
                        return src.substring(0, src.indexOf('assets/js/script.js'));
                    }
                }
                
                // Si todo falla, intentar detectar desde la ruta actual
                const path = window.location.pathname;
                const planillaIndex = path.indexOf('/planilla');
                if (planillaIndex !== -1) {
                    return window.location.origin + path.substring(0, planillaIndex + 9) + '/';
                }
                
                // Último recurso: usar ruta relativa
                return './';
            })();
            
            // Configuración global para todos los DataTables
            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    url: baseUrl + 'assets/js/es-ES.json'
                },
                // Configuración básica que evita problemas
                ordering: false,  // Desactivar ordenamiento
                autoWidth: false, // Desactivar autowidth
                deferRender: true // Mejorar rendimiento
            });
        }
        
        // IMPORTANTE: Solo inicializar tablas con la clase 'datatable-init'
        // Esto evita conflictos con tablas que han sido convertidas a soluciones más simples
        document.querySelectorAll('table.datatable-init').forEach(function(table) {
            try {
                const tableId = table.id;
                if (!tableId) return;
                
                // Saltar tablas problemáticas conocidas
                if (tableId === 'planillasTable' || tableId === 'detallesTable') {
                    console.log('Tabla ' + tableId + ' excluida de la inicialización de DataTables');
                    return;
                }
                
                // Saltar tablas que ya fueron inicializadas manualmente
                if (table.hasAttribute('data-dt-initialized')) {
                    console.log('Tabla ' + tableId + ' ya inicializada manualmente, saltando');
                    return;
                }
                
                // Evitar reinicialización
                if ($.fn.DataTable.isDataTable('#' + tableId)) {
                    $('#' + tableId).DataTable().destroy();
                }
                
                // Configuración básica y segura
                $('#' + tableId).DataTable({
                    // No definir columns - eso causa el error
                    paging: true,
                    searching: true,
                    info: true,
                    processing: false, // Desactivar procesamiento que causa errores
                    columnDefs: [
                        // Hacer que todas las columnas acepten cualquier contenido
                        { targets: '_all', defaultContent: '' }
                    ]
                });
                
                // Marcar como inicializada
                table.setAttribute('data-dt-initialized', 'true');
            } catch (error) {
                console.error('Error al inicializar DataTable:', error);
                // Si falla, quitar clase dataTable para evitar estilos rotos
                $('#' + tableId).removeClass('dataTable');
            }
        });
    }
};

// Funciones para formularios
const Forms = {
    /**
     * Inicializa validación de formularios Bootstrap
     */
    initValidation: function() {
        const forms = document.querySelectorAll('.needs-validation');
        
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    },
    
    /**
     * Formatea inputs de números para moneda
     */
    initCurrencyInputs: function() {
        document.querySelectorAll('.currency-input').forEach(input => {
            input.addEventListener('blur', function() {
                // Convertir a número
                let value = this.value.replace(/[^\d.-]/g, '');
                if (value) {
                    // Formatear como moneda
                    this.value = parseFloat(value).toFixed(2);
                }
            });
        });
    },
    
    /**
     * Inicializa datepickers
     */
    initDatepickers: function() {
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.datepicker', {
                dateFormat: 'd/m/Y',
                locale: 'es',
                allowInput: true
            });
        }
    }
};

// Funciones para la planilla
const Planilla = {
    /**
     * Calcula totales de la planilla
     */
    calcularTotales: function() {
        let totalBruto = 0;
        let totalDeducciones = 0;
        let totalNeto = 0;
        
        // Sumar todos los montos de las filas
        document.querySelectorAll('.fila-empleado').forEach(fila => {
            const salarioTotal = parseFloat(fila.querySelector('.salario-total').value) || 0;
            const deducciones = parseFloat(fila.querySelector('.total-deducciones').value) || 0;
            const liquido = parseFloat(fila.querySelector('.liquido-recibir').value) || 0;
            
            totalBruto += salarioTotal;
            totalDeducciones += deducciones;
            totalNeto += liquido;
        });
        
        // Actualizar totales en la interfaz
        document.getElementById('total_bruto').value = totalBruto.toFixed(2);
        document.getElementById('total_deducciones').value = totalDeducciones.toFixed(2);
        document.getElementById('total_neto').value = totalNeto.toFixed(2);
    },
    
    /**
     * Calcula el salario de un empleado en la planilla
     */
    calcularSalarioEmpleado: function(filaId) {
        const fila = document.getElementById(filaId);
        if (!fila) return;
        
        // Obtener valores
        const salarioBase = parseFloat(fila.querySelector('.salario-base').value) || 0;
        const bonificacion = parseFloat(fila.querySelector('.bonificacion').value) || 0;
        const diasTrabajados = parseFloat(fila.querySelector('.dias-trabajados').value) || 0;
        const horasExtra = parseFloat(fila.querySelector('.horas-extra').value) || 0;
        const montoHorasExtra = parseFloat(fila.querySelector('.monto-horas-extra').value) || 0;
        const comisiones = parseFloat(fila.querySelector('.comisiones').value) || 0;
        const bonificacionesAdicionales = parseFloat(fila.querySelector('.bonificaciones-adicionales').value) || 0;
        
        // Calcular salario proporcional a días trabajados
        const salarioProporcional = (salarioBase / 30) * diasTrabajados;
        
        // Calcular salario total (ingresos)
        const salarioTotal = salarioProporcional + bonificacion + montoHorasExtra + comisiones + bonificacionesAdicionales;
        fila.querySelector('.salario-total').value = salarioTotal.toFixed(2);
        
        // Obtener deducciones
        const igssLaboral = parseFloat(fila.querySelector('.igss-laboral').value) || 0;
        const isrRetenido = parseFloat(fila.querySelector('.isr-retenido').value) || 0;
        const otrasDeducciones = parseFloat(fila.querySelector('.otras-deducciones').value) || 0;
        const prestamos = parseFloat(fila.querySelector('.prestamos').value) || 0;
        const descuentosJudiciales = parseFloat(fila.querySelector('.descuentos-judiciales').value) || 0;
        
        // Calcular total deducciones
        const totalDeducciones = igssLaboral + isrRetenido + otrasDeducciones + prestamos + descuentosJudiciales;
        fila.querySelector('.total-deducciones').value = totalDeducciones.toFixed(2);
        
        // Calcular líquido a recibir
        const liquidoRecibir = salarioTotal - totalDeducciones;
        fila.querySelector('.liquido-recibir').value = liquidoRecibir.toFixed(2);
        
        // Recalcular totales de la planilla
        this.calcularTotales();
    }
};

// Funciones para reportes
const Reportes = {
    /**
     * Prepara para imprimir
     */
    print: function() {
        window.print();
    },
    
    /**
     * Exporta a Excel
     */
    exportToExcel: function(tableId, fileName) {
        if (typeof XLSX === 'undefined') {
            console.error('XLSX library not loaded');
            return;
        }
        
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const wb = XLSX.utils.table_to_book(table);
        XLSX.writeFile(wb, fileName || 'export.xlsx');
    },
    
    /**
     * Exporta a PDF
     */
    exportToPdf: function(elementId, fileName) {
        if (typeof html2pdf === 'undefined') {
            console.error('html2pdf library not loaded');
            return;
        }
        
        const element = document.getElementById(elementId);
        if (!element) return;
        
        html2pdf()
            .from(element)
            .save(fileName || 'export.pdf');
    }
};

// Inicialización cuando el DOM está cargado
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    Forms.initValidation();
    Forms.initCurrencyInputs();
    Forms.initDatepickers();
    
    // Inicializar las DataTables de forma segura
    DataTablesConfig.init();
    
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers de Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}); 