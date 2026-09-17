<?php
require_once __DIR__ . '/includes/auth.php';
$usuario = exigirAdmin();
$pdo = getDB();

// Apenas o Jeff pode gerar novos códigos automáticos
$podeGerar = strtolower(trim($usuario['username'])) === 'jeff@smartweb.com.br';

$msg = null;
$erro = null;

/** Gera um código aleatório de 6 caracteres (A-Z, 0-9), garantindo unicidade. */
function gerarParametroUnico(PDO $pdo): string {
    $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $codigo = '';
        for ($i = 0; $i < 6; $i++) {
            $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        $stmt = $pdo->prepare('SELECT 1 FROM empresas WHERE parametro = ? LIMIT 1');
        $stmt->execute([$codigo]);
    } while ($stmt->fetch());
    return $codigo;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'gerar') {
        if (!$podeGerar) {
            http_response_code(403);
            die('Apenas o administrador responsável pode gerar novos códigos.');
        }
        // Gera e já salva um novo código para uma empresa
        $id = (int)($_POST['id'] ?? 0);
        $novo = gerarParametroUnico($pdo);
        $pdo->prepare('UPDATE empresas SET parametro = ? WHERE id = ?')->execute([$novo, $id]);
        $msg = 'Novo link gerado com sucesso.';
    } elseif ($acao === 'salvar') {
        // Salva os códigos digitados manualmente
        $entradas = $_POST['parametro'] ?? [];
        $erros = [];
        $vistos = [];
        $aplicar = [];
        foreach ($entradas as $id => $valor) {
            $id = (int)$id;
            $valor = trim((string)$valor);
            if (!preg_match('/^[A-Za-z0-9_-]{4,20}$/', $valor)) {
                $erros[] = 'Código inválido: "' . htmlspecialchars($valor) . '" (use 4 a 20 letras/números, sem espaços).';
                continue;
            }
            $chaveDup = strtoupper($valor);
            if (isset($vistos[$chaveDup])) {
                $erros[] = 'Código repetido entre empresas: "' . htmlspecialchars($valor) . '".';
                continue;
            }
            $vistos[$chaveDup] = true;
            $aplicar[$id] = $valor;
        }

        if (empty($erros)) {
            // checa unicidade contra o banco (outras empresas)
            $upd = $pdo->prepare('UPDATE empresas SET parametro = ? WHERE id = ?');
            $chk = $pdo->prepare('SELECT id FROM empresas WHERE parametro = ? AND id <> ? LIMIT 1');
            foreach ($aplicar as $id => $valor) {
                $chk->execute([$valor, $id]);
                if ($chk->fetch()) {
                    $erros[] = 'O código "' . htmlspecialchars($valor) . '" já está em uso por outra empresa.';
                }
            }
            if (empty($erros)) {
                foreach ($aplicar as $id => $valor) {
                    $upd->execute([$valor, $id]);
                }
                $msg = 'Links atualizados com sucesso.';
            }
        }
        if (!empty($erros)) {
            $erro = implode(' ', $erros);
        }
    }
}

$empresas = $pdo->query('SELECT id, nome, parametro FROM empresas ORDER BY nome')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<title>Empresas - Painel Canal de Denúncias</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<div class="container wide">
    <div class="topbar">
        <div class="topbar-brand">
            <img src="assets/logo-smartweb.svg" alt="Smartweb" class="brand-logo">
            <h1>Empresas e links</h1>
        </div>
        <div class="user-info">
            Olá, <?= htmlspecialchars($usuario['nome']) ?>
            <?= renderMenuAdmin($usuario, 'empresas.php') ?>
        </div>
    </div>

    <?php if ($msg): ?><div class="form-message sucesso"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="form-message erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <p style="font-size:0.88rem;color:#555;">Cada empresa tem um <strong>código</strong> que identifica o formulário pela URL. Use o link abaixo no site de cada empresa. Você pode <strong>gerar um novo código automático</strong> ou <strong>digitar um código manualmente</strong> e salvar.</p>

    <form method="POST" id="formEmpresas">
        <?= csrfCampo() ?>
        <input type="hidden" name="acao" value="salvar">

        <table class="denuncias tabela-empresas">
            <thead>
                <tr>
                    <th style="width:160px;">Empresa</th>
                    <th style="width:150px;">Código</th>
                    <th>Link do formulário</th>
                    <th style="width:230px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($empresas as $e): ?>
                    <tr data-id="<?= (int)$e['id'] ?>">
                        <td><strong><?= htmlspecialchars($e['nome']) ?></strong></td>
                        <td>
                            <input type="text" class="campo-codigo" name="parametro[<?= (int)$e['id'] ?>]"
                                   value="<?= htmlspecialchars($e['parametro']) ?>" maxlength="20" autocomplete="off">
                        </td>
                        <td>
                            <input type="text" class="campo-link" readonly value="">
                        </td>
                        <td class="acoes-empresa">
                            <button type="button" class="btn btn-secundario btn-mini btn-copiar">Copiar link</button>
                            <?php if ($podeGerar): ?>
                                <button type="button" class="btn btn-secundario btn-mini btn-gerar">Gerar novo</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:18px;">
            <button type="submit" class="btn btn-primario">Salvar códigos</button>
        </div>
    </form>

    <!-- Formulário oculto para gerar+salvar um código automático no servidor -->
    <form method="POST" id="formGerar" style="display:none;">
        <?= csrfCampo() ?>
        <input type="hidden" name="acao" value="gerar">
        <input type="hidden" name="id" id="gerarId" value="">
    </form>
</div>

<script>
(function () {
    var base = <?= json_encode(baseUrl() . '/?par=') ?>;

    function atualizarLink(tr) {
        var codigo = tr.querySelector('.campo-codigo').value.trim();
        tr.querySelector('.campo-link').value = codigo ? (base + codigo) : '';
    }

    document.querySelectorAll('.tabela-empresas tbody tr').forEach(function (tr) {
        atualizarLink(tr);

        tr.querySelector('.campo-codigo').addEventListener('input', function () {
            atualizarLink(tr);
        });

        // Copiar link
        tr.querySelector('.btn-copiar').addEventListener('click', function () {
            var campo = tr.querySelector('.campo-link');
            if (!campo.value) { return; }
            navigator.clipboard.writeText(campo.value).then(function () {
                var b = tr.querySelector('.btn-copiar');
                var txt = b.textContent;
                b.textContent = 'Copiado!';
                setTimeout(function () { b.textContent = txt; }, 1500);
            }).catch(function () {
                campo.select();
                document.execCommand('copy');
            });
        });

        // Gerar novo (salva no servidor para garantir unicidade) — só existe para o admin responsável
        var btnGerar = tr.querySelector('.btn-gerar');
        if (btnGerar) {
            btnGerar.addEventListener('click', function () {
                if (!confirm('Gerar um novo código para esta empresa? O link atual deixará de funcionar.')) { return; }
                document.getElementById('gerarId').value = tr.dataset.id;
                document.getElementById('formGerar').submit();
            });
        }
    });
})();
</script>
</body>
</html>
