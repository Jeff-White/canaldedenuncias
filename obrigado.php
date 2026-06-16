<?php
require_once __DIR__ . '/includes/db.php';

$slug = isset($_GET['empresa']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['empresa']) : '';
$empresa = $slug ? getEmpresaBySlug($slug) : null;
$protocolo = isset($_GET['protocolo']) ? preg_replace('/[^A-Z0-9-]/', '', $_GET['protocolo']) : '';

if (!$empresa) {
    http_response_code(404);
    die('Empresa não identificada.');
}

$logo = $empresa['logo_path'] ? 'assets/logos/' . $empresa['logo_path'] : null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Denúncia enviada - <?= htmlspecialchars($empresa['nome']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container" style="text-align:center;">
    <header class="form-header">
        <?php if ($logo): ?>
            <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($empresa['nome']) ?>" class="logo">
        <?php endif; ?>
        <h1>Canal de Denúncias</h1>
        <p class="subtitulo"><?= htmlspecialchars($empresa['nome']) ?></p>
    </header>

    <div class="form-message sucesso">
        <p>Obrigado por preencher o formulário. Nosso compromisso é promover um ambiente seguro, saudável e respeitoso para todos. Sua denúncia será tratada com responsabilidade, e cada relato é essencial para construirmos juntos um local de trabalho melhor.</p>
    </div>

    <?php if ($protocolo): ?>
        <p>Guarde o número de protocolo abaixo caso precise acompanhar sua denúncia:</p>
        <p style="font-size:1.4rem;font-weight:bold;letter-spacing:1px;color:var(--cor-primaria);"><?= htmlspecialchars($protocolo) ?></p>
    <?php endif; ?>

    <?php if (!empty($empresa['url_site'])): ?>
        <p style="margin-top:30px;">
            <a href="<?= htmlspecialchars($empresa['url_site']) ?>" class="btn btn-primario">Ir para o Site</a>
        </p>
    <?php endif; ?>
</div>
</body>
</html>
