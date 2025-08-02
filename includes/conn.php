<?php
$host = 'localhost';
$db = 'file_manager';
$user = 'root';
$pass = ''; // cambiá esto si tu contraseña de MySQL no está vacía

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Conexión fallida: " . $e->getMessage());
}
?>
