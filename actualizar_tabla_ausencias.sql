-- SQL para actualizar la tabla de ausencias
ALTER TABLE ausencias ADD COLUMN justificada TINYINT(1) DEFAULT 1 AFTER fecha_fin,
ADD COLUMN archivo_justificacion VARCHAR(255) NULL AFTER observaciones;
