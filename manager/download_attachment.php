<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_role('manager');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT original_name, file_path, mime_type FROM task_attachments WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('File not found.');
}

$path = __DIR__ . '/../' . ltrim($file['file_path'], '/');
if (!is_file($path)) {
    http_response_code(404);
    exit('File no longer exists.');
}

$name = str_replace(["\r","\n"], '', basename($file['original_name']));
header('Content-Type: ' . $file['mime_type']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . addcslashes($name, '"\\') . '"');
readfile($path);
exit;
