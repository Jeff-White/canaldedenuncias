<?php
require_once __DIR__ . '/includes/db.php';

$par = isset($_GET['par']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['par']) : '';
$slug = isset($_GET['empresa']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['empresa']) : '';

$empresa = null;
if ($par !== '') {
    $empresa = getEmpresaByParam($par);
} elseif ($slug !== '') {
    $empresa = getEmpresaBySlug($slug);
}

if (!$empresa) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex"><title>Canal de Denúncias</title></head><body style="font-family:Arial,sans-serif;text-align:center;padding:60px;">';
    echo '<h1>Empresa não encontrada</h1>';
    echo '<p>Por favor, verifique o link utilizado.</p>';
    echo '<button onclick="window.close()" style="padding:12px 28px;border:none;border-radius:6px;background:#0d3b66;color:#fff;font-size:1rem;font-weight:600;cursor:pointer;">Fechar janela</button>';
    echo '</body></html>';
    exit;
}

require __DIR__ . '/form.php';
