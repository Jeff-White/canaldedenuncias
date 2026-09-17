<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/zeptomail.php';
$opcoes = require __DIR__ . '/includes/opcoes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$slug = preg_replace('/[^a-z0-9_-]/i', '', $_POST['empresa_slug'] ?? '');
$empresa = $slug ? getEmpresaBySlug($slug) : null;

function erroSaida(array $empresa, string $mensagem) {
    http_response_code(422);
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Erro</title>';
    echo '<link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container">';
    echo '<div class="form-message erro">' . htmlspecialchars($mensagem) . '</div>';
    echo '<p><a href="index.php?empresa=' . urlencode($empresa['slug']) . '">Voltar ao formulário</a></p>';
    echo '</div></body></html>';
    exit;
}

if (!$empresa) {
    http_response_code(404);
    die('Empresa não identificada.');
}

// ---------- Validação server-side ----------
function val(string $campo): string {
    return trim($_POST[$campo] ?? '');
}

function emLista(string $valor, array $lista): bool {
    return in_array($valor, $lista, true);
}

$erros = [];

$identificado = val('identificado');
if (!emLista($identificado, $opcoes['sim_nao'])) $erros[] = 'Campo "Você gostaria de se identificar?" inválido.';

$nome = $identificado === 'Sim' ? val('nome') : null;
if ($identificado === 'Sim' && $nome === '') $erros[] = 'Nome é obrigatório quando você opta por se identificar.';

$empresaFato = val('empresa_fato');
if ($empresaFato === '') $erros[] = 'Campo "Nome da empresa" é obrigatório.';

$vinculo = val('vinculo');
if (!emLista($vinculo, $opcoes['vinculo'])) $erros[] = 'Campo "Vínculo com a empresa" inválido.';

$tipoIncidente = val('tipo_incidente');
if (!emLista($tipoIncidente, $opcoes['tipo_incidente'])) $erros[] = 'Campo "Tipo de incidente" inválido.';

$filialDepartamento = val('filial_departamento');
if ($filialDepartamento === '') $erros[] = 'Campo "Filial/Departamento" é obrigatório.';

$resumoFato = val('resumo_fato');
if ($resumoFato === '') $erros[] = 'Campo "Resumo do fato" é obrigatório.';

$comoSoube = val('como_soube');
if (!emLista($comoSoube, $opcoes['como_soube'])) $erros[] = 'Campo "Como tomou conhecimento" inválido.';

$gerenteCiente = val('gerente_ciente');
if (!emLista($gerenteCiente, $opcoes['sim_nao'])) $erros[] = 'Campo "Gerente ciente" inválido.';

$gestoresParticiparam = val('gestores_participaram');
if (!emLista($gestoresParticiparam, $opcoes['sim_nao'])) $erros[] = 'Campo "Gestores participaram" inválido.';

$afastamentosRecentes = val('afastamentos_recentes');
if (!emLista($afastamentosRecentes, $opcoes['sim_nao'])) $erros[] = 'Campo "Afastamentos recentes" inválido.';
$descricaoAfastamentos = $afastamentosRecentes === 'Sim' ? val('descricao_afastamentos') : null;
if ($afastamentosRecentes === 'Sim' && $descricaoAfastamentos === '') $erros[] = 'Descreva os afastamentos recentes.';

$altaRotatividade = val('alta_rotatividade');
if (!emLista($altaRotatividade, $opcoes['sim_nao'])) $erros[] = 'Campo "Alta rotatividade" inválido.';
$descricaoRotatividade = $altaRotatividade === 'Sim' ? val('descricao_rotatividade') : null;
if ($altaRotatividade === 'Sim' && $descricaoRotatividade === '') $erros[] = 'Descreva a alta rotatividade.';

$testemunhas = val('testemunhas') ?: null;
$sugestoes = val('sugestoes') ?: null;

$jaRelatou = val('ja_relatou');
if (!emLista($jaRelatou, $opcoes['sim_nao'])) $erros[] = 'Campo "Já relatou" inválido.';

