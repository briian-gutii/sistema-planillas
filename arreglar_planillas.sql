-- Verificar qué tablas de periodos existen
SHOW TABLES LIKE 'periodo%';
 
-- Verificar qué columna en planillas hace referencia a los periodos
DESCRIBE Planillas;

-- Verificar si existen periodos
SELECT * FROM periodos_pago LIMIT 5;  

-- Crear un periodo si no existe
INSERT INTO periodos_pago (nombre, fecha_inicio, fecha_fin, estado) 
SELECT 'Periodo Mayo 2023', '2023-05-01', '2023-05-31', 'Activo'
WHERE NOT EXISTS (SELECT 1 FROM periodos_pago LIMIT 1);

-- Obtener el ID del último periodo o de alguno existente
SELECT @id_periodo := IF(LAST_INSERT_ID() > 0, 
                          LAST_INSERT_ID(), 
                          (SELECT id_periodo FROM periodos_pago ORDER BY id_periodo DESC LIMIT 1));

-- Crear una planilla nueva
INSERT INTO Planillas (descripcion, fecha_generacion, id_periodo, estado)
VALUES ('Planilla de prueba SQL', NOW(), @id_periodo, 'Generada');

-- Obtener el ID de la planilla
SELECT @id_planilla := LAST_INSERT_ID();

-- Verificar los empleados activos
SELECT * FROM empleados WHERE estado = 'Activo' LIMIT 5;

-- Insertar detalles para los empleados activos
INSERT INTO Detalle_Planilla (
    id_planilla, id_empleado, dias_trabajados, salario_base, 
    bonificacion_incentivo, salario_total, igss_laboral,
    total_deducciones, liquido_recibir
)
SELECT 
    @id_planilla, id_empleado, 30, IFNULL(salario_base, 5000), 
    250, IFNULL(salario_base, 5000) + 250, ROUND(IFNULL(salario_base, 5000) * 0.0483, 2),
    ROUND(IFNULL(salario_base, 5000) * 0.0483, 2), (IFNULL(salario_base, 5000) + 250) - ROUND(IFNULL(salario_base, 5000) * 0.0483, 2)
FROM empleados 
WHERE estado = 'Activo';

-- Si no hay empleados activos, usar cualquier empleado
INSERT INTO Detalle_Planilla (
    id_planilla, id_empleado, dias_trabajados, salario_base, 
    bonificacion_incentivo, salario_total, igss_laboral,
    total_deducciones, liquido_recibir
)
SELECT 
    @id_planilla, id_empleado, 30, IFNULL(salario_base, 5000), 
    250, IFNULL(salario_base, 5000) + 250, ROUND(IFNULL(salario_base, 5000) * 0.0483, 2),
    ROUND(IFNULL(salario_base, 5000) * 0.0483, 2), (IFNULL(salario_base, 5000) + 250) - ROUND(IFNULL(salario_base, 5000) * 0.0483, 2)
FROM empleados 
WHERE NOT EXISTS (SELECT 1 FROM Detalle_Planilla WHERE id_planilla = @id_planilla)
LIMIT 5;

-- Verificar si se crearon los detalles
SELECT COUNT(*) AS detalles_creados FROM Detalle_Planilla WHERE id_planilla = @id_planilla;

-- Mostrar información de la planilla creada
SELECT p.id_planilla, p.descripcion, p.fecha_generacion, p.estado, COUNT(dp.id_detalle) AS num_detalles
FROM Planillas p
LEFT JOIN Detalle_Planilla dp ON p.id_planilla = dp.id_planilla
WHERE p.id_planilla = @id_planilla
GROUP BY p.id_planilla; 