<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/escapar.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Identificador inválido.');
}

$errores = [];
$mensaje = '';

// Cargar datos actuales del usuario activo[cite: 18]
$stmt = $conexion->prepare('SELECT id, nombre, apellido, dni, email, rol FROM usuarios WHERE id = :id AND archivado_en IS NULL LIMIT 1');
$stmt->execute(['id' => $id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    exit('Usuario no encontrado o se encuentra archivado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if ($apellido === '') $errores[] = 'El apellido es obligatorio.';
    if (!preg_match('/^[0-9]{7,8}$/', $dni)) $errores[] = 'El DNI debe tener 7 u 8 números.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo electrónico no es válido.';

    if (empty($errores)) {
        // Comprobar que el DNI o Email no pertenezcan a OTRO usuario[cite: 19]
        $stmtDup = $conexion->prepare('SELECT id FROM usuarios WHERE (dni = :dni OR email = :email) AND id <> :id LIMIT 1');
        $stmtDup->execute(['dni' => $dni, 'email' => $email, 'id' => $id]);

        if ($stmtDup->fetch()) {
            $errores[] = 'El DNI o correo electrónico ya está en uso por otro usuario.';
        } else {
            $sqlUpdate = 'UPDATE usuarios 
                          SET nombre = :nombre, apellido = :apellido, dni = :dni, email = :email 
                          WHERE id = :id AND archivado_en IS NULL';
            $stmtUp = $conexion->prepare($sqlUpdate);
            $stmtUp->execute([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'dni' => $dni,
                'email' => $email,
                'id' => $id
            ]);

            $mensaje = 'Datos actualizados correctamente.';
            $usuario['nombre'] = $nombre;
            $usuario['apellido'] = $apellido;
            $usuario['dni'] = $dni;
            $usuario['email'] = $email;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RiverID | Editar Usuario</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="bg-dark">
    <header class="navbar-river">
        <div class="nav-brand">RIVER<span>ID</span> | EDITAR USUARIO</div>
        <nav class="nav-links">
            <a href="admin_usuarios.php">← Volver al listado</a>
        </nav>
    </header>

    <main class="container-principal">
        <section class="card-river" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header-banda">
                <h2>EDITAR SOCIO #<?= escapar((string)$usuario['id']) ?></h2>
            </div>

            <?php if ($mensaje !== ''): ?>
                <div class="alerta alerta-exito"><p><?= escapar($mensaje) ?></p></div>
            <?php endif; ?>
            <?php if (!empty($errores)): ?>
                <div class="alerta alerta-error">
                    <?php foreach ($errores as $e): ?><p><?= escapar($e) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="editar_usuario.php?id=<?= (int)$usuario['id'] ?>" method="POST" class="form-river">
                <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">

                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="<?= escapar($usuario['nombre']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="apellido">Apellido</label>
                    <input type="text" id="apellido" name="apellido" value="<?= escapar($usuario['apellido']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="dni">DNI</label>
                    <input type="text" id="dni" name="dni" pattern="[0-9]{7,8}" maxlength="8" value="<?= escapar($usuario['dni']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" value="<?= escapar($usuario['email']) ?>" required>
                </div>

                <button type="submit" class="btn-river-rojo">GUARDAR CAMBIOS</button>
            </form>
        </section>
    </main>
</body>
</html>