<?php
include 'includes/session.php';

header('Content-Type: application/json');

$baseDir = __DIR__ . '/uploads/';

$folderName = trim($_POST['folder_name'] ?? '');
$currentFolder = trim($_POST['current_folder'] ?? '');

if (strpos($folderName, '..') !== false || strpos($currentFolder, '..') !== false) {
    echo json_encode(['success' => false, 'error' => 'Nombre inválido']);
    exit;
}

if ($folderName === '') {
    echo json_encode(['success' => false, 'error' => 'Nombre vacío']);
    exit;
}

$targetPath = realpath($baseDir) . DIRECTORY_SEPARATOR . ($currentFolder ? $currentFolder . DIRECTORY_SEPARATOR : '') . $folderName;

if (file_exists($targetPath)) {
    echo json_encode(['success' => false, 'error' => 'Carpeta ya existe']);
    exit;
}

if (mkdir($targetPath, 0755, true)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se pudo crear carpeta']);
}