$medidasPrevencao = val('medidas_prevencao');
if (!emLista($medidasPrevencao, $opcoes['sim_nao'])) $erros[] = 'Campo "Medidas de prevenção" inválido.';

$falhaProcessos = val('falha_processos');
if (!emLista($falhaProcessos, $opcoes['sim_nao'])) $erros[] = 'Campo "Falha em processos" inválido.';

$buscouSuporte = val('buscou_suporte');
if (!emLista($buscouSuporte, $opcoes['buscou_suporte'])) $erros[] = 'Campo "Buscou suporte" inválido.';

$apoioPsicologico = val('apoio_psicologico');
if (!emLista($apoioPsicologico, $opcoes['sim_nao'])) $erros[] = 'Campo "Apoio psicológico" inválido.';

$presenciouSimilar = val('presenciou_similar');
if (!emLista($presenciouSimilar, $opcoes['presenciou_similar'])) $erros[] = 'Campo "Presenciou situação semelhante" inválido.';

// Etapa 4 - opcionais
$idade60 = val('idade_60mais');
$idade60 = emLista($idade60, $opcoes['sim_nao']) ? $idade60 : null;
$consentimentoIdade = isset($_POST['consentimento_idade']) ? 1 : 0;

$genero = val('genero');
$genero = emLista($genero, $opcoes['genero']) ? $genero : null;
$consentimentoGenero = isset($_POST['consentimento_genero']) ? 1 : 0;

$deficiencia = val('deficiencia');
$deficiencia = emLista($deficiencia, $opcoes['sim_nao']) ? $deficiencia : null;
$consentimentoDeficiencia = isset($_POST['consentimento_deficiencia']) ? 1 : 0;

if (!empty($erros)) {
    erroSaida($empresa, implode(' ', $erros));
}

