<?php
include 'includes/session.php';
include 'includes/conn.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['paths']) || !is_array($data['paths'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros o formato inválido']);
    exit;
}

$baseDir = realpath(__DIR__ . '/uploads/');
$errors = [];

foreach ($data['paths'] as $path) {
    if (!$path || strpos($path, '..') !== false) {
        $errors[] = "Ruta inválida: $path";
        continue;
    }

    $fullPath = realpath(__DIR__ . '/' . $path);
    if (!$fullPath || strpos($fullPath, $baseDir) !== 0) {
        $errors[] = "Archivo no válido o fuera de directorio permitido: $path";
        continue;
    }

    if (is_dir($fullPath)) {
        $errors[] = "No se permite eliminar carpetas: $path";
        continue;
    }

    if (is_file($fullPath) && unlink($fullPath)) {
    } else {
        $errors[] = "No se pudo eliminar archivo: $path";
    }
}

if (count($errors) > 0) {
    echo json_encode(['success' => false, 'error' => implode('; ', $errors)]);
} else {
    echo json_encode(['success' => true]);
}
