<?php
include 'includes/session.php';
include 'includes/conn.php';

header('Content-Type: application/json');

$baseDir = realpath(__DIR__ . '/uploads/');
$oldPath = $_POST['oldPath'] ?? '';
$newName = $_POST['newNameInput'] ?? '';

if (!$oldPath || !$newName) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros']);
    exit;
}

if (strpos($oldPath, '..') !== false || strpos($newName, '..') !== false) {
    echo json_encode(['success' => false, 'error' => 'Ruta inválida']);
    exit;
}

$fullOldPath = realpath(__DIR__ . '/' . $oldPath);
if (!$fullOldPath || strpos($fullOldPath, $baseDir) !== 0) {
    echo json_encode(['success' => false, 'error' => 'Ruta inválida']);
    exit;
}

// Obtener ruta relativa base para actualizar en la DB
$relativeDir = dirname($oldPath);
$relativeNewPath = ($relativeDir !== '.' ? $relativeDir . '/' : '') . $newName;

// Obtener ruta absoluta destino
$fullNewPath = dirname($fullOldPath) . '/' . $newName;

// Verificamos que no exista ya un archivo con ese nombre
if (file_exists($fullNewPath)) {
    echo json_encode(['success' => false, 'error' => 'Ya existe un archivo con ese nombre']);
    exit;
}

// Intentar renombrar físicamente
if (!rename($fullOldPath, $fullNewPath)) {
    echo json_encode(['success' => false, 'error' => 'No se pudo renombrar el archivo']);
    exit;
}

// Actualizar base de datos
try {
    // Si es carpeta, actualizamos subrutas también
    if (is_dir($fullNewPath)) {
        $stmt = $pdo->prepare("SELECT filepath FROM files WHERE filepath LIKE ?");
        $stmt->execute([$oldPath . '/%']);
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $oldFile = $row['filepath'];
            $newFile = preg_replace('#^' . preg_quote($oldPath, '#') . '#', $relativeNewPath, $oldFile);
            $update = $pdo->prepare("UPDATE files SET filepath = ? WHERE filepath = ?");
            $update->execute([$newFile, $oldFile]);
        }
    }

    // Actualiza la entrada principal
    $stmt = $pdo->prepare("UPDATE files SET filepath = ?, filename = ? WHERE filepath = ?");
    $stmt->execute([$relativeNewPath, $newName, $oldPath]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error DB: ' . $e->getMessage()]);
}
