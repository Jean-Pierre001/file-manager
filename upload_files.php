<?php
include 'includes/session.php';
include 'includes/conn.php'; // PDO connection

header('Content-Type: application/json');

// Recibir el ID de carpeta destino
$targetFolderId = $_POST['targetFolderId'] ?? null;

if (!$targetFolderId || !is_numeric($targetFolderId)) {
    echo json_encode(['error' => 'ID de carpeta no válido.']);
    exit();
}

// Obtener el path relativo de la carpeta destino desde la base de datos
$stmt = $pdo->prepare("SELECT path FROM folders WHERE id = ?");
$stmt->execute([$targetFolderId]);
$folderPath = $stmt->fetchColumn();

if (!$folderPath) {
    echo json_encode(['error' => 'Carpeta destino no encontrada.']);
    exit();
}

// Definir directorio base físico de uploads
$uploadBaseDir = __DIR__ . '/uploads/';
$baseDirReal = realpath($uploadBaseDir);

// Construir ruta física absoluta destino, usando DIRECTORY_SEPARATOR para compatibilidad
$fullTargetDir = $baseDirReal . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $folderPath);

// Validar que la ruta destino está dentro del directorio base (seguridad)
if (strpos($fullTargetDir, $baseDirReal) !== 0) {
    echo json_encode(['error' => 'Ruta destino fuera de uploads no permitida.']);
    exit();
}

// Crear la carpeta destino si no existe
if (!is_dir($fullTargetDir)) {
    if (!mkdir($fullTargetDir, 0755, true)) {
        echo json_encode(['error' => 'No se pudo crear la carpeta destino.']);
        exit();
    }
}

// Validar que se hayan recibido archivos
if (empty($_FILES['file'])) {
    echo json_encode(['error' => 'No se ha recibido ningún archivo.']);
    exit();
}

$files = $_FILES['file'];

// Lista de tipos MIME permitidos
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
    $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];

    if (!in_array($fileType, $allowedMimeTypes)) {
        $errorFiles[] = $fileName . ' (tipo no permitido)';
        continue;
    }

    $safeName = basename($fileName);
    $targetPath = $fullTargetDir . DIRECTORY_SEPARATOR . $safeName;

    // Evitar sobreescribir archivos con el mismo nombre
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
