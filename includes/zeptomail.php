<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Envia e-mail via API do Zeptomail.
 *
 * @param string|array $to       e-mail único ou array de e-mails destinatários
 * @param string $subject
 * @param string $htmlBody
 * @return bool true se enviado com sucesso (HTTP 2xx)
 */
function enviarEmailZepto($to, string $subject, string $htmlBody): bool {
    if (is_string($to)) {
        $to = preg_split('/[\s,;]+/', trim($to), -1, PREG_SPLIT_NO_EMPTY);
    } elseif (!is_array($to)) {
        $to = [];
    }

    $validEmails = [];
    foreach ($to as $email) {
        $email = trim((string)$email);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $validEmails, true)) {
            $validEmails[] = $email;
        }
    }

    if (empty($validEmails)) {
        error_log('Zeptomail error: nenhum destinatário válido fornecido.');
        return false;
    }

    $toList = array_map(function ($email) {
        return [
            'email_address' => [
                'address' => $email,
            ],
        ];
    }, $validEmails);

    $payload = [
        'from' => [
            'address' => ZEPTOMAIL_FROM_EMAIL,
            'name' => ZEPTOMAIL_FROM_NAME,
        ],
        'to' => $toList,
        'subject' => $subject,
        'htmlbody' => $htmlBody,
    ];

    $ch = curl_init(ZEPTOMAIL_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: ' . ZEPTOMAIL_TOKEN,
        ],
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log('Zeptomail cURL error: ' . $error);
        return false;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('Zeptomail API error (' . $httpCode . '): ' . $response);
        return false;
    }

    return true;
}

/**
 * Monta o corpo HTML do e-mail de notificação interna (para a empresa).
 */
function montarEmailNotificacao(array $dados, string $empresaNome, string $protocolo): string {
    $esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

    $linhas = [
        'Protocolo' => $esc($protocolo),
        'Empresa' => $esc($empresaNome),
        'Identificado' => $esc($dados['identificado']),
        'Nome' => $esc($dados['nome'] ?? '(não identificado)'),
        'Empresa do fato' => $esc($dados['empresa_fato']),
        'Vínculo' => $esc($dados['vinculo']),
        'Tipo de incidente' => $esc($dados['tipo_incidente']),
        'Filial/Departamento' => $esc($dados['filial_departamento']),
        'Resumo' => nl2br($esc($dados['resumo_fato'])),
        'Como tomou conhecimento' => $esc($dados['como_soube']),
        'Gerente ciente' => $esc($dados['gerente_ciente']),
        'Gestores participaram' => $esc($dados['gestores_participaram']),
        'Afastamentos recentes' => $esc($dados['afastamentos_recentes']) . (!empty($dados['descricao_afastamentos']) ? ' - ' . $esc($dados['descricao_afastamentos']) : ''),
        'Alta rotatividade' => $esc($dados['alta_rotatividade']) . (!empty($dados['descricao_rotatividade']) ? ' - ' . $esc($dados['descricao_rotatividade']) : ''),
        'Testemunhas' => nl2br($esc($dados['testemunhas'] ?? '')),
        'Sugestões' => nl2br($esc($dados['sugestoes'] ?? '')),
        'Já relatou' => $esc($dados['ja_relatou']),
        'Medidas de prevenção' => $esc($dados['medidas_prevencao']),
        'Falha em processos' => $esc($dados['falha_processos']),
        'Buscou suporte' => $esc($dados['buscou_suporte']),
        'Apoio psicológico' => $esc($dados['apoio_psicologico']),
        'Presenciou situação semelhante' => $esc($dados['presenciou_similar']),
    ];

    $rows = '';
    foreach ($linhas as $label => $valor) {
        $rows .= "<tr><td style='padding:6px 10px;font-weight:bold;border:1px solid #ddd;background:#f5f5f5;width:260px;vertical-align:top;'>{$label}</td><td style='padding:6px 10px;border:1px solid #ddd;'>{$valor}</td></tr>";
    }

    $evidencia = !empty($dados['evidencia_path']) ? '<p>Evidência anexada pelo denunciante (ver dashboard).</p>' : '';

    // Dados de auditoria da máquina / conexão
    $tabelaDispositivo = '';
    if (!empty($dados['dispositivo']) && is_array($dados['dispositivo'])) {
        $disp = $dados['dispositivo'];
        $camposTecnicos = [
            'IP de Origem' => $disp['ip'] ?? null,
            'Hostname / Provedor' => $disp['hostname_reverso'] ?? null,
            'Sistema Operacional' => $disp['sistema_operacional'] ?? null,
            'Navegador' => $disp['navegador'] ?? null,
            'Tipo de Dispositivo' => $disp['tipo_dispositivo'] ?? null,
            'Resolução de Tela' => $disp['resolucao'] ?? null,
            'Fuso Horário' => $disp['fuso_horario'] ?? null,
            'Idioma' => $disp['idioma'] ?? null,
            'Processador (Cores)' => $disp['cores_cpu'] ?? null,
            'Memória Estimada' => $disp['memoria_ram'] ?? null,
            'Data/Hora de Envio' => $disp['data_envio'] ?? null,
            'User-Agent Completo' => $disp['user_agent_completo'] ?? null,
        ];
        $linhasDispRows = '';
        foreach ($camposTecnicos as $rotulo => $valTec) {
            if ($valTec !== null && $valTec !== '') {
                $linhasDispRows .= "<tr><td style='padding:5px 9px;font-weight:bold;border:1px solid #e2e8f0;background:#f8fafc;width:240px;vertical-align:top;font-size:12px;color:#475569;'>{$esc($rotulo)}</td><td style='padding:5px 9px;border:1px solid #e2e8f0;font-size:12px;font-family:monospace;word-break:break-all;'>{$esc($valTec)}</td></tr>";
            }
        }
        if ($linhasDispRows !== '') {
            $tabelaDispositivo = "<h3 style='margin-top:24px;margin-bottom:8px;font-size:13px;color:#0d3b66;text-transform:uppercase;letter-spacing:0.5px;'>Dados Técnicos da Máquina e Conexão</h3><table style='border-collapse:collapse;width:100%;max-width:700px;'>{$linhasDispRows}</table>";
        }
    }

    return "
        <div style='font-family:Arial,sans-serif;font-size:14px;color:#333;'>
            <h2>Nova denúncia recebida - {$empresaNome}</h2>
            <p>Protocolo: <strong>{$protocolo}</strong></p>
            <table style='border-collapse:collapse;width:100%;max-width:700px;'>{$rows}</table>
            {$evidencia}
            {$tabelaDispositivo}
            <p style='margin-top:20px;color:#777;font-size:12px;'>Este e-mail foi gerado automaticamente pelo Canal de Denúncias.</p>
        </div>
    ";
}
