<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/escapar.php';

// Control de acceso: solo administradores
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado. Se requieren permisos de administrador.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errores = [];
$mensaje = '';

// 1. Procesar alta de nuevo usuario desde el panel administrativo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = ($_POST['rol'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';

    if ($nombre === '' || $apellido === '') {
        $errores[] = 'Nombre y apellido son obligatorios.';
    }
    if (!preg_match('/^[0-9]{7,8}$/', $dni)) {
        $errores[] = 'El DNI debe tener 7 u 8 dígitos.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }
    if (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    if (empty($errores)) {
        $stmtCheck = $conexion->prepare('SELECT id FROM usuarios WHERE dni = :dni OR email = :email LIMIT 1');
        $stmtCheck->execute(['dni' => $dni, 'email' => $email]);

        if ($stmtCheck->fetch()) {
            $errores[] = 'Ya existe un usuario con ese DNI o correo electrónico.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtInsert = $conexion->prepare('INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol) VALUES (:nombre, :apellido, :dni, :email, :password_hash, :rol)');
            $stmtInsert->execute([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'dni' => $dni,
                'email' => $email,
                'password_hash' => $hash,
                'rol' => $rol
            ]);
            $mensaje = 'Usuario registrado correctamente desde el panel administrativo.';
        }
    }
}

// 2. Buscador de usuarios activos
$buscar = trim($_GET['buscar'] ?? '');
$sql = 'SELECT id, nombre, apellido, dni, email, rol, creado_en 
        FROM usuarios 
        WHERE archivado_en IS NULL';
$parametros = [];

if ($buscar !== '') {
    $sql .= ' AND (nombre LIKE :nombre OR apellido LIKE :apellido OR dni LIKE :dni OR email LIKE :email)';
    $termino = '%' . $buscar . '%';
    $parametros = [
        'nombre' => $termino,
        'apellido' => $termino,
        'dni' => $termino,
        'email' => $termino
    ];
}

$sql .= ' ORDER BY id DESC';
$stmt = $conexion->prepare($sql);
$stmt->execute($parametros);
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RiverID | Administración de Usuarios</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="bg-dark">
    <header class="navbar-river">
        <div class="nav-brand">RIVER<span>ID</span> | PANEL ADMINISTRATIVO</div>
        <nav class="nav-links">
            <a href="admin_usuarios.php" class="active">Usuarios Activos</a>
            <a href="usuarios_archivados.php">Usuarios Archivados</a>
            <a href="panel.php">Vista Socio</a>
            <a href="cerrar_sesion.php" class="btn-salir">Cerrar sesión</a>
        </nav>
    </header>

    <main class="container-principal">
        <?php if ($mensaje !== ''): ?>
            <div class="alerta alerta-exito"><p><?= escapar($mensaje) ?></p></div>
        <?php endif; ?>
        <?php if (!empty($errores)): ?>
            <div class="alerta alerta-error">
                <?php foreach ($errores as $e): ?><p><?= escapar($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de Alta desde Admin -->
        <section class="card-river">
            <div class="card-header-banda">
                <h2>REGISTRAR NUEVO USUARIO / ADMINISTRADOR</h2>
            </div>
            <form action="admin_usuarios.php" method="POST" class="form-admin-grid">
                <input type="hidden" name="accion" value="crear">
                <input type="text" name="nombre" placeholder="Nombre" maxlength="80" required>
                <input type="text" name="apellido" placeholder="Apellido" maxlength="80" required>
                <input type="text" name="dni" placeholder="DNI (7-8 números)" pattern="[0-9]{7,8}" maxlength="8" required>
                <input type="email" name="email" placeholder="Correo electrónico" maxlength="254" required>
                <input type="password" name="password" placeholder="Contraseña (mín. 6)" minlength="6" required>
                <select name="rol">
                    <option value="usuario">Rol: Usuario</option>
                    <option value="admin">Rol: Admin</option>
                </select>
                <button type="submit" class="btn-river-rojo">Registrar</button>
            </form>
        </section>

        <!-- Buscador y Listado Activo -->
        <section class="card-river">
            <div class="card-header-banda">
                <h2>PADRON DE USUARIOS ACTIVOS (<?= count($usuarios) ?>)</h2>
            </div>

            <form method="GET" action="admin_usuarios.php" class="form-buscador">
                <input type="search" name="buscar" maxlength="100" placeholder="Buscar por nombre, apellido, DNI o correo..." value="<?= escapar($buscar) ?>">
                <button type="submit" class="btn-river-rojo">Buscar</button>
                <?php if ($buscar !== ''): ?>
                    <a href="admin_usuarios.php" class="btn-secundario">Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if (empty($usuarios)): ?>
                <p class="texto-intro">No se encontraron usuarios activos.</p>
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
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td><?= escapar((string)$u['id']) ?></td>
                                    <td><?= escapar($u['nombre']) ?></td>
                                    <td><?= escapar($u['apellido']) ?></td>
                                    <td><?= escapar($u['dni']) ?></td>
                                    <td><?= escapar($u['email']) ?></td>
                                    <td><span class="badge-rol <?= $u['rol'] === 'admin' ? 'rol-admin' : '' ?>"><?= escapar($u['rol']) ?></span></td>
                                    <td class="acciones-celda">
                                        <a href="editar_usuario.php?id=<?= (int)$u['id'] ?>" class="btn-accion btn-editar">Editar</a>
                                        
                                        <?php if ((int)$u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                            <form action="archivar_usuario.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que querés archivar esta cuenta?');">
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= escapar($_SESSION['csrf_token']) ?>">
                                                <button type="submit" class="btn-accion btn-archivar">Archivar</button>
                                            </form>

                                            <form action="eliminar_usuario.php" method="POST" style="display:inline;" onsubmit="return confirm('ATENCIÓN: Esta acción eliminará al usuario definitivamente de la base de datos. ¿Continuar?');">
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= escapar($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="origen" value="activos">
                                                <button type="submit" class="btn-accion btn-eliminar">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
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