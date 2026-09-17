<?php
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    $seguro = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => $seguro,
        'samesite' => 'Lax',
    ]);
    session_name(SESSION_NAME);
    session_start();
}

// ---------- Sessão / usuário ----------
function usuarioLogado(): ?array {
    return $_SESSION['admin_user'] ?? null;
}

function exigirLogin(): array {
    $user = usuarioLogado();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function ehAdmin(?array $usuario = null): bool {
    $usuario = $usuario ?? usuarioLogado();
    return ($usuario['nivel'] ?? '') === 'admin';
}

function exigirAdmin(): array {
    $user = exigirLogin();
    if (!ehAdmin($user)) {
        http_response_code(403);
        die('Acesso restrito a administradores.');
    }
    return $user;
}

/**
 * Renderiza o menu do painel (igual em todas as páginas),
 * destacando a página ativa em vez de ocultá-la.
 */
function renderMenuAdmin(array $usuario, string $ativo): string {
    $itens = [
        'index.php'    => ['Denúncias',  false],
        'empresas.php' => ['Empresas',   true],
        'usuarios.php' => ['Usuários',   true],
        'emails.php'   => ['E-mails',    true],
        'perfil.php'   => ['Meus dados', false],
    ];
    $html = '<nav class="menu-admin">';
    foreach ($itens as $arquivo => $info) {
        if ($info[1] && !ehAdmin($usuario)) {
            continue;
        }
        $cls = $arquivo === $ativo ? ' class="ativo"' : '';
        $html .= '<a href="' . $arquivo . '"' . $cls . '>' . htmlspecialchars($info[0]) . '</a>';
    }
    $html .= '<a href="logout.php" class="sair">Sair</a>';
    $html .= '</nav>';
    return $html;
}

// ---------- CSRF ----------
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCampo(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function csrfValido(): bool {
    $enviado = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $enviado);
}

function exigirCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrfValido()) {
        http_response_code(419);
        die('Sessão expirada ou requisição inválida. Recarregue a página e tente novamente.');
    }
}

// ---------- Captcha (pergunta matemática, sem serviço externo) ----------
function captchaCampo(): string {
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['captcha_resposta'] = (string)($a + $b);
    return '<div class="campo"><label for="captcha">Quanto é <strong>' . $a . ' + ' . $b . '</strong>? <span class="req">*</span></label>'
         . '<input type="text" id="captcha" name="captcha" inputmode="numeric" autocomplete="off" required></div>';
}

function captchaValido(): bool {
    $resp = trim((string)($_POST['captcha'] ?? ''));
    return isset($_SESSION['captcha_resposta']) && hash_equals($_SESSION['captcha_resposta'], $resp);
}

// ---------- Honeypot (campo oculto que só bots preenchem) ----------
function honeypotCampo(): string {
    return '<div class="hp-campo" aria-hidden="true">'
         . '<label>Não preencha este campo</label>'
         . '<input type="text" name="website" tabindex="-1" autocomplete="off"></div>';
}

function honeypotVazio(): bool {
    return trim((string)($_POST['website'] ?? '')) === '';
}

// ---------- URL base (para montar links absolutos em e-mails e telas) ----------
function baseUrl(): string {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'canaldedenuncias.smartwebhosted.com';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $dir = preg_replace('#/admin.*$#', '', $dir);
    return $proto . '://' . $host . $dir;
}

// ---------- Recuperação de senha (tokens) ----------
/** Cria um token de reset para o usuário e retorna o token bruto (vai no link do e-mail). */
function criarTokenReset(PDO $pdo, int $usuarioId): string {
    $tokenBruto = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $tokenBruto);
    // invalida tokens anteriores ainda válidos
    $pdo->prepare('UPDATE password_resets SET usado = 1 WHERE usuario_id = ? AND usado = 0')->execute([$usuarioId]);
    $ins = $pdo->prepare('INSERT INTO password_resets (usuario_id, token_hash, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
    $ins->execute([$usuarioId, $tokenHash]);
    return $tokenBruto;
}

/** Valida um token bruto. Retorna o usuário (id, nome, username) ou null. */
function validarTokenReset(PDO $pdo, string $tokenBruto): ?array {
    if ($tokenBruto === '') return null;
    $tokenHash = hash('sha256', $tokenBruto);
    $stmt = $pdo->prepare(
        'SELECT pr.id AS reset_id, u.id, u.nome, u.username
         FROM password_resets pr
         JOIN admin_usuarios u ON u.id = pr.usuario_id
         WHERE pr.token_hash = ? AND pr.usado = 0 AND pr.expira_em > NOW() AND u.ativo = 1
         LIMIT 1'
    );
    $stmt->execute([$tokenHash]);
    return $stmt->fetch() ?: null;
}

function consumirTokenReset(PDO $pdo, int $resetId): void {
    $pdo->prepare('UPDATE password_resets SET usado = 1 WHERE id = ?')->execute([$resetId]);
}

// ---------- Controle de tentativas de login (anti brute-force) ----------
function ipHashAtual(): string {
    return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|canal-denuncias-login');
}

/** Retorna true se o IP atual está bloqueado por excesso de tentativas. */
function loginBloqueado(PDO $pdo): bool {
    $stmt = $pdo->prepare('SELECT bloqueado_ate FROM login_attempts WHERE ip_hash = ? LIMIT 1');
    $stmt->execute([ipHashAtual()]);
    $row = $stmt->fetch();
    if (!$row || empty($row['bloqueado_ate'])) {
        return false;
    }
    return strtotime($row['bloqueado_ate']) > time();
}

function registrarFalhaLogin(PDO $pdo): void {
    $ip = ipHashAtual();
    $maxTentativas = 5;
    $bloqueioMin = 15;

    $stmt = $pdo->prepare('SELECT tentativas FROM login_attempts WHERE ip_hash = ? LIMIT 1');
    $stmt->execute([$ip]);
    $row = $stmt->fetch();

    if (!$row) {
        $ins = $pdo->prepare('INSERT INTO login_attempts (ip_hash, tentativas) VALUES (?, 1)');
        $ins->execute([$ip]);
        return;
    }

    $tentativas = (int)$row['tentativas'] + 1;
    if ($tentativas >= $maxTentativas) {
        $upd = $pdo->prepare("UPDATE login_attempts SET tentativas = 0, bloqueado_ate = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE ip_hash = ?");
        $upd->execute([$bloqueioMin, $ip]);
    } else {
        $upd = $pdo->prepare('UPDATE login_attempts SET tentativas = ? WHERE ip_hash = ?');
        $upd->execute([$tentativas, $ip]);
    }
}

function limparTentativasLogin(PDO $pdo): void {
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_hash = ?');
    $stmt->execute([ipHashAtual()]);
}

function tentarLogin(string $username, string $senha): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM admin_usuarios WHERE username = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$username]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['password_hash'])) {
        unset($usuario['password_hash']);
        return $usuario;
    }
    return null;
}

/**
 * Retorna a cláusula WHERE (e parâmetros) que restringe a consulta de
 * denúncias à(s) empresa(s) que o usuário logado pode ver.
 * Usuários com empresa_id = NULL veem todas as empresas.
 */
function filtroEmpresaUsuario(array $usuario): array {
    if (($usuario['empresa_id'] ?? null) === null) {
        return ['', []];
    }
    return [' AND d.empresa_id = ?', [$usuario['empresa_id']]];
}
