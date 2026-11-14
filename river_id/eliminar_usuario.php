<?php
session_start();
require_once __DIR__ . '/conexion.php';

// Verificar sesión y rol de administrador
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

// Exigir método POST (nunca DELETE por GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

// Validar token CSRF
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    exit('Token de seguridad inválido.');
}

// Validar ID y evitar que el admin se elimine a sí mismo
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1 || $id === (int)$_SESSION['usuario_id']) {
    http_response_code(400);
    exit('Operación inválida.');
}

// Borrado definitivo en MySQL con consulta preparada
$stmt = $conexion->prepare('DELETE FROM usuarios WHERE id = :id');
$stmt->execute(['id' => $id]);

// Volver a la página desde donde se ejecutó la acción
$origen = ($_POST['origen'] ?? '') === 'archivados' ? 'usuarios_archivados.php' : 'admin_usuarios.php';
header('Location: ' . $origen);
exit;
?>