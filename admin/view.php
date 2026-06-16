<?php
require_once __DIR__ . '/includes/auth.php';
$usuario = exigirLogin();
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
[$filtroSql, $filtroParams] = filtroEmpresaUsuario($usuario);

$sql = "SELECT d.*, e.nome AS empresa_nome, e.slug AS empresa_slug FROM denuncias d
        JOIN empresas e ON e.id = d.empresa_id
        WHERE d.id = ?" . $filtroSql;
$params = array_merge([$id], $filtroParams);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$d = $stmt->fetch();

if (!$d) {
    http_response_code(404);
    die('Denúncia não encontrada ou sem permissão de acesso.');
}

// Atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $novoStatus = $_POST['status'] ?? '';
    if (in_array($novoStatus, ['Novo', 'Em análise', 'Concluído'], true)) {
        $upd = $pdo->prepare('UPDATE denuncias SET status = ? WHERE id = ?');
        $upd->execute([$novoStatus, $d['id']]);
        $d['status'] = $novoStatus;
    }
}

function linha(string $label, ?string $valor): string {
    $valor = $valor === null || $valor === '' ? '-' : nl2br(htmlspecialchars($valor));
    return '<div class="label">' . htmlspecialchars($label) . '</div><div class="valor">' . $valor . '</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Denúncia <?= htmlspecialchars($d['protocolo']) ?> - Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<div class="container wide">
    <a href="index.php" class="voltar-link">&larr; Voltar à lista</a>

    <div class="topbar">
        <div class="topbar-brand">
            <img src="assets/logo-smartweb.svg" alt="Smartweb" class="brand-logo">
            <h1>Denúncia <?= htmlspecialchars($d['protocolo']) ?></h1>
        </div>
        <form method="POST" style="display:flex;gap:8px;align-items:center;">
            <?= csrfCampo() ?>
            <label for="status" style="margin:0;color:#fff;">Status:</label>
            <select name="status" id="status" onchange="this.form.submit()">
                <?php foreach (['Novo', 'Em análise', 'Concluído'] as $s): ?>
                    <option value="<?= $s ?>" <?= $d['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="detalhe-grid">
        <?= linha('Data/Hora', date('d/m/Y H:i:s', strtotime($d['created_at']))) ?>
        <?= linha('Empresa', $d['empresa_nome']) ?>
        <?= linha('Identificado', $d['identificado']) ?>
        <?= linha('Nome', $d['nome']) ?>

        <?= linha('1. Empresa onde ocorreu o fato', $d['empresa_fato']) ?>
        <?= linha('2. Vínculo com a empresa', $d['vinculo']) ?>
        <?= linha('3. Tipo de incidente', $d['tipo_incidente']) ?>
        <?= linha('4. Filial/Departamento', $d['filial_departamento']) ?>
        <?= linha('5. Resumo do fato', $d['resumo_fato']) ?>
        <?= linha('6. Como tomou conhecimento', $d['como_soube']) ?>

        <?= linha('7. Gerente/diretor ciente', $d['gerente_ciente']) ?>
        <?= linha('8. Gestores participaram', $d['gestores_participaram']) ?>
        <?= linha('9. Afastamentos recentes', $d['afastamentos_recentes']) ?>
        <?= linha('Descrição dos afastamentos', $d['descricao_afastamentos']) ?>
        <?= linha('10. Alta rotatividade', $d['alta_rotatividade']) ?>
        <?= linha('Descrição da rotatividade', $d['descricao_rotatividade']) ?>
        <?= linha('11. Testemunhas', $d['testemunhas']) ?>

        <div class="label">12. Evidência anexada</div>
        <div class="valor">
            <?php if ($d['evidencia_path']): ?>
                <a href="evidencia.php?id=<?= $d['id'] ?>" target="_blank">Ver/baixar arquivo</a>
            <?php else: ?>
                -
            <?php endif; ?>
        </div>

        <?= linha('13. Sugestões', $d['sugestoes']) ?>
        <?= linha('14. Já relatou anteriormente', $d['ja_relatou']) ?>
        <?= linha('15. Acredita em medidas preventivas', $d['medidas_prevencao']) ?>
        <?= linha('16. Falha em processos internos', $d['falha_processos']) ?>
        <?= linha('17. Buscou suporte', $d['buscou_suporte']) ?>
        <?= linha('18. Necessidade de apoio psicológico', $d['apoio_psicologico']) ?>
        <?= linha('19. Presenciou situação semelhante', $d['presenciou_similar']) ?>

        <?= linha('60+ anos', $d['idade_60mais']) ?>
        <?= linha('Gênero', $d['genero']) ?>
        <?= linha('Possui deficiência', $d['deficiencia']) ?>
    </div>
</div>
</body>
</html>
