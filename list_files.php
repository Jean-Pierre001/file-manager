<?php
// Evitar cache HTTP
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

// Define el directorio base (ajusta según tu estructura)
$baseDir = __DIR__ . '/uploads/';

$folder = $_GET['folder'] ?? '';
$folder = trim($folder, "/");

$baseDirReal = realpath($baseDir);
$fullPath = realpath(rtrim($baseDir, '/') . '/' . $folder);

if (!$fullPath || strpos($fullPath, $baseDirReal) !== 0) {
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

$dh = opendir($fullPath);
if ($dh) {
    while (($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $fullPath . DIRECTORY_SEPARATOR . $file;
        if (is_dir($filePath)) {
            $folders[] = $file;
        } else {
            $filesize = filesize($filePath);
            $uploaded_at = date("Y-m-d H:i:s", filemtime($filePath));
            $filetype = mime_content_type($filePath);
            $files[] = [
                'filename' => $file,
                'filesize' => $filesize,
                'uploaded_at' => $uploaded_at,
                'path' => 'uploads/' . ($folder ? $folder . '/' : '') . $file,
                'type' => $filetype
            ];
        }
    }
    closedir($dh);
}

// Ordenar carpetas y archivos alfabéticamente
sort($folders);
usort($files, function($a, $b) {
    return strcasecmp($a['filename'], $b['filename']);
});

echo json_encode([
    'current_folder' => $folder,
    'breadcrumbs' => buildBreadcrumbs($folder),
    'folders' => $folders,
    'files' => $files
]);
