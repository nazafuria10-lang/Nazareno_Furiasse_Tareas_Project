<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/escapar.php';

// Verificar que exista una sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Recuperar los datos actualizados del usuario logueado
$stmt = $conexion->prepare('SELECT id, nombre, apellido, dni, email, rol, creado_en FROM usuarios WHERE id = :id AND archivado_en IS NULL LIMIT 1');
$stmt->execute(['id' => $_SESSION['usuario_id']]);
$datosUsuario = $stmt->fetch();

if (!$datosUsuario) {
    header('Location: cerrar_sesion.php');
    exit;
}

// Control de pestaña activa (datos, entradas, historia)
$seccion = $_GET['seccion'] ?? 'datos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>River Plate | Panel de Socio</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="bg-dark">
    <header class="navbar-river">
        <div class="nav-brand">RIVER<span>ID</span> | CLUB ATLÉTICO RIVER PLATE</div>
        <nav class="nav-links">
            <a href="panel.php?seccion=datos" class="<?= $seccion === 'datos' ? 'active' : '' ?>">Mis Datos</a>
            <a href="panel.php?seccion=entradas" class="<?= $seccion === 'entradas' ? 'active' : '' ?>">Entradas y Tribunas</a>
            <a href="panel.php?seccion=historia" class="<?= $seccion === 'historia' ? 'active' : '' ?>">Historia y Monumental</a>
            <?php if ($datosUsuario['rol'] === 'admin'): ?>
                <a href="admin_usuarios.php" class="btn-nav-admin">Panel Admin</a>
            <?php endif; ?>
            <a href="cerrar_sesion.php" class="btn-salir">Cerrar sesión</a>
        </nav>
    </header>

    <main class="container-principal">
        <?php if ($seccion === 'datos'): ?>
            <section class="card-river">
                <div class="card-header-banda">
                    <h2>CARNET DIGITAL RIVERID</h2>
                    <span class="badge-estado">SOCIO ACTIVO</span>
                </div>
                <p class="texto-intro">Bienvenido/a, <strong><?= escapar($datosUsuario['nombre'] . ' ' . $datosUsuario['apellido']) ?></strong>. Estos son tus datos registrados en el sistema oficial:</p>

                <div class="grid-datos">
                    <div class="dato-box">
                        <span class="dato-label">N° de Socio / ID</span>
                        <span class="dato-valor">#<?= escapar((string)$datosUsuario['id']) ?></span>
                    </div>
                    <div class="dato-box">
                        <span class="dato-label">Nombre Completo</span>
                        <span class="dato-valor"><?= escapar($datosUsuario['nombre'] . ' ' . $datosUsuario['apellido']) ?></span>
                    </div>
                    <div class="dato-box">
                        <span class="dato-label">Documento (DNI)</span>
                        <span class="dato-valor"><?= escapar($datosUsuario['dni']) ?></span>
                    </div>
                    <div class="dato-box">
                        <span class="dato-label">Correo Electrónico</span>
                        <span class="dato-valor"><?= escapar($datosUsuario['email']) ?></span>
                    </div>
                    <div class="dato-box">
                        <span class="dato-label">Tipo de Cuenta</span>
                        <span class="dato-valor"><?= escapar(strtoupper($datosUsuario['rol'])) ?></span>
                    </div>
                    <div class="dato-box">
                        <span class="dato-label">Fecha de Alta</span>
                        <span class="dato-valor"><?= escapar($datosUsuario['creado_en']) ?></span>
                    </div>
                </div>
            </section>

        <?php elseif ($seccion === 'entradas'): ?>
            <section class="card-river">
                <div class="card-header-banda">
                    <h2>VENTA DE ENTRADAS - MÂS MONUMENTAL</h2>
                    <span class="badge-estado">PRÓXIMO PARTIDO DE LOCAL</span>
                </div>
                <p class="texto-intro">Valores vigentes por sector para socios con RiverID activo:</p>

                <table class="tabla-river">
                    <thead>
                        <tr>
                            <th>Tribuna</th>
                            <th>Ubicación</th>
                            <th>Precio Socio</th>
                            <th>Precio Somos River</th>
                            <th>Disponibilidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Sívori Baja / Alta</strong></td>
                            <td>Cabecera Norte</td>
                            <td>$18.000</td>
                            <td>$24.500</td>
                            <td><span class="tag-disponible">Disponible</span></td>
                        </tr>
                        <tr>
                            <td><strong>Centenario Baja / Alta</strong></td>
                            <td>Cabecera Sur</td>
                            <td>$18.000</td>
                            <td>$24.500</td>
                            <td><span class="tag-disponible">Disponible</span></td>
                        </tr>
                        <tr>
                            <td><strong>San Martín Alta</strong></td>
                            <td>Platea Lateral Oeste</td>
                            <td>$32.000</td>
                            <td>$41.000</td>
                            <td><span class="tag-disponible">Disponible</span></td>
                        </tr>
                        <tr>
                            <td><strong>San Martín Media / Baja</strong></td>
                            <td>Platea Preferencial Oeste</td>
                            <td>$58.000</td>
                            <td>$72.000</td>
                            <td><span class="tag-agotado">Últimos lugares</span></td>
                        </tr>
                        <tr>
                            <td><strong>Belgrano Alta</strong></td>
                            <td>Platea Lateral Este</td>
                            <td>$32.000</td>
                            <td>$41.000</td>
                            <td><span class="tag-disponible">Disponible</span></td>
                        </tr>
                        <tr>
                            <td><strong>Belgrano Media / Baja</strong></td>
                            <td>Platea Preferencial Este</td>
                            <td>$55.000</td>
                            <td>$69.000</td>
                            <td><span class="tag-disponible">Disponible</span></td>
                        </tr>
                    </tbody>
                </table>
            </section>

        <?php elseif ($seccion === 'historia'): ?>
            <section class="card-river">
                <div class="card-header-banda">
                    <h2>HISTORIA DEL CLUB Y EL ESTADIO MÂS MONUMENTAL</h2>
                    <span class="badge-estado">DESDE 1901</span>
                </div>

                <div class="historia-grid">
                    <article class="historia-bloque">
                        <h3>El nacimiento del Más Grande (1901)</h3>
                        <p>El <strong>Club Atlético River Plate</strong> fue fundado el 25 de mayo de 1901 en el barrio de La Boca, tras la fusión de los clubes Santa Rosa y La Rosales. Con la tradicional camiseta blanca cruzada por la banda roja diagonal, River construyó una identidad basada en el fútbol ofensivo ("La Máquina"), formando ídolos históricos como Ángel Labruna, Amadeo Carrizo, Norberto Alonso, Enzo Francescoli y Marcelo Gallardo.</p>
                        <p>A lo largo de su historia conquistó decenas de campeonatos locales y títulos internacionales, entre los que destacan <strong>4 Copas Libertadores de América</strong> (1986, 1996, 2015 y la histórica final de Madrid en 2018) y la <strong>Copa Intercontinental 1986</strong>.</p>
                    </article>

                    <article class="historia-bloque">
                        <h3>El Estadio Mâs Monumental (1938 - Actualidad)</h3>
                        <p>Impulsado por la visión del presidente Antonio Vespucio Liberti, el estadio ubicado en el barrio de Belgrano/Núñez fue inaugurado el <strong>26 de mayo de 1938</strong> con un triunfo 3-1 frente a Peñarol.</p>
                        <p>Fue sede de la final de la Copa Mundial de la FIFA 1978 y escenario habitual de la Selección Argentina. Tras su reciente remodelación integral, se convirtió en el estadio más grande de Sudamérica, con capacidad para más de <strong>85.000 espectadores</strong>, nuevas tribunas bajas cercanas al campo de juego y césped híbrido de última generación.</p>
                    </article>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>