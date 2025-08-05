<?php
include 'includes/session.php';
include 'includes/conn.php';

header('Content-Type: application/json');

// Leer JSON raw del body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos']);
    exit;
}

$sources = $input['source'] ?? [];
$target = $input['target'] ?? '';
$action = $input['action'] ?? '';

if (!is_array($sources) || empty($sources)) {
    echo json_encode(['success' => false, 'error' => 'No hay archivos para mover o copiar']);
    exit;
}

// Carpeta base absoluta "uploads"
$baseDir = realpath(__DIR__ . '/uploads');
if (!$baseDir) {
    echo json_encode(['success' => false, 'error' => 'Error con carpeta base uploads']);
    exit;
}

// Validar carpeta destino (debe existir y estar dentro de uploads)
$targetDir = realpath($baseDir . '/' . $target);
if (!$targetDir || strpos($targetDir, $baseDir) !== 0) {
    echo json_encode(['success' => false, 'error' => 'Carpeta destino inválida']);
    exit;
}

$repeatedFiles = [];

try {
    foreach ($sources as $sourcePath) {
        // Remover prefijo 'uploads/' si existe
        if (strpos($sourcePath, 'uploads/') === 0) {
            $sourcePath = substr($sourcePath, strlen('uploads/'));
        }

        // Seguridad: evita rutas extrañas (../)
        $sourceFullPath = realpath($baseDir . '/' . $sourcePath);
        if (!$sourceFullPath || strpos($sourceFullPath, $baseDir) !== 0) {
            continue; // Ignora rutas no válidas
        }

        $filename = basename($sourceFullPath);
        $destPath = $targetDir . '/' . $filename;

        // Si archivo ya existe en destino, agregar a repetidos y saltar
        if (file_exists($destPath)) {
            $repeatedFiles[] = $filename;
            continue;
        }

        if ($action === 'copy') {
            if (!copy($sourceFullPath, $destPath)) {
                echo json_encode(['success' => false, 'error' => "Error copiando $filename"]);
                exit;
            }
            
            } elseif ($action === 'cut') {
            if (!rename($sourceFullPath, $destPath)) {
                echo json_encode(['success' => false, 'error' => "Error moviendo $filename"]);
                exit;
            }
        }
    }

    $msg = count($sources) . ' archivo(s) ' . ($action === 'copy' ? 'copiados' : 'movidos');

    if (!empty($repeatedFiles)) {
        $msg .= '. Sin embargo, los siguientes archivos ya existen en la carpeta destino y no se copiaron/movieron: ' . implode(', ', $repeatedFiles);
        echo json_encode(['success' => true, 'warning' => $msg]);
    } else {
        echo json_encode(['success' => true, 'message' => $msg]);
    }
} catch (Exception $ex) {
    echo json_encode(['success' => false, 'error' => $ex->getMessage()]);
}
