-- Mostrar las planillas disponibles con conteo de detalles
SELECT 
    p.id_planilla, 
    p.descripcion, 
    p.fecha_generacion, 
    p.estado, 
    pp.nombre AS periodo,
    COUNT(dp.id_detalle) AS num_detalles
FROM 
    Planillas p
LEFT JOIN 
    Detalle_Planilla dp ON p.id_planilla = dp.id_planilla
LEFT JOIN
    periodos_pago pp ON p.id_periodo = pp.id_periodo
GROUP BY 
    p.id_planilla
ORDER BY 
    p.id_planilla DESC
LIMIT 10; 