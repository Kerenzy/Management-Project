<?php
require_once 'config/session.php';
require_once 'models/DokumentasiModel.php';

requireLogin();
$currentUserId = getCurrentUserId();

$id = $_GET['id'] ?? 0;
$dokumentasiModel = new DokumentasiModel();
$file = $dokumentasiModel->getById($id);

if ($file && file_exists($file['path_file'])) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file['nama_file'] . '"');
    header('Content-Length: ' . filesize($file['path_file']));
    readfile($file['path_file']);
    exit;
} else {
    $_SESSION['error'] = 'File tidak ditemukan';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}
?>

