<?php
header('Content-Type: application/json');

$baseDir = realpath(__DIR__ . '/uploads/');

$path = $_POST['path'] ?? '';
$newName = $_POST['new_name'] ?? '';

if (!$path || !$newName) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros']);
    exit;
}

// Seguridad: Evitar .. y normalizar rutas
if (strpos($path, '..') !== false || strpos($newName, '..') !== false) {
    echo json_encode(['success' => false, 'error' => 'Ruta inválida']);
    exit;
}

$oldFullPath = realpath(__DIR__ . '/' . $path);
if (!$oldFullPath || strpos($oldFullPath, $baseDir) !== 0) {
    echo json_encode(['success' => false, 'error' => 'Archivo no válido']);
    exit;
}

$newFullPath = dirname($oldFullPath) . DIRECTORY_SEPARATOR . basename($newName);

// Evitar sobreescritura
if (file_exists($newFullPath)) {
    echo json_encode(['success' => false, 'error' => 'Ya existe un archivo con ese nombre']);
    exit;
}

if (rename($oldFullPath, $newFullPath)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error renombrando archivo']);
}
