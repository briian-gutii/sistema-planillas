<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Actualización de consultas para usar la relación tipo_planilla</h1>";

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Modificar el archivo lista.php para utilizar la nueva relación
    $rutaArchivo = __DIR__ . '/pages/planillas/lista.php';
    
    if (file_exists($rutaArchivo)) {
        $contenido = file_get_contents($rutaArchivo);
        
        // 1. Cambiar la verificación de la columna tipo_planilla a id_tipo_planilla
        $contenido = str_replace(
            'SHOW COLUMNS FROM planillas LIKE \'tipo_planilla\'',
            'SHOW COLUMNS FROM planillas LIKE \'id_tipo_planilla\'',
            $contenido
        );
        
        // 2. Cambiar la forma en que se incluyen los campos en el query
        $contenido = str_replace(
            '// Si existe la columna tipo_planilla, la incluimos, si no, usamos valor por defecto
        if ($tieneTipoPlanilla) {
            $query .= "p.tipo_planilla, ";
        } else {
            $query .= "\'No disponible\' as tipo_planilla, ";
        }',
            '// Utilizamos la relación con la tabla tipo_planilla
        if ($tieneTipoPlanilla) {
            $query .= "(SELECT tp.nombre FROM tipo_planilla tp WHERE tp.id_tipo_planilla = p.id_tipo_planilla) as tipo_planilla, ";
        } else {
            $query .= "\'No disponible\' as tipo_planilla, ";
        }',
            $contenido
        );
        
        // 3. Cambiar la condición del filtro por tipo
        $contenido = str_replace(
            '// Aplicar filtro de tipo solo si la columna existe
        if (!empty($tipo) && $tieneTipoPlanilla) {
            $query .= " AND p.tipo_planilla = :tipo";
            $params[\':tipo\'] = $tipo;
        }',
            '// Aplicar filtro de tipo usando id_tipo_planilla
        if (!empty($tipo) && $tieneTipoPlanilla) {
            $query .= " AND p.id_tipo_planilla = :tipo";
            $params[\':tipo\'] = $tipo;
        }',
            $contenido
        );
        
        // 4. Actualizar el select de tipos para obtener los valores de la tabla tipo_planilla
        $patron = '<select class="form-select" id="tipo" name="tipo" <?php if (!isset\($tieneTipoPlanilla\) \|\| !$tieneTipoPlanilla\) echo \'disabled\'; ?>>
                                <option value="">Todos los tipos</option>
                                <option value="Ordinaria" <?php if\($tipo == \'Ordinaria\'\) echo \'selected\'; ?>>Ordinaria</option>
                                <option value="Extraordinaria" <?php if\($tipo == \'Extraordinaria\'\) echo \'selected\'; ?>>Extraordinaria</option>
                                <option value="Aguinaldo" <?php if\($tipo == \'Aguinaldo\'\) echo \'selected\'; ?>>Aguinaldo</option>
                                <option value="Bono14" <?php if\($tipo == \'Bono14\'\) echo \'selected\'; ?>>Bono 14</option>
                            </select>';
        
        $reemplazo = '<select class="form-select" id="tipo" name="tipo" <?php if (!isset($tieneTipoPlanilla) || !$tieneTipoPlanilla) echo \'disabled\'; ?>>
                                <option value="">Todos los tipos</option>
                                <?php 
                                // Cargar tipos de planilla desde la tabla
                                try {
                                    $tiposQuery = $db->query("SELECT id_tipo_planilla, nombre FROM tipo_planilla WHERE activo = 1 ORDER BY nombre");
                                    $tiposPlanilla = $tiposQuery->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($tiposPlanilla as $tipoPlanilla) {
                                        echo "<option value=\"" . $tipoPlanilla[\'id_tipo_planilla\'] . "\" " . 
                                             ($tipo == $tipoPlanilla[\'id_tipo_planilla\'] ? \'selected\' : \'\') . ">" . 
                                             htmlspecialchars($tipoPlanilla[\'nombre\']) . "</option>";
                                    }
                                } catch (Exception $e) {
                                    // Si hay error, mostrar opciones predeterminadas
                                    echo "<option value=\"1\" " . ($tipo == 1 ? \'selected\' : \'\') . ">Ordinaria</option>";
                                    echo "<option value=\"2\" " . ($tipo == 2 ? \'selected\' : \'\') . ">Extraordinaria</option>";
                                }
                                ?>
                            </select>';
                            
        $contenido = str_replace($patron, $reemplazo, $contenido);
        
        // 5. Cambiar el mensaje de error
        $contenido = str_replace(
            '<small><i class="fas fa-exclamation-circle"></i> La columna tipo_planilla no existe en la base de datos</small>',
            '<small><i class="fas fa-exclamation-circle"></i> La columna id_tipo_planilla no existe en la base de datos</small>',
            $contenido
        );
        
        // Guardar los cambios
        file_put_contents($rutaArchivo, $contenido);
        echo "<p>Se ha actualizado el archivo lista.php con las nuevas consultas.</p>";
        
        // También actualizar el archivo ver.php para mostrar el nombre del tipo de planilla
        $rutaArchivoVer = __DIR__ . '/pages/planillas/ver.php';
        if (file_exists($rutaArchivoVer)) {
            $contenidoVer = file_get_contents($rutaArchivoVer);
            
            // Buscar y reemplazar la forma en que se muestra el tipo de planilla
            $patronVer = 'Planilla #<?php echo $planilla[\'id_planilla\'] ?? \'N/A\'; ?> - <?php echo $planilla[\'tipo_planilla\'] ?? \'N/A\'; ?>';
            $reemplazoVer = 'Planilla #<?php echo $planilla[\'id_planilla\'] ?? \'N/A\'; ?> - <?php 
                // Obtener nombre del tipo de planilla
                if (isset($planilla[\'id_tipo_planilla\'])) {
                    try {
                        $tipoQuery = $db->prepare("SELECT nombre FROM tipo_planilla WHERE id_tipo_planilla = :id");
                        $tipoQuery->bindParam(\':id\', $planilla[\'id_tipo_planilla\'], PDO::PARAM_INT);
                        $tipoQuery->execute();
                        $tipoData = $tipoQuery->fetch(PDO::FETCH_ASSOC);
                        echo $tipoData ? $tipoData[\'nombre\'] : \'N/A\';
                    } catch (Exception $e) {
                        echo \'N/A\';
                    }
                } else {
                    echo \'N/A\';
                }
            ?>';
            
            $contenidoVer = str_replace($patronVer, $reemplazoVer, $contenidoVer);
            file_put_contents($rutaArchivoVer, $contenidoVer);
            echo "<p>Se ha actualizado el archivo ver.php para mostrar correctamente el tipo de planilla.</p>";
        }
        
        // Actualizar editar.php si existe
        $rutaArchivoEditar = __DIR__ . '/pages/planillas/editar.php';
        if (file_exists($rutaArchivoEditar)) {
            $contenidoEditar = file_get_contents($rutaArchivoEditar);
            
            // Buscar y reemplazar la forma en que se muestra el tipo de planilla
            $patronEditar = '<p><strong>Tipo:</strong> <?php echo htmlspecialchars($planilla[\'tipo_planilla\'] ?? \'N/A\'); ?></p>';
            $reemplazoEditar = '<p><strong>Tipo:</strong> <?php 
                // Obtener nombre del tipo de planilla
                if (isset($planilla[\'id_tipo_planilla\'])) {
                    try {
                        $tipoQuery = $db->prepare("SELECT nombre FROM tipo_planilla WHERE id_tipo_planilla = :id");
                        $tipoQuery->bindParam(\':id\', $planilla[\'id_tipo_planilla\'], PDO::PARAM_INT);
                        $tipoQuery->execute();
                        $tipoData = $tipoQuery->fetch(PDO::FETCH_ASSOC);
                        echo htmlspecialchars($tipoData ? $tipoData[\'nombre\'] : \'N/A\');
                    } catch (Exception $e) {
                        echo htmlspecialchars(\'N/A\');
                    }
                } else {
                    echo htmlspecialchars(\'N/A\');
                }
            ?></p>';
            
            $contenidoEditar = str_replace($patronEditar, $reemplazoEditar, $contenidoEditar);
            file_put_contents($rutaArchivoEditar, $contenidoEditar);
            echo "<p>Se ha actualizado el archivo editar.php para mostrar correctamente el tipo de planilla.</p>";
        }
    } else {
        echo "<p>Error: No se encontró el archivo lista.php en la ruta " . $rutaArchivo . "</p>";
    }
    
    $db->commit();
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>
            <h3>Proceso completado exitosamente</h3>
            <p>Las consultas han sido actualizadas para usar la nueva estructura de tipo_planilla.</p>
          </div>";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>
            <h3>Error al actualizar las consultas</h3>
            <p>" . $e->getMessage() . "</p>
          </div>";
}
?> 