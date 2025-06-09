<?php

// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_CONTABILIDAD))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar que se haya enviado el ID de la planilla
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_planilla'])) {
    setFlashMessage('Solicitud inválida', 'danger');
    header('Location: ' . BASE_URL . '?page=planillas/lista');
    exit;
}

$id_planilla = intval($_POST['id_planilla']);

if ($id_planilla <= 0) {
    setFlashMessage('ID de planilla inválido', 'danger');
    header('Location: ' . BASE_URL . '?page=planillas/lista');
    exit;
}

// Verificar que la planilla exista y esté en estado borrador
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
    
    if ($planilla['estado'] != 'Borrador') {
        setFlashMessage('Solo se pueden anular planillas en estado borrador', 'warning');
        header('Location: ' . BASE_URL . '?page=planillas/ver&id=' . $id_planilla);
        exit;
    }
    
    // Anular la planilla (cambiar el estado a Anulada)
    $queryUpdate = "UPDATE planillas SET 
                   estado = 'Anulada', 
                   fecha_anulacion = NOW(),
                   anulado_por = :anulado_por
                   WHERE id_planilla = :id_planilla";
    
    $stmtUpdate = $db->prepare($queryUpdate);
    $stmtUpdate->bindParam(':anulado_por', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmtUpdate->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
    
    if ($stmtUpdate->execute()) {
        // Registrar la acción en el historial
        try {
            $queryHistorial = "INSERT INTO historial (accion, descripcion, tipo_entidad, id_entidad, usuario_id, fecha)
                              VALUES ('Anulación de planilla', 'Se anuló la planilla #" . $id_planilla . "', 'planillas', :id_planilla, :usuario_id, NOW())";
            
            $stmtHistorial = $db->prepare($queryHistorial);
            $stmtHistorial->bindParam(':id_planilla', $id_planilla, PDO::PARAM_INT);
            $stmtHistorial->bindParam(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmtHistorial->execute();
        } catch (Exception $e) {
            // Si hay error en el registro del historial, ignoramos para no interrumpir flujo
        }
        
        setFlashMessage('Planilla anulada correctamente', 'success');
    } else {
        setFlashMessage('Error al anular la planilla', 'danger');
    }
    
} catch (Exception $e) {
    setFlashMessage('Error: ' . $e->getMessage(), 'danger');
}

// Redireccionar a la vista de la planilla
header('Location: ' . BASE_URL . '?page=planillas/ver&id=' . $id_planilla);
exit; 