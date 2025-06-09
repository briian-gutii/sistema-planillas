<?php

// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar que se haya enviado el ID de la planilla
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_planilla']) || !isset($_POST['fecha_pago'])) {
    setFlashMessage('Solicitud inválida', 'danger');
    header('Location: ' . BASE_URL . '?page=planillas/lista');
    exit;
}

$id_planilla = intval($_POST['id_planilla']);
$fecha_pago = trim($_POST['fecha_pago']);
$referencia_pago = trim($_POST['referencia_pago'] ?? '');

if ($id_planilla <= 0) {
    setFlashMessage('ID de planilla inválido', 'danger');
    header('Location: ' . BASE_URL . '?page=planillas/lista');
    exit;
}

if (empty($fecha_pago)) {
    setFlashMessage('La fecha de pago es obligatoria', 'danger');
    header('Location: ' . BASE_URL . '?page=planillas/ver&id=' . $id_planilla);
    exit;
}

// Verificar formato de fecha
$fecha = DateTime::createFromFormat('Y-m-d', $fecha_pago);
if (!$fecha || $fecha->format('Y-m-d') != $fecha_pago) {
    setFlashMessage('Formato de fecha de pago inválido', 'danger');
    header('Location: ' . BASE_URL . '?page=planillas/ver&id=' . $id_planilla);
    exit;
}

// Verificar que la planilla exista y esté en estado aprobada
$db = getDB();
try {
    $query = "SELECT * FROM planillas WHERE id_planilla = :id_planilla";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage('La planilla especificada no existe', 'danger');
        header('Location: ' . BASE_URL . '?page=planillas/lista');
        exit;
    }
    
    $planilla = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($planilla['estado'] != 'Aprobada') {
        setFlashMessage('Solo se pueden pagar planillas en estado aprobada', 'warning');
        header('Location: ' . BASE_URL . '?page=planillas/ver&id=' . $id_planilla);
        exit;
    }
    
    // Marcar la planilla como pagada
    $queryUpdate = "UPDATE planillas SET 
                   estado = 'Pagada', 
                   fecha_pago = :fecha_pago,
                   referencia_pago = :referencia_pago,
                   usuario_pago = :usuario_pago
                   WHERE id_planilla = :id_planilla";
    
    $stmtUpdate = $db->prepare($queryUpdate);
    $stmtUpdate->bindParam(':fecha_pago', $fecha_pago);
    $stmtUpdate->bindParam(':referencia_pago', $referencia_pago);
    $stmtUpdate->bindParam(':usuario_pago', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmtUpdate->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    
    if ($stmtUpdate->execute()) {
        // Registrar la acción en el historial
        try {
            $queryHistorial = "INSERT INTO historial (accion, descripcion, tipo_entidad, id_entidad, usuario_id, fecha)
                              VALUES ('Pago de planilla', 'Se registró el pago de la planilla #" . $id_planilla . " con fecha " . $fecha_pago . "', 'planillas', :id_planilla, :usuario_id, NOW())";
            
            $stmtHistorial = $db->prepare($queryHistorial);
            $stmtHistorial->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
            $stmtHistorial->bindParam(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmtHistorial->execute();
        } catch (Exception $e) {
            // Si hay error en el registro del historial, ignoramos para no interrumpir flujo
        }
        
        setFlashMessage('Pago de planilla registrado correctamente', 'success');
    } else {
        setFlashMessage('Error al registrar el pago de la planilla', 'danger');
    }
    
} catch (Exception $e) {
    setFlashMessage('Error: ' . $e->getMessage(), 'danger');
}

// Redireccionar a la vista de la planilla
header('Location: ' . BASE_URL . '?page=planillas/ver&id=' . $id_planilla);
exit; 