<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/escapar.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $conexion->query('SELECT id, nombre, apellido, dni, email, rol, archivado_en FROM usuarios WHERE archivado_en IS NOT NULL ORDER BY archivado_en DESC');
$archivados = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RiverID | Usuarios Archivados</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="bg-dark">
    <header class="navbar-river">
        <div class="nav-brand">RIVER<span>ID</span> | CUENTAS ARCHIVADAS</div>
        <nav class="nav-links">
            <a href="admin_usuarios.php">Usuarios Activos</a>
            <a href="usuarios_archivados.php" class="active">Usuarios Archivados</a>
            <a href="cerrar_sesion.php" class="btn-salir">Cerrar sesión</a>
        </nav>
    </header>

    <main class="container-principal">
        <section class="card-river">
            <div class="card-header-banda">
                <h2>USUARIOS ARCHIVADOS (<?= count($archivados) ?>)</h2>
            </div>

            <?php if (empty($archivados)): ?>
                <p class="texto-intro">No hay cuentas archivadas actualmente.</p>
            <?php else: ?>
                <div class="tabla-responsive">
                    <table class="tabla-river">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>DNI</th>
                                <th>Correo</th>
                                <th>Fecha Archivada</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archivados as $u): ?>
                                <tr>
                                    <td><?= escapar((string)$u['id']) ?></td>
                                    <td><?= escapar($u['nombre']) ?></td>
                                    <td><?= escapar($u['apellido']) ?></td>
                                    <td><?= escapar($u['dni']) ?></td>
                                    <td><?= escapar($u['email']) ?></td>
                                    <td><?= escapar($u['archivado_en']) ?></td>
                                    <td class="acciones-celda">
                                        <form action="reactivar_usuario.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Reactivar esta cuenta?');">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= escapar($_SESSION['csrf_token']) ?>">
                                            <button type="submit" class="btn-accion btn-reactivar">Reactivar</button>
                                        </form>

                                        <form action="eliminar_usuario.php" method="POST" style="display:inline;" onsubmit="return confirm('ATENCIÓN: Esta acción eliminará al usuario definitivamente de la base de datos. ¿Continuar?');">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= escapar($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="origen" value="archivados">
                                            <button type="submit" class="btn-accion btn-eliminar">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>