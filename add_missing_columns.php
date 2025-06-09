<?php
require_once 'config/database.php';

// Habilitar reportes de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Agregar Columnas Faltantes a Tabla de Empleados</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // 1. Verificar si las columnas ya existen
    $columnsQuery = "SHOW COLUMNS FROM empleados";
    $columnsResult = $db->query($columnsQuery);
    $columns = $columnsResult->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $needsDepartamento = !in_array('id_departamento', $columns);
    $needsPuesto = !in_array('id_puesto', $columns);
    
    if (!$needsDepartamento && !$needsPuesto) {
        echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
              ✓ Las columnas 'id_departamento' e 'id_puesto' ya existen en la tabla empleados
              </div>";
        exit;
    }
    
    // 2. Agregar las columnas faltantes
    $columnsAdded = [];
    
    if ($needsDepartamento) {
        try {
            $alterQuery = "ALTER TABLE empleados ADD COLUMN id_departamento INT NULL AFTER primer_apellido";
            $db->exec($alterQuery);
            $columnsAdded[] = 'id_departamento';
            
            echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                  ✓ Columna 'id_departamento' agregada correctamente
                  </div>";
        } catch (Exception $e) {
            echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                  Error al agregar columna 'id_departamento': " . $e->getMessage() . "
                  </div>";
        }
    }
    
    if ($needsPuesto) {
        try {
            $alterQuery = "ALTER TABLE empleados ADD COLUMN id_puesto INT NULL AFTER " . 
                         ($needsDepartamento ? "id_departamento" : "primer_apellido");
            $db->exec($alterQuery);
            $columnsAdded[] = 'id_puesto';
            
            echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                  ✓ Columna 'id_puesto' agregada correctamente
                  </div>";
        } catch (Exception $e) {
            echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                  Error al agregar columna 'id_puesto': " . $e->getMessage() . "
                  </div>";
        }
    }
    
    if (empty($columnsAdded)) {
        echo "<div style='color: yellow; padding: 10px; border: 1px solid yellow;'>
              No se realizaron cambios en la estructura de la tabla
              </div>";
        exit;
    }
    
    // 3. Obtener departamentos disponibles
    echo "<h2>Configuración de departamentos y puestos</h2>";
    
    // Verificar si la tabla departamentos existe
    $deptExists = false;
    try {
        $checkDept = $db->query("SHOW TABLES LIKE 'departamentos'");
        $deptExists = ($checkDept && $checkDept->rowCount() > 0);
    } catch (Exception $e) {
        $deptExists = false;
    }
    
    if ($deptExists) {
        try {
            $deptsQuery = "SELECT id_departamento, nombre FROM departamentos ORDER BY nombre";
            $deptsStmt = $db->query($deptsQuery);
            $departamentos = $deptsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($departamentos) > 0) {
                echo "<h3>Departamentos disponibles:</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Nombre</th></tr>";
                
                foreach ($departamentos as $dept) {
                    echo "<tr>";
                    echo "<td>" . $dept['id_departamento'] . "</td>";
                    echo "<td>" . $dept['nombre'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No hay departamentos disponibles. Debe crear departamentos primero.</p>";
                
                // Ofrecer crear un departamento predeterminado
                echo "<form method='post' action=''>";
                echo "<input type='hidden' name='action' value='create_default_dept'>";
                echo "<button type='submit' class='btn btn-primary'>Crear departamento predeterminado</button>";
                echo "</form>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error al obtener departamentos: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
              La tabla 'departamentos' no existe. Debe crearla primero.
              </div>";
        
        // Ofrecer crear la tabla
        echo "<form method='post' action=''>";
        echo "<input type='hidden' name='action' value='create_dept_table'>";
        echo "<button type='submit' class='btn btn-primary'>Crear tabla de departamentos</button>";
        echo "</form>";
    }
    
    // 4. Obtener puestos disponibles
    $puestoExists = false;
    try {
        $checkPuesto = $db->query("SHOW TABLES LIKE 'puestos'");
        $puestoExists = ($checkPuesto && $checkPuesto->rowCount() > 0);
    } catch (Exception $e) {
        $puestoExists = false;
    }
    
    if ($puestoExists) {
        try {
            $puestosQuery = "SELECT id_puesto, nombre FROM puestos ORDER BY nombre";
            $puestosStmt = $db->query($puestosQuery);
            $puestos = $puestosStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($puestos) > 0) {
                echo "<h3>Puestos disponibles:</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Nombre</th></tr>";
                
                foreach ($puestos as $puesto) {
                    echo "<tr>";
                    echo "<td>" . $puesto['id_puesto'] . "</td>";
                    echo "<td>" . $puesto['nombre'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No hay puestos disponibles. Debe crear puestos primero.</p>";
                
                // Ofrecer crear un puesto predeterminado
                echo "<form method='post' action=''>";
                echo "<input type='hidden' name='action' value='create_default_puesto'>";
                echo "<button type='submit' class='btn btn-primary'>Crear puesto predeterminado</button>";
                echo "</form>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error al obtener puestos: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
              La tabla 'puestos' no existe. Debe crearla primero.
              </div>";
        
        // Ofrecer crear la tabla
        echo "<form method='post' action=''>";
        echo "<input type='hidden' name='action' value='create_puesto_table'>";
        echo "<button type='submit' class='btn btn-primary'>Crear tabla de puestos</button>";
        echo "</form>";
    }
    
    // 5. Formulario para asignar valores a los empleados
    echo "<h2>Asignar valores a los empleados existentes</h2>";
    
    // Obtener empleados
    $empleadosQuery = "SELECT id_empleado, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido FROM empleados ORDER BY primer_apellido, primer_nombre";
    $empleadosStmt = $db->query($empleadosQuery);
    $empleados = $empleadosStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($empleados) > 0) {
        if ($deptExists && $puestoExists) {
            echo "<form method='post' action=''>";
            echo "<input type='hidden' name='action' value='assign_values'>";
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Departamento</th>
                    <th>Puesto</th>
                 </tr>";
            
            foreach ($empleados as $empleado) {
                echo "<tr>";
                echo "<td>" . $empleado['id_empleado'] . "</td>";
                echo "<td>" . $empleado['primer_nombre'] . " " . $empleado['primer_apellido'] . "</td>";
                
                // Departamento
                echo "<td>";
                if (in_array('id_departamento', $columnsAdded) || in_array('id_departamento', $columns)) {
                    echo "<select name='dept_" . $empleado['id_empleado'] . "'>";
                    echo "<option value=''>-- Seleccione --</option>";
                    
                    if (isset($departamentos)) {
                        foreach ($departamentos as $dept) {
                            echo "<option value='" . $dept['id_departamento'] . "'>" . $dept['nombre'] . "</option>";
                        }
                    }
                    
                    echo "</select>";
                } else {
                    echo "N/A";
                }
                echo "</td>";
                
                // Puesto
                echo "<td>";
                if (in_array('id_puesto', $columnsAdded) || in_array('id_puesto', $columns)) {
                    echo "<select name='puesto_" . $empleado['id_empleado'] . "'>";
                    echo "<option value=''>-- Seleccione --</option>";
                    
                    if (isset($puestos)) {
                        foreach ($puestos as $puesto) {
                            echo "<option value='" . $puesto['id_puesto'] . "'>" . $puesto['nombre'] . "</option>";
                        }
                    }
                    
                    echo "</select>";
                } else {
                    echo "N/A";
                }
                echo "</td>";
                
                echo "</tr>";
            }
            
            echo "</table>";
            
            echo "<p><button type='submit' class='btn btn-success'>Guardar Cambios</button></p>";
            echo "</form>";
        } else {
            echo "<p>Debe crear las tablas de departamentos y puestos antes de asignar valores.</p>";
        }
    } else {
        echo "<p>No hay empleados registrados en el sistema.</p>";
    }
    
    // 6. Procesar acciones POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Crear tabla de departamentos
        if ($action === 'create_dept_table') {
            try {
                $createDeptQuery = "CREATE TABLE departamentos (
                                    id_departamento INT AUTO_INCREMENT PRIMARY KEY,
                                    nombre VARCHAR(100) NOT NULL,
                                    descripcion TEXT,
                                    estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo'
                                  )";
                $db->exec($createDeptQuery);
                
                // Insertar departamento predeterminado
                $insertDeptQuery = "INSERT INTO departamentos (nombre, descripcion) VALUES ('Administración', 'Departamento administrativo')";
                $db->exec($insertDeptQuery);
                
                echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                      ✓ Tabla de departamentos creada exitosamente
                      </div>";
                
                echo "<meta http-equiv='refresh' content='2;url=" . $_SERVER['PHP_SELF'] . "'>";
            } catch (Exception $e) {
                echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                      Error al crear tabla de departamentos: " . $e->getMessage() . "
                      </div>";
            }
        }
        
        // Crear departamento predeterminado
        else if ($action === 'create_default_dept') {
            try {
                $insertDeptQuery = "INSERT INTO departamentos (nombre, descripcion) VALUES ('Administración', 'Departamento administrativo')";
                $db->exec($insertDeptQuery);
                
                echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                      ✓ Departamento predeterminado creado exitosamente
                      </div>";
                
                echo "<meta http-equiv='refresh' content='2;url=" . $_SERVER['PHP_SELF'] . "'>";
            } catch (Exception $e) {
                echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                      Error al crear departamento predeterminado: " . $e->getMessage() . "
                      </div>";
            }
        }
        
        // Crear tabla de puestos
        else if ($action === 'create_puesto_table') {
            try {
                $createPuestoQuery = "CREATE TABLE puestos (
                                      id_puesto INT AUTO_INCREMENT PRIMARY KEY,
                                      nombre VARCHAR(100) NOT NULL,
                                      descripcion TEXT,
                                      estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo'
                                    )";
                $db->exec($createPuestoQuery);
                
                // Insertar puesto predeterminado
                $insertPuestoQuery = "INSERT INTO puestos (nombre, descripcion) VALUES ('Asistente', 'Puesto asistencial')";
                $db->exec($insertPuestoQuery);
                
                echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                      ✓ Tabla de puestos creada exitosamente
                      </div>";
                
                echo "<meta http-equiv='refresh' content='2;url=" . $_SERVER['PHP_SELF'] . "'>";
            } catch (Exception $e) {
                echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                      Error al crear tabla de puestos: " . $e->getMessage() . "
                      </div>";
            }
        }
        
        // Crear puesto predeterminado
        else if ($action === 'create_default_puesto') {
            try {
                $insertPuestoQuery = "INSERT INTO puestos (nombre, descripcion) VALUES ('Asistente', 'Puesto asistencial')";
                $db->exec($insertPuestoQuery);
                
                echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                      ✓ Puesto predeterminado creado exitosamente
                      </div>";
                
                echo "<meta http-equiv='refresh' content='2;url=" . $_SERVER['PHP_SELF'] . "'>";
            } catch (Exception $e) {
                echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                      Error al crear puesto predeterminado: " . $e->getMessage() . "
                      </div>";
            }
        }
        
        // Asignar valores a los empleados
        else if ($action === 'assign_values') {
            try {
                $updateCount = 0;
                
                foreach ($empleados as $empleado) {
                    $id = $empleado['id_empleado'];
                    $updateFields = [];
                    $updateParams = [];
                    
                    // Departamento
                    if (isset($_POST['dept_' . $id]) && $_POST['dept_' . $id] !== '') {
                        $updateFields[] = "id_departamento = :dept_" . $id;
                        $updateParams[':dept_' . $id] = $_POST['dept_' . $id];
                    }
                    
                    // Puesto
                    if (isset($_POST['puesto_' . $id]) && $_POST['puesto_' . $id] !== '') {
                        $updateFields[] = "id_puesto = :puesto_" . $id;
                        $updateParams[':puesto_' . $id] = $_POST['puesto_' . $id];
                    }
                    
                    if (!empty($updateFields)) {
                        $updateQuery = "UPDATE empleados SET " . implode(", ", $updateFields) . " WHERE id_empleado = :id_empleado";
                        $updateParams[':id_empleado'] = $id;
                        
                        $updateStmt = $db->prepare($updateQuery);
                        $updateStmt->execute($updateParams);
                        
                        if ($updateStmt->rowCount() > 0) {
                            $updateCount++;
                        }
                    }
                }
                
                echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
                      ✓ Se actualizaron $updateCount empleados exitosamente
                      </div>";
                
                echo "<p><a href='fix_planilla_query.php' class='btn btn-primary'>Verificar consulta de planilla</a></p>";
            } catch (Exception $e) {
                echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
                      Error al actualizar empleados: " . $e->getMessage() . "
                      </div>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
          Error general: " . $e->getMessage() . "
          </div>";
}
?> 