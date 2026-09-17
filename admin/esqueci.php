<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/zeptomail.php';

$pdo = getDB();
$erro = null;
$enviado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif (!honeypotVazio()) {
        $erro = 'Não foi possível processar a solicitação.';
    } elseif (!captchaValido()) {
        $erro = 'Resposta da verificação incorreta. Tente novamente.';
    } else {
        $username = trim($_POST['username'] ?? '');

        // Busca usuário ativo pelo e-mail (username). Resposta é sempre genérica.
        $stmt = $pdo->prepare('SELECT id, nome, username FROM admin_usuarios WHERE username = ? AND ativo = 1 LIMIT 1');
        $stmt->execute([$username]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $token = criarTokenReset($pdo, (int)$usuario['id']);
            $link = baseUrl() . '/admin/redefinir.php?token=' . urlencode($token);

            $html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#333;">'
                  . '<h2>Redefinição de senha</h2>'
                  . '<p>Olá, ' . htmlspecialchars($usuario['nome']) . '.</p>'
                  . '<p>Recebemos uma solicitação para redefinir a senha de acesso ao painel do Canal de Denúncias. '
                  . 'Clique no botão abaixo para criar uma nova senha (o link é válido por 1 hora):</p>'
                  . '<p style="margin:24px 0;"><a href="' . htmlspecialchars($link) . '" '
                  . 'style="background:#0d3b66;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">Criar nova senha</a></p>'
                  . '<p style="font-size:12px;color:#777;">Se você não solicitou isso, ignore este e-mail — sua senha permanecerá a mesma.</p>'
                  . '<p style="font-size:12px;color:#777;word-break:break-all;">Se o botão não funcionar, copie e cole este endereço no navegador:<br>' . htmlspecialchars($link) . '</p>'
                  . '</div>';

            enviarEmailZepto($usuario['username'], 'Redefinição de senha - Canal de Denúncias', $html);
        }

        // Mesma resposta independentemente de o e-mail existir (evita enumeração)
        $enviado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<title>Esqueci minha senha - Painel Canal de Denúncias</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<div class="container" style="max-width:380px;margin-top:80px;">
    <img src="assets/logo-smartweb.svg" alt="Smartweb" class="login-logo">
    <h1 style="text-align:center;font-size:1.2rem;color:var(--cor-primaria);">Recuperar acesso</h1>

    <?php if ($enviado): ?>
        <div class="form-message sucesso">Se o e-mail informado estiver cadastrado, enviamos um link para criar uma nova senha. Verifique sua caixa de entrada (e o spam).</div>
        <a class="link-aux" href="login.php">Voltar ao login</a>
    <?php else: ?>
        <?php if ($erro): ?><div class="form-message erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
        <p style="font-size:0.88rem;color:#555;">Informe o e-mail de acesso e enviaremos um link para você criar uma nova senha.</p>
        <form method="POST">
            <?= csrfCampo() ?>
            <?= honeypotCampo() ?>
            <div class="campo">
                <label for="username">E-mail de acesso</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <?= captchaCampo() ?>
            <button type="submit" class="btn btn-primario" style="width:100%;">Enviar link</button>
            <a class="link-aux" href="login.php">Voltar ao login</a>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
