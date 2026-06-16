<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = getDB();
$erro = null;
$sucesso = false;

// Token vem na query (GET) e também trafega num hidden no POST
$token = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['token'] ?? '')
    : ($_GET['token'] ?? '');
$token = preg_replace('/[^a-f0-9]/i', '', $token);

$dadosToken = validarTokenReset($pdo, $token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif (!honeypotVazio()) {
        $erro = 'Não foi possível processar a solicitação.';
    } elseif (!captchaValido()) {
        $erro = 'Resposta da verificação incorreta. Tente novamente.';
    } elseif (!$dadosToken) {
        $erro = 'Link inválido ou expirado. Solicite um novo.';
    } else {
        $nova = $_POST['nova_senha'] ?? '';
        $confirmar = $_POST['confirmar_senha'] ?? '';
        if (strlen($nova) < 8) {
            $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($nova !== $confirmar) {
            $erro = 'A confirmação da senha não confere.';
        } else {
            $hash = password_hash($nova, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE admin_usuarios SET password_hash = ? WHERE id = ?')
                ->execute([$hash, (int)$dadosToken['id']]);
            consumirTokenReset($pdo, (int)$dadosToken['reset_id']);
            $sucesso = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Criar nova senha - Painel Canal de Denúncias</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<div class="container" style="max-width:380px;margin-top:80px;">
    <img src="assets/logo-smartweb.svg" alt="Smartweb" class="login-logo">
    <h1 style="text-align:center;font-size:1.2rem;color:var(--cor-primaria);">Criar nova senha</h1>

    <?php if ($sucesso): ?>
        <div class="form-message sucesso">Senha alterada com sucesso! Você já pode entrar com a nova senha.</div>
        <a class="link-aux" href="login.php">Ir para o login</a>
    <?php elseif (!$dadosToken && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <div class="form-message erro">Link inválido ou expirado. Solicite um novo link de recuperação.</div>
        <a class="link-aux" href="esqueci.php">Solicitar novo link</a>
    <?php else: ?>
        <?php if ($erro): ?><div class="form-message erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
        <?php if ($dadosToken): ?>
            <p style="font-size:0.88rem;color:#555;">Olá, <?= htmlspecialchars($dadosToken['nome']) ?>. Defina sua nova senha de acesso.</p>
        <?php endif; ?>
        <form method="POST">
            <?= csrfCampo() ?>
            <?= honeypotCampo() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="campo">
                <label for="nova_senha">Nova senha (mín. 8 caracteres)</label>
                <div class="campo-senha">
                    <input type="password" id="nova_senha" name="nova_senha" required autocomplete="new-password">
                    <button type="button" class="toggle-senha" data-alvo="nova_senha" aria-label="Mostrar senha">👁</button>
                </div>
            </div>
            <div class="campo">
                <label for="confirmar_senha">Confirmar nova senha</label>
                <div class="campo-senha">
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required autocomplete="new-password">
                    <button type="button" class="toggle-senha" data-alvo="confirmar_senha" aria-label="Mostrar senha">👁</button>
                </div>
            </div>
            <?= captchaCampo() ?>
            <button type="submit" class="btn btn-primario" style="width:100%;">Salvar nova senha</button>
            <a class="link-aux" href="login.php">Voltar ao login</a>
        </form>
    <?php endif; ?>
</div>
<script src="assets/painel.js"></script>
</body>
</html>
