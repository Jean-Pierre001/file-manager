<?php
header('Content-Type: application/json');

$baseDir = realpath(__DIR__ . '/uploads/');

$path = $_GET['path'] ?? '';

if (!$path || strpos($path, '..') !== false) {
    echo json_encode(['success' => false, 'error' => 'Ruta inválida']);
    exit;
}

$fullPath = realpath(__DIR__ . '/' . $path);

if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !file_exists($fullPath)) {
    echo json_encode(['success' => false, 'error' => 'Archivo no encontrado']);
    exit;
}

$sizeKb = round(filesize($fullPath) / 1024, 2);
$type = mime_content_type($fullPath);
$modified = date("Y-m-d H:i:s", filemtime($fullPath));
$name = basename($fullPath);

echo json_encode([
    'success' => true,
    'name' => $name,
    'size' => $sizeKb,
    'type' => $type,
    'modified' => $modified
]);
