<?php
include 'includes/session.php';
include 'includes/conn.php';

header('Content-Type: application/json');

$baseDir = realpath(__DIR__ . '/uploads/');
$path = $_POST['path'] ?? '';

if (!$path) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros']);
    exit;
}

if (strpos($path, '..') !== false) {
    echo json_encode(['success' => false, 'error' => 'Ruta inválida']);
    exit;
}

$fullPath = realpath(__DIR__ . '/' . $path);
if (!$fullPath || strpos($fullPath, $baseDir) !== 0) {
    echo json_encode(['success' => false, 'error' => 'Archivo no válido']);
    exit;
}

// Eliminar archivo físico
if (is_file($fullPath) && unlink($fullPath)) {
    // Eliminar registro de la DB
    $stmt = $pdo->prepare("DELETE FROM files WHERE filepath = ?");
    $stmt->execute([$path]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al eliminar archivo']);
}
