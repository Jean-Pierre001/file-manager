<?php
// Evitar cache HTTP
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

// Define el directorio base
$baseDir = __DIR__ . '/uploads/';

$folder = $_GET['folder'] ?? '';
$folder = trim($folder, "/");

$baseDirReal = realpath($baseDir);
$fullPath = realpath($baseDir . $folder);

// Validación: asegurar que la ruta esté dentro del directorio base
if (!$fullPath || strpos($fullPath, $baseDirReal) !== 0 || !is_dir($fullPath)) {
    echo json_encode(['error' => 'Ruta inválida']);
    exit;
}

// Función para crear breadcrumbs
function buildBreadcrumbs($folder) {
    $crumbs = [];
    $parts = $folder === '' ? [] : explode('/', $folder);
    $acc = '';
    $crumbs[] = ['name' => 'Inicio', 'path' => ''];
    foreach ($parts as $part) {
        $acc = ($acc === '') ? $part : $acc . '/' . $part;
        $crumbs[] = ['name' => $part, 'path' => $acc];
    }
    return $crumbs;
}

$folders = [];
$files = [];

// Leer contenido del directorio para archivos
if ($dh = opendir($fullPath)) {
    while (($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') continue;

        $filePath = $fullPath . DIRECTORY_SEPARATOR . $file;
        $relativePath = 'uploads/' . ($folder ? $folder . '/' : '') . $file;

        if (is_dir($filePath)) {
            $folders[] = $file;
        } elseif (is_file($filePath)) {
            $files[] = [
                'filename' => $file,
                'filesize' => filesize($filePath),
                'uploaded_at' => date("Y-m-d H:i:s", filemtime($filePath)),
                'path' => $relativePath,
                'type' => mime_content_type($filePath)
            ];
        }
    }
    closedir($dh);
}

// Ordenar alfabéticamente
sort($folders);
usort($files, fn($a, $b) => strcasecmp($a['filename'], $b['filename']));

// Devolver respuesta JSON
echo json_encode([
    'current_folder' => $folder,
    'breadcrumbs' => buildBreadcrumbs($folder),
    'folders' => $folders,  // 👈 ESTO ENVÍA LAS CARPETAS
    'files' => $files       // Esto son los archivos
]);

