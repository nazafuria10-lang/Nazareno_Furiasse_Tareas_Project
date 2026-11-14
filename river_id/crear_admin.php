<?php
require_once __DIR__ . '/conexion.php';

$nombre = 'Admin';
$apellido = 'River';
$dni = '11111111';
$email = 'admin@riverplate.com';
$passwordHash = password_hash('River1901', PASSWORD_DEFAULT);

try {
    $sql = 'INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol) 
            VALUES (:nombre, :apellido, :dni, :email, :password_hash, :rol)';
    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        'nombre' => $nombre,
        'apellido' => $apellido,
        'dni' => $dni,
        'email' => $email,
        'password_hash' => $passwordHash,
        'rol' => 'admin'
    ]);
    echo "Administrador creado con éxito.<br>Email: <b>admin@riverplate.com</b><br>Contraseña: <b>River1901</b><br><a href='index.php'>Ir al Login</a>";
} catch (PDOException $e) {
    echo "El administrador ya existe o hubo un error: " . $e->getMessage();
}
?>