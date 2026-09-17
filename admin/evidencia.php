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

if (!$row || empty($row['evidencia_path'])) {
    http_response_code(404);
    die('Arquivo não encontrado.');
}

// Bloqueia qualquer caractere suspeito no caminho relativo
if (preg_match('/\.\./', $row['evidencia_path'])) {
    http_response_code(400);
    die('Caminho de arquivo inválido.');
}

$baseDir = rtrim(realpath(UPLOAD_DIR), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$caminho = realpath($baseDir . $row['evidencia_path']);

// Garante que o caminho canônico reside estritamente dentro do diretório de uploads
if (!$caminho || strpos($caminho, $baseDir) !== 0 || !is_file($caminho)) {
    http_response_code(404);
    die('Arquivo não encontrado.');
}

// Whitelist estrita de MIME types para exibição inline
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($caminho) ?: 'application/octet-stream';
$mimesSegurosInline = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$nomeSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($caminho));
$disposition = in_array($mime, $mimesSegurosInline, true) ? 'inline' : 'attachment';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex');
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Content-Type: ' . ($disposition === 'inline' ? $mime : 'application/octet-stream'));
header('Content-Length: ' . filesize($caminho));
header('Content-Disposition: ' . $disposition . '; filename="' . $nomeSeguro . '"');

readfile($caminho);
exit;
