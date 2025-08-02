<?php

$baseDir = realpath(__DIR__ . '/uploads/');

$path = $_GET['path'] ?? '';

if (!$path || strpos($path, '..') !== false) {
    http_response_code(400);
    exit('Ruta inválida');
}

$fullPath = realpath(__DIR__ . '/' . $path);

if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    http_response_code(404);
    exit('Archivo no encontrado');
}

$filename = basename($fullPath);
$filesize = filesize($fullPath);
$filetype = mime_content_type($fullPath);

header('Content-Description: File Transfer');
header('Content-Type: ' . $filetype);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');
readfile($fullPath);
exit;
