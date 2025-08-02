<?php
require 'includes/conn.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        $message = "Por favor, completa todos los campos.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$username, $email, $hashed_password]);
            $message = "Usuario creado correctamente.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "El nombre de usuario ya existe.";
            } else {
                $message = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Crear Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 400px;">
    <h3 class="mb-4">Crear Usuario Nuevo</h3>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
        <div class="mb-3">
            <label>Usuario</label>
            <input type="text" name="username" class="form-control" required autofocus />
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required />
        </div>
        <div class="mb-3">
            <label>Contraseña</label>
            <input type="password" name="password" class="form-control" required />
        </div>
        <button class="btn btn-primary w-100" type="submit">Crear Usuario</button>
    </form>
    <a href="login.php" class="d-block mt-3 text-center">Volver al login</a>
</div>
</body>
</html>
