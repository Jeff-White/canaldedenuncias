<?php
require_once __DIR__ . '/includes/auth.php';
$usuario = exigirLogin();
$pdo = getDB();

$msg = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $nome = trim($_POST['nome'] ?? '');
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    if ($nome === '') {
        $erro = 'O nome é obrigatório.';
    } else {
        // Busca o hash atual para validar a senha antes de qualquer alteração sensível
        $stmt = $pdo->prepare('SELECT password_hash FROM admin_usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$usuario['id']]);
        $row = $stmt->fetch();

        $querTrocarSenha = $novaSenha !== '' || $confirmar !== '';

        if ($querTrocarSenha) {
            if (!$row || !password_verify($senhaAtual, $row['password_hash'])) {
                $erro = 'Senha atual incorreta.';
            } elseif (strlen($novaSenha) < 8) {
                $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
            } elseif ($novaSenha !== $confirmar) {
                $erro = 'A confirmação da nova senha não confere.';
            }
        }

        if (!$erro) {
            if ($querTrocarSenha) {
                $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $upd = $pdo->prepare('UPDATE admin_usuarios SET nome = ?, password_hash = ? WHERE id = ?');
                $upd->execute([$nome, $hash, (int)$usuario['id']]);
            } else {
                $upd = $pdo->prepare('UPDATE admin_usuarios SET nome = ? WHERE id = ?');
                $upd->execute([$nome, (int)$usuario['id']]);
            }
            // Atualiza o nome na sessão
            $_SESSION['admin_user']['nome'] = $nome;
            $usuario['nome'] = $nome;
            $msg = 'Dados atualizados com sucesso.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Meus dados - Painel Canal de Denúncias</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<div class="container" style="max-width:480px;">
    <div class="topbar">
        <div class="topbar-brand">
            <img src="assets/logo-smartweb.svg" alt="Smartweb" class="brand-logo">
            <h1>Meus dados</h1>
        </div>
        <div class="user-info">
            <nav class="menu-admin">
                <a href="index.php">Denúncias</a>
                <?php if (ehAdmin($usuario)): ?><a href="usuarios.php">Usuários</a><a href="emails.php">E-mails</a><?php endif; ?>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </div>

    <?php if ($msg): ?><div class="form-message sucesso"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="form-message erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <form method="POST">
        <?= csrfCampo() ?>
        <div class="campo">
            <label for="username">Usuário (e-mail)</label>
            <input type="text" id="username" value="<?= htmlspecialchars($usuario['username']) ?>" disabled>
            <small>O e-mail de acesso só pode ser alterado por um administrador.</small>
        </div>
        <div class="campo">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
        </div>

        <h2 style="font-size:1rem;color:var(--cor-primaria);margin-top:20px;">Alterar senha (opcional)</h2>
        <div class="campo">
            <label for="senha_atual">Senha atual</label>
            <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password">
        </div>
        <div class="campo">
            <label for="nova_senha">Nova senha (mín. 8 caracteres)</label>
            <input type="password" id="nova_senha" name="nova_senha" autocomplete="new-password">
        </div>
        <div class="campo">
            <label for="confirmar_senha">Confirmar nova senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primario">Salvar</button>
    </form>
</div>
</body>
</html>
