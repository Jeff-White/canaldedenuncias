<?php
require_once __DIR__ . '/includes/auth.php';
$usuario = exigirAdmin();
$pdo = getDB();

$msg = null;
$erro = null;

// ---------- Ações (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar' || $acao === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $nivel = ($_POST['nivel'] ?? 'editor') === 'admin' ? 'admin' : 'editor';
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $senha = $_POST['senha'] ?? '';

        if ($nome === '' || $username === '') {
            $erro = 'Nome e usuário (e-mail) são obrigatórios.';
        } elseif ($acao === 'criar' && strlen($senha) < 8) {
            $erro = 'A senha deve ter pelo menos 8 caracteres.';
        } elseif ($acao === 'editar' && $senha !== '' && strlen($senha) < 8) {
            $erro = 'A senha deve ter pelo menos 8 caracteres.';
        } else {
            // checa unicidade do username
            $chk = $pdo->prepare('SELECT id FROM admin_usuarios WHERE username = ? AND id <> ? LIMIT 1');
            $chk->execute([$username, $acao === 'editar' ? $id : 0]);
            if ($chk->fetch()) {
                $erro = 'Já existe um usuário com este e-mail.';
            } elseif ($acao === 'criar') {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO admin_usuarios (username, password_hash, nivel, nome, ativo, empresa_id) VALUES (?, ?, ?, ?, ?, NULL)');
                $ins->execute([$username, $hash, $nivel, $nome, $ativo]);
                $msg = 'Usuário criado com sucesso.';
            } else {
                // editar — evita o admin remover o próprio acesso de admin ou se desativar
                if ($id === (int)$usuario['id'] && ($nivel !== 'admin' || $ativo !== 1)) {
                    $erro = 'Você não pode rebaixar ou desativar a si mesmo.';
                } else {
                    if ($senha !== '') {
                        $hash = password_hash($senha, PASSWORD_DEFAULT);
                        $upd = $pdo->prepare('UPDATE admin_usuarios SET nome = ?, username = ?, nivel = ?, ativo = ?, password_hash = ? WHERE id = ?');
                        $upd->execute([$nome, $username, $nivel, $ativo, $hash, $id]);
                    } else {
                        $upd = $pdo->prepare('UPDATE admin_usuarios SET nome = ?, username = ?, nivel = ?, ativo = ? WHERE id = ?');
                        $upd->execute([$nome, $username, $nivel, $ativo, $id]);
                    }
                    $msg = 'Usuário atualizado com sucesso.';
                }
            }
        }
    }
}

// ---------- Carrega usuário para edição ----------
$editando = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT id, username, nivel, nome, ativo FROM admin_usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_GET['edit']]);
    $editando = $stmt->fetch() ?: null;
}

$usuarios = $pdo->query('SELECT id, username, nivel, nome, ativo, created_at FROM admin_usuarios ORDER BY nome')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<title>Usuários - Painel Canal de Denúncias</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<div class="container wide">
    <div class="topbar">
        <div class="topbar-brand">
            <img src="assets/logo-smartweb.svg" alt="Smartweb" class="brand-logo">
            <h1>Gerenciar Usuários</h1>
        </div>
        <div class="user-info">
            Olá, <?= htmlspecialchars($usuario['nome']) ?>
            <?= renderMenuAdmin($usuario, 'usuarios.php') ?>
        </div>
    </div>

    <?php if ($msg): ?><div class="form-message sucesso"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="form-message erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <h2 style="font-size:1.1rem;color:var(--cor-primaria);"><?= $editando ? 'Editar usuário' : 'Novo usuário' ?></h2>
    <form method="POST" class="form-usuario" style="margin-bottom:30px;">
        <?= csrfCampo() ?>
        <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'criar' ?>">
        <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$editando['id'] ?>"><?php endif; ?>

        <div class="campo">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($editando['nome'] ?? '') ?>" required>
        </div>
        <div class="campo">
            <label for="username">Usuário (e-mail)</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($editando['username'] ?? '') ?>" required>
        </div>
        <div class="campo">
            <label for="nivel">Nível</label>
            <select name="nivel" id="nivel">
                <option value="editor" <?= ($editando['nivel'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor (vê tudo)</option>
                <option value="admin" <?= ($editando['nivel'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador (vê tudo + gerencia usuários)</option>
            </select>
        </div>
        <div class="campo">
            <label for="senha">Senha <?= $editando ? '(deixe em branco para manter a atual)' : '(mín. 8 caracteres)' ?></label>
            <input type="password" id="senha" name="senha" <?= $editando ? '' : 'required' ?> autocomplete="new-password">
        </div>
        <div class="campo">
            <label class="checkbox-option"><input type="checkbox" name="ativo" value="1" <?= (!$editando || $editando['ativo']) ? 'checked' : '' ?>> Ativo</label>
        </div>
        <div class="campo">
            <button type="submit" class="btn btn-primario"><?= $editando ? 'Salvar alterações' : 'Criar usuário' ?></button>
            <?php if ($editando): ?><a href="usuarios.php" class="btn btn-secundario">Cancelar</a><?php endif; ?>
        </div>
    </form>

    <table class="denuncias">
        <thead>
            <tr><th>Nome</th><th>Usuário</th><th>Nível</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nome']) ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= $u['nivel'] === 'admin' ? 'Administrador' : 'Editor' ?></td>
                    <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                    <td><a href="usuarios.php?edit=<?= (int)$u['id'] ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
