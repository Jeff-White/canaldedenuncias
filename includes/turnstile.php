<?php
/**
 * Validação do Cloudflare Turnstile via API siteverify
 */

if (!defined('TURNSTILE_SITE_KEY')) {
    define('TURNSTILE_SITE_KEY', '0x4AAAAAAE6-vm0eG5GaHGzr');
}
if (!defined('TURNSTILE_SECRET_KEY')) {
    define('TURNSTILE_SECRET_KEY', '0x4AAAAAAE6-vqqNWfjbZyhuKNbtp3zpj88');
}

/**
 * Valida o token de resposta do Turnstile com os servidores da Cloudflare.
 */
function validarTurnstile(?string $token, ?string $ip = null): bool {
    $token = trim((string)$token);
    if ($token === '') {
        return false;
    }

    $secret = defined('TURNSTILE_SECRET_KEY') ? TURNSTILE_SECRET_KEY : '';
    if (empty($secret)) {
        return true;
    }

    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $postData = [
        'secret' => $secret,
        'response' => $token,
    ];
    if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
        $postData['remoteip'] = $ip;
    }

    $corpo = http_build_query($postData);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $resposta = curl_exec($ch);
        $erroCurl = curl_error($ch);
        curl_close($ch);
    } else {
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $corpo,
                'timeout' => 10,
            ]
        ];
        $contexto = stream_context_create($opts);
        $resposta = @file_get_contents($url, false, $contexto);
    }

    if (!$resposta) {
        return false;
    }

    $dados = json_decode($resposta, true);
    return is_array($dados) && !empty($dados['success']);
}
