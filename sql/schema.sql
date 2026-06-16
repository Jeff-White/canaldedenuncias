-- Banco de dados: Canal de Denúncias (multi-empresa)
-- Importar via phpMyAdmin no painel da Dreamhost

CREATE TABLE IF NOT EXISTS empresas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(50) NOT NULL UNIQUE,      -- identificador interno (gravado em denuncias)
  parametro VARCHAR(20) NOT NULL UNIQUE, -- usado na URL: ?par=GHB3SD
  nome VARCHAR(100) NOT NULL,
  logo_path VARCHAR(255) DEFAULT NULL,   -- caminho relativo dentro de assets/logos/
  email_destino VARCHAR(255) NOT NULL,   -- e-mail(s) que recebem a notificação, separados por vírgula
  cor_primaria VARCHAR(7) DEFAULT '#0d3b66', -- cor de destaque (hex) para personalizar o form
  cor_fundo VARCHAR(7) DEFAULT '#f4f6f9',    -- cor de fundo da página (hex)
  cor_texto_destaque VARCHAR(7) DEFAULT NULL, -- cor dos rótulos/labels (hex), opcional
  url_site VARCHAR(255) DEFAULT NULL,    -- link "voltar ao site" exibido no formulário
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO empresas (slug, parametro, nome, logo_path, email_destino, cor_primaria, cor_fundo, cor_texto_destaque) VALUES
('alibras',   'GHB3SD', 'Alibras',    'alibras.svg',   'jeff@smartweb.com.br', '#737F67', '#FFF8DD', NULL),
('valobras',  'UWCDLR', 'Valobras',   'valobras.png',  'jeff@smartweb.com.br', '#2F6B3A', '#F5EFE0', NULL),
('agatha',    'LEDXI1', 'Agatha Motel','agatha.png',   'jeff@smartweb.com.br', '#5D4037', '#F5EBDD', '#5D4037'),
('aliservice','XAZKL2', 'AliService', 'aliservice.png','jeff@smartweb.com.br', '#0d3b66', '#f4f6f9', NULL),
('alicafe',   'VU9GD0', 'Alicafé',    'alicafe.png',   'jeff@smartweb.com.br', '#0d3b66', '#f4f6f9', NULL);

-- Tabela principal de denúncias
CREATE TABLE IF NOT EXISTS denuncias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  protocolo VARCHAR(20) NOT NULL UNIQUE,   -- código gerado para o denunciante acompanhar (ex: DEN-2026-0001)
  empresa_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  -- Etapa 1 - Identificação
  identificado ENUM('Sim','Não') NOT NULL,
  nome VARCHAR(255) DEFAULT NULL,

  -- Etapa 2 - Sobre o incidente
  empresa_fato VARCHAR(255) NOT NULL,
  vinculo VARCHAR(100) NOT NULL,
  tipo_incidente VARCHAR(150) NOT NULL,
  filial_departamento VARCHAR(255) NOT NULL,
  resumo_fato TEXT NOT NULL,
  como_soube VARCHAR(150) NOT NULL,

  -- Etapa 3 - Contexto investigativo
  gerente_ciente ENUM('Sim','Não') NOT NULL,
  gestores_participaram ENUM('Sim','Não') NOT NULL,
  afastamentos_recentes ENUM('Sim','Não') NOT NULL,
  descricao_afastamentos TEXT DEFAULT NULL,
  alta_rotatividade ENUM('Sim','Não') NOT NULL,
  descricao_rotatividade TEXT DEFAULT NULL,
  testemunhas TEXT DEFAULT NULL,
  evidencia_path VARCHAR(255) DEFAULT NULL,
  sugestoes TEXT DEFAULT NULL,
  ja_relatou ENUM('Sim','Não') NOT NULL,
  medidas_prevencao ENUM('Sim','Não') NOT NULL,
  falha_processos ENUM('Sim','Não') NOT NULL,
  buscou_suporte VARCHAR(60) NOT NULL,
  apoio_psicologico ENUM('Sim','Não') NOT NULL,
  presenciou_similar VARCHAR(30) NOT NULL,

  -- Etapa 4 - Dados demográficos voluntários
  idade_60mais ENUM('Sim','Não') DEFAULT NULL,
  consentimento_idade TINYINT(1) DEFAULT 0,
  genero VARCHAR(30) DEFAULT NULL,
  consentimento_genero TINYINT(1) DEFAULT 0,
  deficiencia ENUM('Sim','Não') DEFAULT NULL,
  consentimento_deficiencia TINYINT(1) DEFAULT 0,

  -- Metadados
  ip_hash VARCHAR(64) DEFAULT NULL,  -- hash do IP apenas para anti-spam, não identifica o autor
  status ENUM('Novo','Em análise','Concluído') NOT NULL DEFAULT 'Novo',

  FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

-- Usuários do painel administrativo
CREATE TABLE IF NOT EXISTS admin_usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nivel ENUM('admin','editor') NOT NULL DEFAULT 'editor', -- admin gerencia usuários; ambos veem tudo
  empresa_id INT DEFAULT NULL, -- NULL = acesso a todas as empresas
  nome VARCHAR(100) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

-- Controle de tentativas de login (anti brute-force)
CREATE TABLE IF NOT EXISTS login_attempts (
  ip_hash VARCHAR(64) NOT NULL PRIMARY KEY,
  tentativas INT NOT NULL DEFAULT 0,
  bloqueado_ate DATETIME DEFAULT NULL,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tokens de redefinição de senha (link enviado por e-mail; armazena só o hash)
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token_hash VARCHAR(64) NOT NULL,
  expira_em DATETIME NOT NULL,
  usado TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token_hash),
  FOREIGN KEY (usuario_id) REFERENCES admin_usuarios(id)
);

-- Usuário admin padrão (senha: troque imediatamente!)
-- senha inicial: "MudeEstaSenha123" -- gerar hash novo antes de usar em produção
-- INSERT INTO admin_usuarios (username, password_hash, empresa_id, nome) VALUES
-- ('admin', '$2y$10$REPLACE_WITH_REAL_HASH', NULL, 'Administrador Geral');
