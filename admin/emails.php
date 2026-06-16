<?php
require_once __DIR__ . '/includes/auth.php';
$usuario = exigirAdmin();
$pdo = getDB();

$msg = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
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

$empresas = $pdo->query('SELECT id, nome, email_destino FROM empresas ORDER BY nome')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
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
            <nav class="menu-admin">
                <a href="index.php">Denúncias</a>
                <a href="usuarios.php">Usuários</a>
                <a href="perfil.php">Meus dados</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </div>

    <?php if ($msg): ?><div class="form-message sucesso"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="form-message erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <p style="font-size:0.88rem;color:#555;">A cada nova denúncia, uma notificação é enviada para os e-mails abaixo, conforme a empresa. Você pode informar <strong>vários e-mails por empresa</strong> — um por linha (ou separados por vírgula).</p>

    <form method="POST">
        <?= csrfCampo() ?>
        <?php foreach ($empresas as $e): ?>
            <?php $linhas = implode("\n", preg_split('/[\s,;]+/', (string)$e['email_destino'], -1, PREG_SPLIT_NO_EMPTY)); ?>
            <div class="campo">
                <label for="emp_<?= (int)$e['id'] ?>"><strong><?= htmlspecialchars($e['nome']) ?></strong></label>
                <textarea id="emp_<?= (int)$e['id'] ?>" name="email_destino[<?= (int)$e['id'] ?>]" rows="3" placeholder="exemplo@empresa.com.br"><?= htmlspecialchars($linhas) ?></textarea>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primario">Salvar e-mails</button>
    </form>
</div>
</body>
</html>
