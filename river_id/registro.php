<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/escapar.php';

$errores = [];
$mensaje = '';
$nombre = '';
$apellido = '';
$dni = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if ($apellido === '') {
        $errores[] = 'El apellido es obligatorio.';
    }
    if ($dni === '' || !preg_match('/^[0-9]{7,8}$/', $dni)) {
        $errores[] = 'El DNI debe contener entre 7 y 8 números.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresá un correo electrónico válido.';
    }
    if (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($password !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $stmtCheck = $conexion->prepare('SELECT id FROM usuarios WHERE dni = :dni OR email = :email LIMIT 1');
        $stmtCheck->execute(['dni' => $dni, 'email' => $email]);

        if ($stmtCheck->fetch()) {
            $errores[] = 'Ya existe una cuenta registrada con ese DNI o correo electrónico.';
        } else {
            try {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = 'INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol) 
                        VALUES (:nombre, :apellido, :dni, :email, :password_hash, :rol)';
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'dni' => $dni,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'rol' => 'usuario'
                ]);

                $mensaje = '¡Cuenta creada con éxito! Ya podés iniciar sesión en RiverID.';
                $nombre = $apellido = $dni = $email = '';
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errores[] = 'Ocurrió un error al registrar la cuenta.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RiverID | Crear cuenta</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="bg-estadio">
    <div class="auth-wrapper">
        <div class="auth-form-col">
            <div class="brand-logo">RIVER<span>ID</span></div>
            <h1>Creá tu cuenta oficial</h1>
            <p class="subtitle">Completá tus datos para asociarte:</p>

            <?php if ($mensaje !== ''): ?>
                <div class="alerta alerta-exito">
                    <p><?= escapar($mensaje) ?></p>
                    <p><a href="index.php" class="link-blanco">Ir a iniciar sesión →</a></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errores)): ?>
                <div class="alerta alerta-error">
                    <?php foreach ($errores as $error): ?>
                        <p><?= escapar($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="registro.php" method="POST" class="form-river">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" maxlength="80" value="<?= escapar($nombre) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido</label>
                        <input type="text" id="apellido" name="apellido" maxlength="80" value="<?= escapar($apellido) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="dni">DNI (sin puntos)</label>
                    <input type="text" id="dni" name="dni" inputmode="numeric" pattern="[0-9]{7,8}" maxlength="8" value="<?= escapar($dni) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" maxlength="254" value="<?= escapar($email) ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmar">Confirmar contraseña</label>
                        <input type="password" id="confirmar" name="confirmar" minlength="6" required>
                    </div>
                </div>

                <button type="submit" class="btn-river-rojo">CREAR CUENTA</button>
            </form>

            <p class="auth-link">¿Ya tenés tu RiverID? <a href="index.php">Ingresá acá</a></p>
        </div>

        <div class="auth-banner-col">
            <div class="promo-card">
                <div class="promo-header">CARNET ÚNICO</div>
                <h2>VIVÍ EL MONUMENTAL<br>DESDE ADENTRO.</h2>
                <span class="promo-badge">MÁS DE 85.000 HINCHAS</span>
                <p class="promo-sub">Con tu registro accedés a la venta de entradas por tribuna y beneficios exclusivos del Club Atlético River Plate.</p>
            </div>
        </div>
    </div>
</body>
</html>