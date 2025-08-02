<?php
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

if (is_dir($fullPath)) {
    // Si quieres eliminar carpetas, hazlo aquí, por ahora no permitimos
    echo json_encode(['success' => false, 'error' => 'No se permite eliminar carpetas']);
    exit;
}

if (unlink($fullPath)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al eliminar archivo']);
}
