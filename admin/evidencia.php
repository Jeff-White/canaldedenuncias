<?php
require_once __DIR__ . '/includes/auth.php';
$usuario = exigirLogin();
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
[$filtroSql, $filtroParams] = filtroEmpresaUsuario($usuario);

$sql = "SELECT d.evidencia_path FROM denuncias d WHERE d.id = ?" . $filtroSql;
$params = array_merge([$id], $filtroParams);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$row = $stmt->fetch();

if (!$row || !$row['evidencia_path']) {
    http_response_code(404);
    die('Arquivo não encontrado.');
}

$caminho = realpath(rtrim(UPLOAD_DIR, '/') . '/' . $row['evidencia_path']);
$baseDir = realpath(UPLOAD_DIR);

// Garante que o caminho final está dentro do diretório de uploads
if (!$caminho || strpos($caminho, $baseDir) !== 0 || !is_file($caminho)) {
    http_response_code(404);
    die('Arquivo não encontrado.');
}

$mime = mime_content_type($caminho) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($caminho));
header('Content-Disposition: inline; filename="' . basename($caminho) . '"');
readfile($caminho);
