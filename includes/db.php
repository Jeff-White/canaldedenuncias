<?php
require_once __DIR__ . '/../config/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/**
 * Busca os dados da empresa pelo slug informado na URL (?empresa=alibras).
 * Retorna null se não existir ou estiver inativa.
 */
function getEmpresaBySlug(string $slug): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM empresas WHERE slug = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $empresa = $stmt->fetch();
    return $empresa ?: null;
}

/**
 * Busca os dados da empresa pelo parâmetro (hash) informado na URL (?par=GHB3SD).
 * Retorna null se não existir ou estiver inativa.
 */
function getEmpresaByParam(string $par): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM empresas WHERE parametro = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$par]);
    $empresa = $stmt->fetch();
    return $empresa ?: null;
}

/**
 * Gera um protocolo único no formato DEN-AAAA-NNNN
 */
function gerarProtocolo(PDO $pdo): string {
    $ano = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM denuncias WHERE protocolo LIKE ?");
    $stmt->execute(["DEN-$ano-%"]);
    $total = (int)$stmt->fetch()['total'] + 1;
    do {
        $protocolo = sprintf('DEN-%s-%04d', $ano, $total);
        $check = $pdo->prepare('SELECT COUNT(*) AS total FROM denuncias WHERE protocolo = ?');
        $check->execute([$protocolo]);
        $total++;
    } while ((int)$check->fetch()['total'] > 0);
    return $protocolo;
}
