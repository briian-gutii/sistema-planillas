<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-question-circle me-2"></i> Ayuda del Sistema</h2>
        <p class="lead">Bienvenido a la sección de ayuda del Sistema de Planillas Guatemala.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-1"></i> Gestión de Empleados</h5>
            </div>
            <div class="card-body">
                <h6 class="card-subtitle mb-3 text-muted">Módulo de gestión de empleados</h6>
                <p>En este módulo puede:</p>
                <ul>
                    <li>Registrar nuevos empleados</li>
                    <li>Gestionar información personal y laboral</li>
                    <li>Asignar departamentos y puestos</li>
                    <li>Gestionar contratos</li>
                    <li>Registrar documentos</li>
                </ul>
                <p>Para registrar un nuevo empleado, haga clic en "Nuevo Empleado" y complete todos los campos requeridos.</p>
            </div>
            <div class="card-footer">
                <a href="<?php echo BASE_URL; ?>?page=empleados/lista" class="btn btn-primary">
                    Ir a Empleados
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-1"></i> Gestión de Planillas</h5>
            </div>
            <div class="card-body">
                <h6 class="card-subtitle mb-3 text-muted">Módulo de planillas y pagos</h6>
                <p>En este módulo puede:</p>
                <ul>
                    <li>Generar planillas por período</li>
                    <li>Calcular automáticamente el IGSS</li>
                    <li>Calcular retención de ISR</li>
                    <li>Aprobar y procesar planillas</li>
                    <li>Generar reportes</li>
                </ul>
                <p>Para generar una nueva planilla, debe tener un período de pago activo, luego haga clic en "Generar Planilla".</p>
            </div>
            <div class="card-footer">
                <a href="<?php echo BASE_URL; ?>?page=planillas/lista" class="btn btn-success">
                    Ir a Planillas
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-1"></i> Reportes</h5>
            </div>
            <div class="card-body">
                <h6 class="card-subtitle mb-3 text-muted">Reportes y estadísticas</h6>
                <p>En este módulo puede:</p>
                <ul>
                    <li>Generar reportes de planilla</li>
                    <li>Gestionar libro de salarios</li>
                    <li>Generar reportes para el IGSS</li>
                    <li>Reporte de vacaciones</li>
                    <li>Reporte de prestaciones</li>
                </ul>
                <p>Para generar cualquier reporte, seleccione el tipo de reporte y los parámetros requeridos como fecha o período.</p>
            </div>
            <div class="card-footer">
                <a href="<?php echo BASE_URL; ?>?page=reportes/planilla" class="btn btn-info">
                    Ir a Reportes
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Preguntas Frecuentes</h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="accordionFAQ">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                ¿Cómo se calcula el IGSS?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                <p>El IGSS en Guatemala se calcula de la siguiente manera:</p>
                                <ul>
                                    <li><strong>Cuota Laboral:</strong> 4.83% del salario base del trabajador.</li>
                                    <li><strong>Cuota Patronal:</strong> 10.67% del salario base del trabajador.</li>
                                </ul>
                                <p>Este cálculo se realiza automáticamente por el sistema al generar las planillas, basado en el salario base sin incluir bonificaciones.</p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                ¿Cómo se calcula el ISR?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                <p>El ISR (Impuesto Sobre la Renta) se calcula según los rangos establecidos en la legislación guatemalteca:</p>
                                <ul>
                                    <li>Ingresos de Q0.01 a Q48,000.00 anuales: 5% sobre el excedente de Q2,000.00</li>
                                    <li>Ingresos superiores a Q48,000.00 anuales: Q2,400.00 por los primeros Q48,000.00 más 7% sobre el excedente</li>
                                </ul>
                                <p>El sistema realiza una proyección anual del ingreso gravable y calcula la retención mensual correspondiente.</p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                ¿Cómo se calculan Aguinaldo y Bono 14?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionFAQ">
                            <div class="accordion-body">
                                <p>El cálculo de Aguinaldo y Bono 14 se realiza de la siguiente manera:</p>
                                <ul>
                                    <li><strong>Aguinaldo:</strong> Se calcula proporcionalmente al tiempo laborado, tomando como referencia un año completo (365 días) desde el 1 de diciembre del año anterior al 30 de noviembre del año en curso. Equivale a un salario mensual.</li>
                                    <li><strong>Bono 14:</strong> Se calcula proporcionalmente al tiempo laborado, tomando como referencia un año completo (365 días) desde el 1 de julio del año anterior al 30 de junio del año en curso. Equivale a un salario mensual.</li>
                                </ul>
                                <p>El sistema permite generar estos cálculos automáticamente en los períodos correspondientes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Soporte Técnico</h5>
            </div>
            <div class="card-body">
                <p>Si necesita asistencia técnica, puede comunicarse con el departamento de TI mediante:</p>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <h6><i class="fas fa-envelope me-2"></i> Email</h6>
                            <p><a href="mailto:soporte@empresa.com">soporte@empresa.com</a></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <h6><i class="fas fa-phone me-2"></i> Teléfono</h6>
                            <p>+502 2222-3333 ext. 123</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <h6><i class="fas fa-comments me-2"></i> Chat en línea</h6>
                            <p>Disponible de 8:00 AM a 5:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 