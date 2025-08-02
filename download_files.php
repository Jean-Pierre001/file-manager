<?php
include 'includes/session.php';

if (!isset($_POST['paths']) || !is_array($_POST['paths'])) {
    die('No files selected');
}

$baseDir = __DIR__ . '/uploads/';

$paths = $_POST['paths'];

$zip = new ZipArchive();
$zipName = tempnam(sys_get_temp_dir(), 'files_') . '.zip';

if ($zip->open($zipName, ZipArchive::CREATE) !== TRUE) {
    die('No se pudo crear el archivo ZIP');
}

foreach ($paths as $path) {
    $file = realpath($baseDir . DIRECTORY_SEPARATOR . $path);
    if ($file && str_starts_with($file, $baseDir) && is_file($file)) {
        // El nombre en el ZIP se mantiene relativo (solo nombre archivo)
        $zip->addFile($file, basename($file));
    }
}

$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="archivos.zip"');
header('Content-Length: ' . filesize($zipName));

readfile($zipName);
unlink($zipName);
exit;
