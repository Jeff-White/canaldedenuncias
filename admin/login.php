<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/turnstile.php';

$erro = null;
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif (!honeypotVazio()) {
        // bot preencheu o campo oculto — rejeita silenciosamente
        $erro = 'Não foi possível processar a solicitação.';
    } elseif (!validarTurnstile($_POST['cf-turnstile-response'] ?? '', $_SERVER['REMOTE_ADDR'] ?? null)) {
        $erro = 'Falha na verificação de segurança (Cloudflare Turnstile). Por favor, marque a caixa e tente novamente.';
    } elseif (loginBloqueado($pdo)) {
        $erro = 'Muitas tentativas de login. Tente novamente em alguns minutos.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $usuario = tentarLogin($username, $senha);
        if ($usuario) {
            limparTentativasLogin($pdo);
            session_regenerate_id(true);
            $_SESSION['admin_user'] = $usuario;
            header('Location: index.php');
            exit;
        }
        registrarFalhaLogin($pdo);
        $erro = 'Usuário ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<title>Login - Painel Canal de Denúncias</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="assets/dashboard.css">
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
<div class="container" style="max-width:380px;margin-top:80px;">
    <img src="assets/logo-smartweb.svg" alt="Smartweb" class="login-logo">
    <h1 style="text-align:center;font-size:1.2rem;color:var(--cor-primaria);">Painel - Canal de Denúncias</h1>

    <?php if ($erro): ?>
        <div class="form-message erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST">
        <?= csrfCampo() ?>
        <?= honeypotCampo() ?>
        <div class="campo">
            <label for="username">Usuário</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>
        <div class="campo">
            <label for="senha">Senha</label>
            <div class="campo-senha">
                <input type="password" id="senha" name="senha" required>
                <button type="button" class="toggle-senha" data-alvo="senha" aria-label="Mostrar senha">👁</button>
            </div>
        </div>
        <div class="campo" style="display:flex;justify-content:center;margin:15px 0;">
            <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" data-theme="light"></div>
        </div>
        <button type="submit" class="btn btn-primario" style="width:100%;">Entrar</button>
        <a class="link-aux" href="esqueci.php">Esqueci minha senha</a>
    </form>
</div>
<script src="assets/painel.js"></script>
</body>
</html>
