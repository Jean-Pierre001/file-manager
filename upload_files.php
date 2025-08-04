<?php
include 'includes/session.php';
include 'includes/conn.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

// Obtener carpeta destino relativa desde POST (ejemplo: "carpeta1/subcarpeta2") o raíz si no está
$targetFolder = trim($_POST['targetFolder'] ?? '');

$uploadBaseDir = __DIR__ . '/uploads/';

// Validar que la carpeta destino no tenga traversal (../)
if (strpos($targetFolder, '..') !== false) {
    echo json_encode(['error' => 'Ruta de carpeta no válida.']);
    exit();
}

$fullTargetDir = rtrim($uploadBaseDir, '/') . '/' . ltrim($targetFolder, '/');
if (substr($fullTargetDir, -1) !== '/') {
    $fullTargetDir .= '/';
}

// Validar con realpath que esté dentro de uploads/
$baseDirReal = realpath($uploadBaseDir);
$targetDirReal = realpath(rtrim($fullTargetDir, '/'));

if ($targetDirReal === false || strpos($targetDirReal, $baseDirReal) !== 0) {
    echo json_encode(['error' => 'Ruta de carpeta no válida.']);
    exit();
}


// Crear carpeta destino si no existe
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

// Soporte para múltiples archivos enviados (Dropzone puede enviar varios)

$allowedMimeTypes = [
    'image/jpeg', 'image/png', 'image/gif',
    'application/pdf', 'text/plain',
    'video/mp4', 'audio/mpeg',
    // agregar más si querés
];

$successFiles = [];
$errorFiles = [];

$fileCount = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $fileCount; $i++) {
    $originalName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
    $fileTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
    $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];

    // Limpiar nombre del archivo
    $cleanName = str_replace(' ', '_', $originalName); // Reemplazar espacios
    $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $cleanName); // Quitar caracteres no seguros
    $safeName = basename($cleanName);

    if (!in_array($fileType, $allowedMimeTypes)) {
        $errorFiles[] = $originalName . ' (tipo no permitido)';
        continue;
    }

    // Evitar sobreescritura
    $targetPath = $fullTargetDir . $safeName;
    $counter = 1;
    $pathInfo = pathinfo($safeName);
    while (file_exists($targetPath)) {
        $safeName = $pathInfo['filename'] . "_$counter." . $pathInfo['extension'];
        $targetPath = $fullTargetDir . $safeName;
        $counter++;
    }

    if (move_uploaded_file($fileTmp, $targetPath)) {
        $dbFilePath = ($targetFolder ? $targetFolder . '/' : '') . $safeName;

        $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, filepath, filesize, filetype) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $safeName, 'uploads/' . $dbFilePath, $fileSize, $fileType]);

        $successFiles[] = $safeName;
    } else {
        $errorFiles[] = $originalName . ' (error al subir)';
    }
}

if (count($errorFiles) > 0) {
    echo json_encode(['error' => 'Errores con archivos: ' . implode(', ', $errorFiles), 'success' => $successFiles]);
} else {
    echo json_encode(['success' => $successFiles]);
}
