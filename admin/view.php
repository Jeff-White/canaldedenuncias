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
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
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

    <?php $telemetria = !empty($d['dispositivo_info']) ? json_decode($d['dispositivo_info'], true) : null; ?>
    <div style="margin-top:28px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h3 style="margin-top:0;margin-bottom:16px;font-size:1.02rem;color:var(--sw-azul-escuro);display:flex;align-items:center;gap:8px;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Dados da Máquina, IP e Conexão (Auditoria)
        </h3>

        <?php if ($telemetria || !empty($d['ip_origem'])): ?>
            <div class="detalhe-grid" style="margin-top:0;">
                <?= linha('IP de Envio', $telemetria['ip'] ?? $d['ip_origem'] ?? '-') ?>
                <?= linha('Nome do Host / Provedor', $telemetria['hostname_reverso'] ?? '-') ?>
                <?= linha('Sistema Operacional', $telemetria['sistema_operacional'] ?? '-') ?>
                <?= linha('Navegador', $telemetria['navegador'] ?? '-') ?>
                <?= linha('Tipo de Dispositivo', $telemetria['tipo_dispositivo'] ?? '-') ?>
                <?= linha('Resolução de Tela', $telemetria['resolucao'] ?? '-') ?>
                <?= linha('Fuso Horário', $telemetria['fuso_horario'] ?? '-') ?>
                <?= linha('Idioma do Navegador', $telemetria['idioma'] ?? '-') ?>
                <?= linha('Hardware / Processador', !empty($telemetria['cores_cpu']) ? $telemetria['cores_cpu'] . ' núcleos lógicos' : '-') ?>
                <?= linha('Memória RAM Estimada', $telemetria['memoria_ram'] ?? '-') ?>
                <?= linha('Tela Touch', $telemetria['tela_touch'] ?? '-') ?>
                <?= linha('Data / Hora do Envio', date('d/m/Y H:i:s', strtotime($d['created_at']))) ?>
                <div class="label" style="grid-column:1/-1;">User-Agent Completo da Máquina</div>
                <div class="valor" style="grid-column:1/-1;font-family:monospace;font-size:0.8rem;word-break:break-all;background:#f8fafc;padding:8px 12px;border-radius:6px;border:1px solid #e2e8f0;">
                    <?= htmlspecialchars($telemetria['user_agent_completo'] ?? $d['user_agent'] ?? '-') ?>
                </div>
            </div>
        <?php else: ?>
            <p style="font-size:0.88rem;color:#64748b;margin:0;">
                Esta denúncia foi registrada antes da ativação do rastreamento de máquina/IP.
                <?php if (!empty($d['ip_hash'])): ?>
                    <br><span style="font-size:0.8rem;color:#94a3b8;">Hash diário de controle do envio: <code><?= htmlspecialchars($d['ip_hash']) ?></code></span>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
