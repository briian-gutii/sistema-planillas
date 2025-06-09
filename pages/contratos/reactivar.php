<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('Método de solicitud no válido', 'danger');
    header('Location: ' . BASE_URL . '?page=contratos/lista');
    exit;
}

// Verificar que el ID de contrato exista
if (!isset($_POST['id_contrato']) || empty($_POST['id_contrato'])) {
    setFlashMessage('ID de contrato no proporcionado', 'danger');
    header('Location: ' . BASE_URL . '?page=contratos/lista');
    exit;
}

$idContrato = filter_var($_POST['id_contrato'], FILTER_VALIDATE_INT);
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Verificar si el contrato existe y está finalizado
    $query = "SELECT * FROM contratos WHERE id_contrato = :id_contrato";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_contrato', $idContrato, PDO::PARAM_INT);
    $stmt->execute();
    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contrato) {
        throw new Exception("El contrato no existe.");
    }
    
    if (($contrato['estado'] ?? 1) == 1) {
        throw new Exception("El contrato ya está activo.");
    }
    
    // Actualizar el contrato a estado activo y eliminar la fecha de fin
    $query = "UPDATE contratos SET 
                estado = 1, 
                fecha_fin = NULL,
                motivo_fin = NULL,
                updated_at = NOW()
              WHERE id_contrato = :id_contrato";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_contrato', $idContrato, PDO::PARAM_INT);
    $stmt->execute();
    
    // Registrar en la bitácora
    $detalles = "Contrato #$idContrato reactivado. ";
    if (!empty($observaciones)) {
        $detalles .= "Observaciones: $observaciones";
    }
    
    $query = "INSERT INTO bitacora (id_usuario, accion, detalles, created_at)
              VALUES (:id_usuario, 'Reactivación de contrato', :detalles, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_usuario', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':detalles', $detalles);
    $stmt->execute();
    
    $db->commit();
    setFlashMessage('Contrato reactivado exitosamente', 'success');
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    setFlashMessage('Error al reactivar el contrato: ' . $e->getMessage(), 'danger');
}

// Redirigir a la página de lista de contratos
header('Location: ' . BASE_URL . '?page=contratos/lista');
exit;
?> 