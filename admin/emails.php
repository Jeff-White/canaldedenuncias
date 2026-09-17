<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/zeptomail.php';
$usuario = exigirAdmin();
$pdo = getDB();

$msg = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $acao = $_POST['acao'] ?? 'salvar';

    if ($acao === 'testar') {
        $emailTeste = trim((string)($_POST['email_teste'] ?? ''));
        if (!filter_var($emailTeste, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um e-mail válido para o teste.';
        } else {
            $assunto = 'Teste de envio - Canal de Denúncias [' . date('d/m/Y H:i') . ']';
            $corpo = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#333;line-height:1.6;">'
                   . '<h2 style="color:#0d3b66;">Teste de Notificação - Canal de Denúncias</h2>'
                   . '<p>Este é um e-mail de teste disparado pelo painel administrativo.</p>'
                   . '<p><strong>Status:</strong> A integração com a API ZeptoMail está ativa e funcionando perfeitamente!</p>'
                   . '<p style="color:#777;font-size:12px;margin-top:20px;border-top:1px solid #eee;padding-top:10px;">'
                   . 'Disparado por: ' . htmlspecialchars($usuario['nome']) . ' (' . htmlspecialchars($usuario['username']) . ') em ' . date('d/m/Y H:i:s') . '</p>'
                   . '</div>';

            $ok = enviarEmailZepto($emailTeste, $assunto, $corpo);
            if ($ok) {
                $msg = 'E-mail de teste enviado com sucesso para: ' . htmlspecialchars($emailTeste);
            } else {
                $erro = 'Falha ao disparar o e-mail via ZeptoMail. Verifique se o token e o e-mail remetente em config/config.php estão autorizados.';
            }
        }
    } else {
        $entradas = $_POST['email_destino'] ?? [];

        $erros = [];
        $normalizados = []; // empresa_id => "a@x.com, b@y.com"

        foreach ($entradas as $empresaId => $texto) {
            $empresaId = (int)$empresaId;
            // aceita separação por vírgula, ponto-e-vírgula ou quebra de linha
            $partes = preg_split('/[\s,;]+/', trim((string)$texto), -1, PREG_SPLIT_NO_EMPTY);
            $validos = [];
            foreach ($partes as $email) {
                $email = trim($email);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $erros[] = 'E-mail inválido: "' . htmlspecialchars($email) . '"';
                } elseif (!in_array($email, $validos, true)) {
                    $validos[] = $email;
                }
            }
            if (empty($validos)) {
                $erros[] = 'Cada empresa precisa de pelo menos um e-mail de destino.';
            }
            $normalizados[$empresaId] = implode(', ', $validos);
        }

        if (empty($erros)) {
            $upd = $pdo->prepare('UPDATE empresas SET email_destino = ? WHERE id = ?');
            foreach ($normalizados as $empresaId => $emails) {
                $upd->execute([$emails, $empresaId]);
            }
            $msg = 'E-mails de destino atualizados com sucesso.';
        } else {
            $erro = implode(' ', array_unique($erros));
        }
    }
}

$empresas = $pdo->query('SELECT id, nome, email_destino FROM empresas ORDER BY nome')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<title>E-mails de destino - Painel Canal de Denúncias</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<div class="container wide">
    <div class="topbar">
        <div class="topbar-brand">
            <img src="assets/logo-smartweb.svg" alt="Smartweb" class="brand-logo">
            <h1>E-mails que recebem as denúncias</h1>
        </div>
        <div class="user-info">
            Olá, <?= htmlspecialchars($usuario['nome']) ?>
            <?= renderMenuAdmin($usuario, 'emails.php') ?>
        </div>
    </div>

    <?php if ($msg): ?><div class="form-message sucesso"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="form-message erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <p style="font-size:0.88rem;color:#555;">A cada nova denúncia, uma notificação é enviada para os e-mails abaixo, conforme a empresa. Você pode informar <strong>vários e-mails por empresa</strong> — um por linha (ou separados por vírgula).</p>

    <form method="POST">
        <?= csrfCampo() ?>
        <input type="hidden" name="acao" value="salvar">
        <?php foreach ($empresas as $e): ?>
            <?php $linhas = implode("\n", preg_split('/[\s,;]+/', (string)$e['email_destino'], -1, PREG_SPLIT_NO_EMPTY)); ?>
            <div class="campo">
                <label for="emp_<?= (int)$e['id'] ?>"><strong><?= htmlspecialchars($e['nome']) ?></strong></label>
                <textarea id="emp_<?= (int)$e['id'] ?>" name="email_destino[<?= (int)$e['id'] ?>]" rows="3" placeholder="exemplo@empresa.com.br"><?= htmlspecialchars($linhas) ?></textarea>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primario">Salvar e-mails</button>
    </form>

    <hr style="margin:40px 0 24px;border:none;border-top:1px solid #e2e8f0;">

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;max-width:540px;">
        <h3 style="margin-top:0;font-size:1rem;color:var(--sw-azul-escuro);">Testar envio via ZeptoMail</h3>
        <p style="font-size:0.84rem;color:#666;margin-bottom:14px;">Envie uma mensagem de teste para confirmar que o envio de e-mails está funcionando corretamente:</p>
        <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;">
            <?= csrfCampo() ?>
            <input type="hidden" name="acao" value="testar">
            <input type="email" name="email_teste" placeholder="seu-email@dominio.com" required style="flex:1;min-width:220px;padding:8px 12px;font-size:0.9rem;">
            <button type="submit" class="btn btn-primario" style="padding:8px 16px;font-size:0.88rem;">Enviar Teste</button>
        </form>
    </div>
</div>
</body>
</html>
