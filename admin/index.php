<?php
require_once __DIR__ . '/includes/auth.php';
$usuario = exigirLogin();
$pdo = getDB();

[$filtroSql, $filtroParams] = filtroEmpresaUsuario($usuario);

// ---------- Filtros ----------
$empresaFiltro = isset($_GET['empresa_id']) && $_GET['empresa_id'] !== '' ? (int)$_GET['empresa_id'] : null;
$statusFiltro = $_GET['status'] ?? '';
$busca = trim($_GET['busca'] ?? '');

$where = ['1=1'];
$params = [];

if ($filtroSql !== '') {
    $where[] = trim(substr($filtroSql, 5)); // remove " AND "
    $params = array_merge($params, $filtroParams);
}

if ($empresaFiltro) {
    $where[] = 'd.empresa_id = ?';
    $params[] = $empresaFiltro;
}

if ($statusFiltro !== '' && in_array($statusFiltro, ['Novo', 'Em análise', 'Concluído'], true)) {
    $where[] = 'd.status = ?';
    $params[] = $statusFiltro;
}

if ($busca !== '') {
    $where[] = '(d.protocolo LIKE ? OR d.empresa_fato LIKE ? OR d.resumo_fato LIKE ?)';
    $like = '%' . $busca . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);

// ---------- Paginação ----------
$porPagina = 20;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

$totalStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM denuncias d WHERE $whereSql");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetch()['total'];
$totalPaginas = max(1, (int)ceil($total / $porPagina));

$sql = "SELECT d.*, e.nome AS empresa_nome FROM denuncias d
        JOIN empresas e ON e.id = d.empresa_id
        WHERE $whereSql
        ORDER BY d.created_at DESC
        LIMIT $porPagina OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$denuncias = $stmt->fetchAll();

// Lista de empresas para o filtro (apenas se usuário tiver acesso a todas)
$empresas = [];
if ($usuario['empresa_id'] === null) {
    $empresas = $pdo->query('SELECT id, nome FROM empresas ORDER BY nome')->fetchAll();
}

function badgeStatus(string $status): string {
    $classe = [
        'Novo' => 'badge-novo',
        'Em análise' => 'badge-analise',
        'Concluído' => 'badge-concluido',
    ][$status] ?? '';
    return '<span class="badge ' . $classe . '">' . htmlspecialchars($status) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - Canal de Denúncias</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<div class="container wide">
    <div class="topbar">
        <div class="topbar-brand">
            <img src="assets/logo-smartweb.svg" alt="Smartweb" class="brand-logo">
            <h1>Canal de Denúncias - Painel</h1>
        </div>
        <div class="user-info">
            Olá, <?= htmlspecialchars($usuario['nome']) ?>
            <nav class="menu-admin">
                <a href="perfil.php">Meus dados</a>
                <?php if (ehAdmin($usuario)): ?>
                    <a href="usuarios.php">Usuários</a>
                    <a href="emails.php">E-mails</a>
                <?php endif; ?>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </div>

    <form method="GET" class="filtros">
        <?php if ($usuario['empresa_id'] === null): ?>
        <div class="campo">
            <label for="empresa_id">Empresa</label>
            <select name="empresa_id" id="empresa_id">
                <option value="">Todas</option>
                <?php foreach ($empresas as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $empresaFiltro === (int)$e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="campo">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="">Todos</option>
                <?php foreach (['Novo', 'Em análise', 'Concluído'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFiltro === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="busca">Buscar (protocolo, empresa, resumo)</label>
            <input type="text" name="busca" id="busca" value="<?= htmlspecialchars($busca) ?>">
        </div>

        <div class="campo">
            <button type="submit" class="btn btn-primario">Filtrar</button>
        </div>
    </form>

    <table class="denuncias">
        <thead>
            <tr>
                <th>Protocolo</th>
                <th>Data</th>
                <th>Empresa</th>
                <th>Tipo de incidente</th>
                <th>Identificado</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($denuncias)): ?>
                <tr><td colspan="7" style="text-align:center;color:#888;">Nenhuma denúncia encontrada.</td></tr>
            <?php endif; ?>
            <?php foreach ($denuncias as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['protocolo']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
                    <td><?= htmlspecialchars($d['empresa_nome']) ?></td>
                    <td><?= htmlspecialchars($d['tipo_incidente']) ?></td>
                    <td><?= htmlspecialchars($d['identificado']) ?></td>
                    <td><?= badgeStatus($d['status']) ?></td>
                    <td><a href="view.php?id=<?= $d['id'] ?>">Ver detalhes</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPaginas > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <?php
                $qs = $_GET;
                $qs['pagina'] = $p;
            ?>
            <a href="?<?= http_build_query($qs) ?>" class="<?= $p === $pagina ? 'ativo' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
