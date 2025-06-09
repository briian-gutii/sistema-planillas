-- SQL para actualizar la tabla planillas
-- Este script añade la columna tipo_planilla a la tabla planillas si no existe

-- Comprobar si la columna tipo_planilla existe y añadirla si no
ALTER TABLE planillas
ADD COLUMN IF NOT EXISTS tipo_planilla VARCHAR(50) DEFAULT 'Ordinaria' AFTER id_periodo;

-- Actualizar valores existentes (opcional)
-- UPDATE planillas SET tipo_planilla = 'Ordinaria' WHERE tipo_planilla IS NULL;

-- NOTA: Ejecute este script en phpMyAdmin o en MySQL para añadir la columna faltante.
-- Si recibe un error con IF NOT EXISTS, puede omitir esa parte
-- y ejecutar primero una comprobación manual para ver si la columna existe. 