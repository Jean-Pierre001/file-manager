<?php
include 'includes/session.php';
require_once 'includes/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['folder_id'])) {
        $folder_id = $_POST['folder_id'];

        try {
            // Buscar la carpeta en la base de datos
            $stmt = $pdo->prepare("SELECT * FROM folders WHERE id = ?");
            $stmt->execute([$folder_id]);
            $folder = $stmt->fetch();

            if (!$folder) {
                $_SESSION['error'] = "Carpeta no encontrada en la base de datos.";
                header('Location: folders.php');
                exit;
            }

            // Preparar datos para la papelera
            $trashFolderPath = 'trash/' . $folder['folder_system_name'];
            $trashFolderSystemName = $folder['folder_system_name'];

            // Insertar en la tabla trash (sin location)
            $insert = $pdo->prepare("INSERT INTO trash 
                (name, folder_path, deleted_on, original_id, folder_system_name) 
                VALUES (?, ?, CURDATE(), ?, ?)");
            $insert->execute([
                $folder['name'],
                $trashFolderPath,
                $folder['id'],
                $trashFolderSystemName
            ]);

            // Eliminar la carpeta de la tabla original
            $delete = $pdo->prepare("DELETE FROM folders WHERE id = ?");
            $delete->execute([$folder_id]);

            // Mover carpeta físicamente
            $baseFolders = __DIR__ . '/folders/';
            $baseTrash = __DIR__ . '/trash/';

            $oldPath = $baseFolders . $folder['folder_system_name'];
            $newPath = $baseTrash . $folder['folder_system_name'];

            if (!is_dir($baseTrash)) {
                mkdir($baseTrash, 0755, true);
            }

            // Función para mover carpeta completa recursivamente
            function moveFolder($src, $dst) {
                mkdir($dst, 0755, true);
                foreach (scandir($src) as $file) {
                    if ($file === '.' || $file === '..') continue;

                    $srcFile = $src . DIRECTORY_SEPARATOR . $file;
                    $dstFile = $dst . DIRECTORY_SEPARATOR . $file;

                    if (is_dir($srcFile)) {
                        moveFolder($srcFile, $dstFile);
                    } else {
                        rename($srcFile, $dstFile);
                    }
                }
                rmdir($src);
            }

            moveFolder($oldPath, $newPath);

            $_SESSION['success'] = "Carpeta movida correctamente a la papelera.";
            header('Location: folders.php');
            exit;

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al mover la carpeta: " . $e->getMessage();
            header('Location: folders.php');
            exit;
        }
    } else {
        $_SESSION['error'] = "ID de carpeta no especificado.";
        header('Location: folders.php');
        exit;
    }
} else {
    header('Location: folders.php');
    exit;
}