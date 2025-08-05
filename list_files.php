<?php
include 'includes/session.php';
include 'includes/conn.php'; // conexión PDO

// Evitar cache HTTP
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

// Define el directorio base físico para archivos
$baseDir = __DIR__ . '/uploads/';

$folder = $_GET['folder'] ?? '';
$folder = trim($folder, "/");

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

// Obtener carpeta actual desde BD para sacar su id
if ($folder === '') {
    $currentFolderId = null; // raíz
} else {
    $stmt = $pdo->prepare("SELECT id FROM folders WHERE path = ?");
    $stmt->execute([$folder]);
    $currentFolderId = $stmt->fetchColumn();
    if ($currentFolderId === false) {
        echo json_encode(['error' => 'Carpeta no encontrada']);
        exit;
    }
}

// Listar carpetas hijas desde BD con parent_id = currentFolderId
$stmt = $pdo->prepare("SELECT id, name, path FROM folders WHERE parent_id " . ($currentFolderId === null ? "IS NULL" : "= ?") . " ORDER BY name ASC");
if ($currentFolderId === null) {
    $stmt->execute();
} else {
    $stmt->execute([$currentFolderId]);
}
$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listar archivos desde disco dentro de $baseDir/$folder
$fullPath = realpath($baseDir . $folder);
if (!$fullPath || strpos($fullPath, realpath($baseDir)) !== 0 || !is_dir($fullPath)) {
    // Si la carpeta no existe físicamente, devolver vacíos
    $files = [];
} else {
    $files = [];
    $dh = opendir($fullPath);
    if ($dh) {
        while (($file = readdir($dh)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $fullPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $files[] = [
                    'filename' => $file,
                    'filesize' => filesize($filePath),
                    'uploaded_at' => date("Y-m-d H:i:s", filemtime($filePath)),
                    'path' => ($folder ? $folder . '/' : '') . $file,
                    'type' => mime_content_type($filePath)
                ];
            }
        }
        closedir($dh);
    }
    // Ordenar archivos por nombre
    usort($files, fn($a, $b) => strcasecmp($a['filename'], $b['filename']));
}

// Devolver JSON con folders (objetos con id, name, path), archivos, breadcrumbs
echo json_encode([
    'current_folder' => $folder,
    'breadcrumbs' => buildBreadcrumbs($folder),
    'folders' => $folders,
    'files' => $files
]);
