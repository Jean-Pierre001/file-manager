<?php
include 'includes/session.php';
include 'includes/conn.php'; // PDO connection

header('Content-Type: application/json');

$targetFolderId = $_POST['targetFolderId'] ?? null;

if (!$targetFolderId || !is_numeric($targetFolderId)) {
    echo json_encode(['error' => 'ID de carpeta no válido.']);
    exit();
}

// Obtener path físico en base a carpeta en BD
$stmt = $pdo->prepare("SELECT path FROM folders WHERE id = ?");
$stmt->execute([$targetFolderId]);
$folderPath = $stmt->fetchColumn();

if (!$folderPath) {
    echo json_encode(['error' => 'Carpeta destino no encontrada.']);
    exit();
}

$uploadBaseDir = __DIR__ . '/uploads/';
$baseDirReal = realpath($uploadBaseDir);

$fullTargetDir = $baseDirReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folderPath);

if (!is_dir($fullTargetDir)) {
    if (!mkdir($fullTargetDir, 0755, true)) {
        echo json_encode(['error' => 'No se pudo crear la carpeta destino.']);
        exit();
    }
}

if (empty($_FILES['file'])) {
    echo json_encode(['error' => 'No se ha recibido ningún archivo.']);
    exit();
}

$files = $_FILES['file'];

$allowedMimeTypes = [
    'image/jpeg', 'image/png', 'image/gif',
    'application/pdf', 'text/plain',
    'video/mp4', 'audio/mpeg',
];

$successFiles = [];
$errorFiles = [];

$fileCount = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $fileCount; $i++) {
    $originalName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
    $fileName = str_replace(' ', '_', $originalName);
    $fileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $fileName);
    $fileTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
    $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];

    if (!in_array($fileType, $allowedMimeTypes)) {
        $errorFiles[] = $fileName . ' (tipo no permitido)';
        continue;
    }

    $safeName = basename($fileName);
    $targetPath = $fullTargetDir . DIRECTORY_SEPARATOR . $safeName;

    $counter = 1;
    $pathInfo = pathinfo($safeName);
    while (file_exists($targetPath)) {
        $safeName = $pathInfo['filename'] . "_$counter." . $pathInfo['extension'];
        $targetPath = $fullTargetDir . DIRECTORY_SEPARATOR . $safeName;
        $counter++;
    }

    if (move_uploaded_file($fileTmp, $targetPath)) {
        $successFiles[] = $safeName;
    } else {
        $errorFiles[] = $fileName . ' (error al subir)';
    }
}

if (count($errorFiles) > 0) {
    echo json_encode(['error' => 'Errores con archivos: ' . implode(', ', $errorFiles), 'success' => $successFiles]);
} else {
    echo json_encode(['success' => $successFiles]);
}