// ---------- Upload de evidência ----------
$evidenciaPath = null;
if (!empty($_FILES['evidencia']['name']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
    $extensao = strtolower(pathinfo($_FILES['evidencia']['name'], PATHINFO_EXTENSION));
    $tamanhoMax = 5 * 1024 * 1024; // 5MB

    if (!in_array($extensao, $extensoesPermitidas, true)) {
        erroSaida($empresa, 'Formato de arquivo não permitido. Envie apenas JPG, JPEG, PNG ou GIF.');
    }
    if ($_FILES['evidencia']['size'] > $tamanhoMax) {
        erroSaida($empresa, 'O arquivo de evidência excede o tamanho máximo de 5MB.');
    }

    // Valida que o arquivo é realmente uma imagem (e não um script renomeado)
    $tiposImagem = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
    $infoImagem = @getimagesize($_FILES['evidencia']['tmp_name']);
    if ($infoImagem === false || !in_array($infoImagem[2], $tiposImagem, true)) {
        erroSaida($empresa, 'O arquivo enviado não é uma imagem válida.');
    }

    $destinoDir = rtrim(UPLOAD_DIR, '/') . '/' . $empresa['slug'] . '/';
    if (!is_dir($destinoDir)) {
        mkdir($destinoDir, 0750, true);
    }

    $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
    $destino = $destinoDir . $nomeArquivo;

    if (!move_uploaded_file($_FILES['evidencia']['tmp_name'], $destino)) {
        erroSaida($empresa, 'Falha ao salvar o arquivo de evidência. Tente novamente.');
    }

    $evidenciaPath = $empresa['slug'] . '/' . $nomeArquivo;
}

// ---------- Captura e identificação de IP e Máquina ----------
function capturarIpCliente(): string {
    $candidatos = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    foreach ($candidatos as $c) {
        if (!empty($_SERVER[$c])) {
            $lista = explode(',', $_SERVER[$c]);
            foreach ($lista as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function identificarDispositivo(string $ua): array {
    $os = 'Desconhecido';
    $navegador = 'Desconhecido';
    $tipo = 'Desktop / Computador';

    if (preg_match('/windows nt 10\.0/i', $ua)) {
        $os = 'Windows 10 / 11';
    } elseif (preg_match('/windows nt 6\.3/i', $ua)) {
        $os = 'Windows 8.1';
    } elseif (preg_match('/windows nt 6\.1/i', $ua)) {
        $os = 'Windows 7';
    } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
        $os = 'macOS (Apple)';
    } elseif (preg_match('/android/i', $ua)) {
        $os = 'Android';
        $tipo = 'Celular / Smartphone';
    } elseif (preg_match('/iphone|ipad|ipod/i', $ua)) {
        $os = 'iOS (iPhone / iPad)';
        $tipo = preg_match('/ipad/i', $ua) ? 'Tablet' : 'Celular / Smartphone';
    } elseif (preg_match('/linux/i', $ua)) {
        $os = 'Linux';
    }

    if (preg_match('/edg\/([0-9\.]+)/i', $ua, $m)) {
        $navegador = 'Microsoft Edge ' . $m[1];
    } elseif (preg_match('/chrome\/([0-9\.]+)/i', $ua, $m)) {
        $navegador = 'Google Chrome ' . $m[1];
    } elseif (preg_match('/firefox\/([0-9\.]+)/i', $ua, $m)) {
        $navegador = 'Mozilla Firefox ' . $m[1];
    } elseif (preg_match('/safari\/([0-9\.]+)/i', $ua, $m) && !preg_match('/chrome/i', $ua)) {
        $navegador = 'Apple Safari ' . $m[1];
    } elseif (preg_match('/opr\/([0-9\.]+)/i', $ua, $m)) {
        $navegador = 'Opera ' . $m[1];
    }

    return ['os' => $os, 'navegador' => $navegador, 'tipo' => $tipo];
}

$ipReal = capturarIpCliente();
$userAgent = trim($_SERVER['HTTP_USER_AGENT'] ?? '');
$parsedDisp = identificarDispositivo($userAgent);
$reverseHost = $ipReal ? @gethostbyaddr($ipReal) : '';

$rawDisp = trim($_POST['dispositivo_dados'] ?? '');
$dispFront = json_decode($rawDisp, true) ?: [];

$infoDispositivo = [
    'ip' => $ipReal,
    'hostname_reverso' => ($reverseHost && $reverseHost !== $ipReal) ? $reverseHost : null,
    'sistema_operacional' => $parsedDisp['os'],
    'navegador' => $parsedDisp['navegador'],
    'tipo_dispositivo' => $parsedDisp['tipo'],
    'resolucao' => $dispFront['resolucao'] ?? null,
    'janela' => $dispFront['janela'] ?? null,
    'fuso_horario' => $dispFront['fuso_horario'] ?? null,
    'idioma' => $dispFront['idioma'] ?? ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null),
    'plataforma' => $dispFront['plataforma'] ?? null,
    'cores_cpu' => $dispFront['cores_cpu'] ?? null,
    'memoria_ram' => $dispFront['memoria_ram'] ?? null,
    'tela_touch' => $dispFront['touch'] ?? null,
    'user_agent_completo' => $userAgent,
    'data_envio' => date('d/m/Y H:i:s'),
];
$dispositivoInfoJson = json_encode($infoDispositivo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// ---------- Gravação no banco ----------
$pdo = getDB();
$protocolo = gerarProtocolo($pdo);

// hash de IP apenas para controle anti-spam
$ipHash = hash('sha256', ($ipReal ?: ($_SERVER['REMOTE_ADDR'] ?? '')) . date('Y-m-d'));

$sql = "INSERT INTO denuncias (
    protocolo, empresa_id, identificado, nome,
    empresa_fato, vinculo, tipo_incidente, filial_departamento, resumo_fato, como_soube,
    gerente_ciente, gestores_participaram, afastamentos_recentes, descricao_afastamentos,
    alta_rotatividade, descricao_rotatividade, testemunhas, evidencia_path, sugestoes,
    ja_relatou, medidas_prevencao, falha_processos, buscou_suporte, apoio_psicologico, presenciou_similar,
    idade_60mais, consentimento_idade, genero, consentimento_genero, deficiencia, consentimento_deficiencia,
    ip_hash, ip_origem, user_agent, dispositivo_info
) VALUES (
    :protocolo, :empresa_id, :identificado, :nome,
    :empresa_fato, :vinculo, :tipo_incidente, :filial_departamento, :resumo_fato, :como_soube,
    :gerente_ciente, :gestores_participaram, :afastamentos_recentes, :descricao_afastamentos,
    :alta_rotatividade, :descricao_rotatividade, :testemunhas, :evidencia_path, :sugestoes,
    :ja_relatou, :medidas_prevencao, :falha_processos, :buscou_suporte, :apoio_psicologico, :presenciou_similar,
    :idade_60mais, :consentimento_idade, :genero, :consentimento_genero, :deficiencia, :consentimento_deficiencia,
    :ip_hash, :ip_origem, :user_agent, :dispositivo_info
)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'protocolo' => $protocolo,
    'empresa_id' => $empresa['id'],
    'identificado' => $identificado,
    'nome' => $nome,
    'empresa_fato' => $empresaFato,
    'vinculo' => $vinculo,
    'tipo_incidente' => $tipoIncidente,
    'filial_departamento' => $filialDepartamento,
    'resumo_fato' => $resumoFato,
    'como_soube' => $comoSoube,
    'gerente_ciente' => $gerenteCiente,
    'gestores_participaram' => $gestoresParticiparam,
    'afastamentos_recentes' => $afastamentosRecentes,
    'descricao_afastamentos' => $descricaoAfastamentos,
    'alta_rotatividade' => $altaRotatividade,
    'descricao_rotatividade' => $descricaoRotatividade,
    'testemunhas' => $testemunhas,
    'evidencia_path' => $evidenciaPath,
    'sugestoes' => $sugestoes,
    'ja_relatou' => $jaRelatou,
    'medidas_prevencao' => $medidasPrevencao,
    'falha_processos' => $falhaProcessos,
    'buscou_suporte' => $buscouSuporte,
    'apoio_psicologico' => $apoioPsicologico,
    'presenciou_similar' => $presenciouSimilar,
    'idade_60mais' => $idade60,
    'consentimento_idade' => $consentimentoIdade,
    'genero' => $genero,
    'consentimento_genero' => $consentimentoGenero,
    'deficiencia' => $deficiencia,
    'consentimento_deficiencia' => $consentimentoDeficiencia,
    'ip_hash' => $ipHash,
    'ip_origem' => $ipReal ?: null,
    'user_agent' => $userAgent ?: null,
    'dispositivo_info' => $dispositivoInfoJson,
]);

// ---------- Notificação por e-mail ----------
$dadosEmail = [
    'identificado' => $identificado,
    'nome' => $nome,
    'empresa_fato' => $empresaFato,
    'vinculo' => $vinculo,
    'tipo_incidente' => $tipoIncidente,
    'filial_departamento' => $filialDepartamento,
    'resumo_fato' => $resumoFato,
    'como_soube' => $comoSoube,
    'gerente_ciente' => $gerenteCiente,
    'gestores_participaram' => $gestoresParticiparam,
    'afastamentos_recentes' => $afastamentosRecentes,
    'descricao_afastamentos' => $descricaoAfastamentos,
    'alta_rotatividade' => $altaRotatividade,
    'descricao_rotatividade' => $descricaoRotatividade,
    'testemunhas' => $testemunhas,
    'evidencia_path' => $evidenciaPath,
    'sugestoes' => $sugestoes,
    'ja_relatou' => $jaRelatou,
    'medidas_prevencao' => $medidasPrevencao,
    'falha_processos' => $falhaProcessos,
    'buscou_suporte' => $buscouSuporte,
    'apoio_psicologico' => $apoioPsicologico,
    'presenciou_similar' => $presenciouSimilar,
    'dispositivo' => $infoDispositivo,
];

$corpoEmail = montarEmailNotificacao($dadosEmail, $empresa['nome'], $protocolo);
enviarEmailZepto($empresa['email_destino'], 'Nova denúncia recebida - ' . $empresa['nome'] . ' [' . $protocolo . ']', $corpoEmail);

// ---------- Página de confirmação ----------
header('Location: obrigado.php?protocolo=' . urlencode($protocolo) . '&empresa=' . urlencode($empresa['slug']));
exit;
