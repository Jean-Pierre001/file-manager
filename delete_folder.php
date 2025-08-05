<?php
include 'includes/session.php';
include 'includes/conn.php'; // PDO connection

header('Content-Type: application/json');

$baseDir = __DIR__ . '/uploads/';
$folderId = $_POST['folder_id'] ?? null;

if (!$folderId || !is_numeric($folderId)) {
    echo json_encode(['success' => false, 'error' => 'ID de carpeta inválido']);
    exit;
}

// Obtener path de la carpeta para borrar en disco
$stmt = $pdo->prepare("SELECT path FROM folders WHERE id = ?");
$stmt->execute([$folderId]);
$relativePath = $stmt->fetchColumn();

if (!$relativePath) {
    echo json_encode(['success' => false, 'error' => 'Carpeta no encontrada']);
    exit;
}

// Construir ruta absoluta
$targetPath = realpath($baseDir) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

// Función recursiva para borrar carpeta y su contenido en disco
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object === '.' || $object === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $object;
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

// Función para eliminar carpeta y sus subcarpetas en BD
function deleteFolderDB($pdo, $folderId) {
    // Buscar carpetas hijas (subfolders)
    $stmt = $pdo->prepare("SELECT id FROM folders WHERE parent_id = ?");
    $stmt->execute([$folderId]);
    $subfolderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Eliminar recursivamente subcarpetas
    foreach ($subfolderIds as $subId) {
        deleteFolderDB($pdo, $subId);
    }

    // Eliminar la carpeta actual
    $stmt = $pdo->prepare("DELETE FROM folders WHERE id = ?");
    $stmt->execute([$folderId]);
}

try {
    // Borrar carpeta en disco recursivamente
    rrmdir($targetPath);

    // Borrar carpeta en BD (y recursivamente sus hijas)
    deleteFolderDB($pdo, $folderId);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al eliminar carpeta: ' . $e->getMessage()]);
}
