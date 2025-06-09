<?php
// Funciones generales para el sistema de planillas

/**
 * Verifica si una tabla existe en la base de datos
 */
function tableExists($tableName) {
    try {
        $result = fetchRow("SHOW TABLES LIKE :tableName", [':tableName' => $tableName]);
        return !empty($result);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Valida si un usuario tiene permisos para acceder a una sección
 */
function hasPermission($requiredRole) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    if ($_SESSION['user_role'] == ROL_ADMIN) {
        return true; // El administrador tiene acceso a todo
    }
    
    // Definir jerarquía de roles
    $roleHierarchy = [
        ROL_ADMIN => 5,
        ROL_GERENCIA => 4,
        ROL_CONTABILIDAD => 3,
        ROL_RRHH => 2,
        ROL_CONSULTA => 1
    ];
    
    $userRoleLevel = $roleHierarchy[$_SESSION['user_role']] ?? 0;
    $requiredRoleLevel = $roleHierarchy[$requiredRole] ?? 0;
    
    return $userRoleLevel >= $requiredRoleLevel;
}

/**
 * Formatea una fecha de MySQL (YYYY-MM-DD) a formato localizado
 */
function formatDate($mysqlDate, $format = 'd/m/Y') {
    if (!$mysqlDate || $mysqlDate == '0000-00-00') {
        return '';
    }
    $date = new DateTime($mysqlDate);
    return $date->format($format);
}

/**
 * Formatea un número como moneda (Q)
 */
function formatMoney($amount) {
    return 'Q ' . number_format($amount, 2, '.', ',');
}

/**
 * Sanea input para prevenir XSS
 */
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Genera un mensaje flash para mostrar en la siguiente solicitud
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtiene y elimina el mensaje flash
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Calcula la edad basada en la fecha de nacimiento
 */
function calcularEdad($fechaNacimiento) {
    $fechaNac = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fechaNac);
    return $edad->y;
}

/**
 * Calcula el IGSS laboral
 */
function calcularIGSSLaboral($sueldoBase) {
    $sql = "SELECT porcentaje_laboral FROM configuracion_igss WHERE activo = 1 ORDER BY fecha_vigencia DESC LIMIT 1";
    $config = fetchRow($sql);
    if (!$config) return 0;
    
    // Solo se calcula sobre el sueldo base, no incluye bonificaciones
    return $sueldoBase * ($config['porcentaje_laboral'] / 100);
}

/**
 * Calcula el IGSS patronal
 */
function calcularIGSSPatronal($sueldoBase) {
    $sql = "SELECT porcentaje_patronal FROM configuracion_igss WHERE activo = 1 ORDER BY fecha_vigencia DESC LIMIT 1";
    $config = fetchRow($sql);
    if (!$config) return 0;
    
    return $sueldoBase * ($config['porcentaje_patronal'] / 100);
}

/**
 * Calcula ISR estimado mensual
 */
function calcularISRMensual($ingresoGravable, $anio) {
    $sql = "SELECT * FROM configuracion_isr WHERE anio <= :anio AND activo = 1 ORDER BY anio DESC, rango_minimo ASC";
    $rangos = fetchAll($sql, [':anio' => $anio]);
    
    if (empty($rangos)) return 0;
    
    // Calcular renta imponible anual estimada
    $rentaImponibleAnual = $ingresoGravable * 12;
    
    $isr = 0;
    foreach ($rangos as $rango) {
        if ($rentaImponibleAnual > $rango['rango_minimo'] && 
            ($rango['rango_maximo'] === null || $rentaImponibleAnual <= $rango['rango_maximo'])) {
            
            if ($rango['importe_fijo'] > 0) {
                // Aplicar importe fijo + porcentaje sobre excedente
                $excedente = $rentaImponibleAnual - $rango['rango_minimo'];
                $isr = $rango['importe_fijo'] + ($excedente * ($rango['porcentaje'] / 100));
            } else {
                // Aplicar solo porcentaje
                $isr = $rentaImponibleAnual * ($rango['porcentaje'] / 100);
            }
            break;
        }
    }
    
    // Devolver el ISR mensual estimado
    return $isr / 12;
}

/**
 * Registra actividad en bitácora del sistema
 */
function registrarBitacora($accion, $tabla = null, $registro = null, $detalles = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $sql = "INSERT INTO bitacora_sistema (id_usuario, fecha_hora, accion, tabla_afectada, registro_afectado, detalles, direccion_ip) 
            VALUES (:id_usuario, NOW(), :accion, :tabla, :registro, :detalles, :ip)";
    
    $params = [
        ':id_usuario' => $_SESSION['user_id'],
        ':accion' => $accion,
        ':tabla' => $tabla,
        ':registro' => $registro,
        ':detalles' => $detalles,
        ':ip' => $ip
    ];
    
    try {
        query($sql, $params);
        return true;
    } catch (PDOException $e) {
        error_log('Error al registrar en bitácora: ' . $e->getMessage());
        return false;
    }
}
?> 