-- Verificar primero las tablas de periodos
SHOW TABLES LIKE 'periodo%';

-- Intentar usar periodos_pago o periodos_nomina 
-- (ajustamos esto dependiendo de la tabla que realmente exista)
SET @tabla_periodos := 'periodos_pago';
SELECT IF(EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES 
                  WHERE TABLE_SCHEMA = 'planillasguatemala' 
                  AND TABLE_NAME = 'periodos_nomina'), 
           'periodos_nomina', @tabla_periodos) INTO @tabla_periodos;

-- 1. Crear un periodo nuevo (usando la tabla que realmente existe)
SET @sql = CONCAT('INSERT INTO ', @tabla_periodos, 
                  ' (nombre, fecha_inicio, fecha_fin, estado) 
                  SELECT ''Periodo Completo - Automático'', ''2023-05-01'', ''2023-05-31'', ''Activo''
                  WHERE NOT EXISTS (SELECT 1 FROM ', @tabla_periodos, ' LIMIT 1)');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Obtener un ID de periodo válido
SET @sql = CONCAT('SELECT id_periodo FROM ', @tabla_periodos, ' ORDER BY id_periodo DESC LIMIT 1 INTO @id_periodo');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Crear una nueva planilla usando el periodo obtenido
INSERT INTO Planillas (descripcion, fecha_generacion, id_periodo, estado)
VALUES (CONCAT('Planilla Directa Terminal - ', NOW()), NOW(), @id_periodo, 'Generada');

-- 4. Guardar el ID de la planilla creada
SET @id_planilla = LAST_INSERT_ID();

-- 5. Insertar detalles para TODOS los empleados, activos o no
INSERT INTO Detalle_Planilla (
    id_planilla, id_empleado, dias_trabajados, salario_base, 
    bonificacion_incentivo, horas_extra, monto_horas_extra, comisiones,
    bonificaciones_adicionales, salario_total, igss_laboral, isr_retenido,
    otras_deducciones, anticipos, prestamos, descuentos_judiciales,
    total_deducciones, liquido_recibir
)
SELECT 
    @id_planilla, id_empleado, 30, IFNULL(salario_base, 5000), 
    250, 0, 0, 0,
    0, IFNULL(salario_base, 5000) + 250, ROUND(IFNULL(salario_base, 5000) * 0.0483, 2), 0,
    0, 0, 0, 0,
    ROUND(IFNULL(salario_base, 5000) * 0.0483, 2), 
    (IFNULL(salario_base, 5000) + 250) - ROUND(IFNULL(salario_base, 5000) * 0.0483, 2)
FROM empleados
LIMIT 10;

-- 6. Mostrar un mensaje con la información de la planilla creada
SELECT CONCAT('✓ Planilla creada con ID: ', @id_planilla) AS 'Resultado';
SELECT CONCAT('Total de empleados en la planilla: ', 
             (SELECT COUNT(*) FROM Detalle_Planilla WHERE id_planilla = @id_planilla)) 
       AS 'Info';

-- 7. Instrucciones para ver la planilla en el sistema
SELECT 'Para ver la planilla creada, vaya a:' AS 'Siguiente paso';
SELECT CONCAT('http://localhost/planilla/index.php?page=planillas/ver&id=', @id_planilla) AS 'URL'; 