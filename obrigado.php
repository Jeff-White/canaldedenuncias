<?php
require_once __DIR__ . '/includes/db.php';

$par = isset($_GET['par']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['par']) : '';
$slug = isset($_GET['empresa']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['empresa']) : '';
$protocolo = isset($_GET['protocolo']) ? preg_replace('/[^A-Z0-9-]/', '', $_GET['protocolo']) : '';

$empresa = null;
if ($par !== '') {
    $empresa = getEmpresaByParam($par);
} elseif ($slug !== '') {
    $empresa = getEmpresaBySlug($slug);
}

if (!$empresa) {
    http_response_code(404);
    die('Empresa não identificada.');
}

$logo = (!empty($empresa['logo_path']) && !preg_match('/\.\./', $empresa['logo_path'])) ? 'assets/logos/' . $empresa['logo_path'] : null;
$urlSite = (!empty($empresa['url_site']) && preg_match('#^https?://#i', $empresa['url_site'])) ? $empresa['url_site'] : null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<title>Denúncia enviada - <?= htmlspecialchars($empresa['nome'], ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container" style="text-align:center;">
    <header class="form-header">
        <?php if ($logo): ?>
            <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($empresa['nome'], ENT_QUOTES, 'UTF-8') ?>" class="logo">
        <?php endif; ?>
        <h1>Canal de Denúncias</h1>
        <p class="subtitulo"><?= htmlspecialchars($empresa['nome'], ENT_QUOTES, 'UTF-8') ?></p>
    </header>

    <div class="form-message sucesso">
        <p>Obrigado por preencher o formulário. Nosso compromisso é promover um ambiente seguro, saudável e respeitoso para todos. Sua denúncia será tratada com responsabilidade, e cada relato é essencial para construirmos juntos um local de trabalho melhor.</p>
    </div>

    <?php if ($protocolo): ?>
        <p>Guarde o número de protocolo abaixo caso precise acompanhar sua denúncia:</p>
        <p style="font-size:1.4rem;font-weight:bold;letter-spacing:1px;color:var(--cor-primaria);"><?= htmlspecialchars($protocolo, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($urlSite): ?>
        <p style="margin-top:30px;">
            <a href="<?= htmlspecialchars($urlSite, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primario">Ir para o Site</a>
        </p>
    <?php endif; ?>
</div>
</body>
</html>
