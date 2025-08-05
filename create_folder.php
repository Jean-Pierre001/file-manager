<?php
include 'includes/session.php';
include 'includes/conn.php'; // Para conexión PDO

header('Content-Type: application/json');

$baseDir = __DIR__ . '/uploads/';
$folderName = trim($_POST['folder_name'] ?? '');
$currentFolder = trim($_POST['current_folder'] ?? '');
$userId = $_SESSION['user_id'] ?? 0; // Usuario que crea la carpeta

// Validar nombre
if (strpos($folderName, '..') !== false || strpos($currentFolder, '..') !== false) {
    echo json_encode(['success' => false, 'error' => 'Nombre inválido']);
    exit;
}

if ($folderName === '') {
    echo json_encode(['success' => false, 'error' => 'Nombre vacío']);
    exit;
}

// Construir rutas
$relativePath = ($currentFolder ? $currentFolder . DIRECTORY_SEPARATOR : '') . $folderName;
$targetPath = realpath($baseDir) . DIRECTORY_SEPARATOR . $relativePath;

// Comprobar si existe
if (file_exists($targetPath)) {
    echo json_encode(['success' => false, 'error' => 'Carpeta ya existe']);
    exit;
}

// Crear carpeta en disco
if (mkdir($targetPath, 0755, true)) {
    try {
        // Buscar parent_id si es subcarpeta
        $parentId = null;
        if ($currentFolder) {
            $stmt = $pdo->prepare("SELECT id FROM folders WHERE path = ?");
            $stmt->execute([$currentFolder]);
            $parentId = $stmt->fetchColumn() ?: null;
        }

        // Insertar en BD
        $stmt = $pdo->prepare("
            INSERT INTO folders (name, path, parent_id, created_by) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$folderName, $relativePath, $parentId, $userId]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false, 
            'error' => 'Carpeta creada en disco pero no registrada en BD: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No se pudo crear carpeta']);
}
