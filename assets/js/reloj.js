/**
 * Funcionalidad del reloj en tiempo real
 */

// Función para actualizar el reloj en tiempo real
function actualizarReloj() {
    const ahora = new Date();
    const opciones = { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    };
    
    const elementoReloj = document.getElementById('reloj-tiempo-real');
    if (elementoReloj) {
        elementoReloj.textContent = ahora.toLocaleString('es-GT', opciones);
    }
    
    // Actualizar cada segundo
    setTimeout(actualizarReloj, 1000);
}

// Inicializar el reloj cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    actualizarReloj();
});

// También inicializar con jQuery (como respaldo)
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        actualizarReloj();
    });
} 