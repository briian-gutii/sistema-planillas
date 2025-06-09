<?php
// Verificar si hay sesión activa y permisos
if (!isset($_SESSION['user_id']) || (!hasPermission(ROL_ADMIN) && !hasPermission(ROL_RRHH))) {
    setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar si se recibió el ID de la ausencia
if (!isset($_POST['id_ausencia']) || empty($_POST['id_ausencia'])) {
    setFlashMessage('ID de ausencia no especificado', 'danger');
    header('Location: ' . BASE_URL . '?page=ausencias/lista');
    exit;
}

$id_ausencia = intval($_POST['id_ausencia']);

// Verificar si la ausencia existe
$db = getDB();
$query = "SELECT id_ausencia FROM ausencias WHERE id_ausencia = :id_ausencia";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_ausencia', $id_ausencia, PDO::PARAM_INT);
$stmt->execute();

if (!$stmt->fetch()) {
    setFlashMessage('La ausencia que intenta eliminar no existe', 'danger');
    header('Location: ' . BASE_URL . '?page=ausencias/lista');
    exit;
}

// Eliminar la ausencia
try {
    $queryDelete = "DELETE FROM ausencias WHERE id_ausencia = :id_ausencia";
    $stmtDelete = $db->prepare($queryDelete);
    $stmtDelete->bindParam(':id_ausencia', $id_ausencia, PDO::PARAM_INT);
    
    if ($stmtDelete->execute()) {
        setFlashMessage('Ausencia eliminada correctamente', 'success');
    } else {
        setFlashMessage('Error al eliminar la ausencia', 'danger');
    }
} catch (PDOException $e) {
    setFlashMessage('Error en la base de datos: ' . $e->getMessage(), 'danger');
}

// Redireccionar a la lista de ausencias
header('Location: ' . BASE_URL . '?page=ausencias/lista');
exit;
?> 