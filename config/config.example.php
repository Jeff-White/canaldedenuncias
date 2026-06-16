<?php
/**
 * Copie este arquivo para "config.php" (fora do controle de versão)
 * e preencha com os dados reais do ambiente Dreamhost / Zeptomail.
 *
 * config.php é incluído por includes/db.php e includes/zeptomail.php
 */

// ----- Banco de dados MySQL (Dreamhost) -----
define('DB_HOST', 'mysql.suaconta.dreamhost.com');
define('DB_NAME', 'nome_do_banco');
define('DB_USER', 'usuario_mysql');
define('DB_PASS', 'senha_mysql');

// ----- Zeptomail (API de envio de e-mail) -----
// Painel Zeptomail > Mail Agents > seu agente > SMTP/API > "Send Mail Token"
define('ZEPTOMAIL_API_URL', 'https://api.zeptomail.com/v1.1/email');
define('ZEPTOMAIL_TOKEN', 'Zoho-enczapikey SEU_TOKEN_AQUI');
define('ZEPTOMAIL_FROM_EMAIL', 'naoresponda@seudominio.com.br');
define('ZEPTOMAIL_FROM_NAME', 'Canal de Denúncias');

// ----- Diretório de uploads de evidências -----
// Deve ficar FORA do webroot público se possível. Se precisar ficar dentro,
// proteja com .htaccess (já incluso em /uploads/.htaccess)
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// ----- Sessão / Dashboard -----
define('SESSION_NAME', 'canal_denuncias_admin');

// ----- Timezone -----
date_default_timezone_set('America/Sao_Paulo');
