<?php
require 'includes/conn.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user';

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role]);
        $success = "Usuario registrado correctamente.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro - File Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
  <form method="post" class="bg-white p-4 rounded shadow" style="width: 320px;">
    <h4 class="text-center mb-3">Registro</h4>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <div class="mb-3">
      <label>Usuario</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Contraseña</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-success w-100">Registrar</button>
    <a href="login.php" class="d-block text-center mt-2">Iniciar sesión</a>
  </form>
</div>
</body>
</html>
