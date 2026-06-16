# Canal de Denúncias - Multi-empresa

Réplica do formulário "Canal de Denúncias" (Gravity Forms) em PHP + MySQL puro,
preparado para hospedagem na Dreamhost, com:

- Wizard de 4 etapas com barra de progresso e lógica condicional (igual ao form original)
- Suporte a 5 empresas (Alibras, Valobras, Agatha Motel, AliService, Alicafé) via parâmetro `?empresa=slug`
- Gravação das denúncias em MySQL, com protocolo único
- Upload de evidências (imagens) protegido contra acesso direto
- Dashboard administrativo com login, filtros, paginação e atualização de status
- Notificação por e-mail via API do Zeptomail

## Estrutura de pastas

```
canal-denuncias/
├── index.php              # entrada do form: ?empresa=alibras
├── form.php                # renderiza o formulário (4 etapas)
├── submit.php               # processa o envio, valida, grava no banco, envia e-mail
├── obrigado.php             # página de confirmação com protocolo
├── config/
│   ├── config.example.php   # copiar para config.php e preencher
│   └── .htaccess            # bloqueia acesso direto a config.php
├── includes/
│   ├── db.php                # conexão PDO + helpers
│   ├── opcoes.php             # opções de selects/radios (compartilhado com submit.php)
│   └── zeptomail.php          # envio de e-mail via API Zeptomail
├── assets/
│   ├── css/style.css
│   ├── js/form.js             # navegação do wizard + lógica condicional
│   └── logos/                 # colocar aqui: alibras.png, valobras.png, agatha.png, aliservice.png, alicafe.png
├── uploads/
│   └── .htaccess             # bloqueia acesso direto aos arquivos de evidência
├── dashboard/
│   ├── login.php / logout.php
│   ├── index.php              # lista de denúncias com filtros
│   ├── view.php                # detalhe + atualização de status
│   ├── evidencia.php           # serve o arquivo de evidência (autenticado)
│   └── includes/auth.php
├── sql/schema.sql             # script para criar as tabelas no MySQL
└── gerar_hash.php             # gera hash de senha para criar usuários do dashboard
```

## Passo a passo de instalação na Dreamhost

1. **Criar o banco MySQL** no painel da Dreamhost (Panel > MySQL Databases).
   Anote: host, nome do banco, usuário e senha.

2. **Importar o schema**: phpMyAdmin > Importar > `sql/schema.sql`.
   Isso cria as tabelas `empresas`, `denuncias` e `admin_usuarios`, já populando
   a tabela `empresas` com as 5 empresas (ajuste cores/e-mails se quiser).

3. **Criar o(s) usuário(s) do dashboard**:
   ```
   php gerar_hash.php "SuaSenhaForte123"
   ```
   Copie o hash retornado e rode no phpMyAdmin:
   ```sql
   INSERT INTO admin_usuarios (username, password_hash, empresa_id, nome)
   VALUES ('admin', '<hash gerado>', NULL, 'Administrador Geral');
   ```
   `empresa_id = NULL` dá acesso a todas as empresas. Para um usuário ver
   só uma empresa, informe o `id` dela (consulte `SELECT id, slug FROM empresas`).

4. **Configurar `config/config.php`**:
   ```
   cp config/config.example.php config/config.php
   ```
   Preencha `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` e os dados do Zeptomail
   (`ZEPTOMAIL_TOKEN`, `ZEPTOMAIL_FROM_EMAIL`, etc.). O token fica em
   Zeptomail > Mail Agents > seu agente > "Send Mail Token".

5. **Subir os arquivos** para a pasta correspondente no servidor (ex: subdomínio
   `denuncias.alibras.com.br` ou pasta `/canal-denuncias/` dentro do domínio).
   Garanta que `uploads/` tenha permissão de escrita (755 ou 750).

6. **Logos das empresas**: colocar os arquivos em `assets/logos/` com os nomes
   já configurados na tabela `empresas` (`alibras.png`, `valobras.png`, etc.).

7. **Testar o link de cada empresa**:
   ```
   https://seudominio.com/canal-denuncias/?empresa=alibras
   https://seudominio.com/canal-denuncias/?empresa=valobras
   https://seudominio.com/canal-denuncias/?empresa=agatha
   https://seudominio.com/canal-denuncias/?empresa=aliservice
   https://seudominio.com/canal-denuncias/?empresa=alicafe
   ```

8. **No WordPress**, configurar o link/botão "Canal de Denúncias" de cada site
   para abrir em nova janela apontando para a URL acima com o `?empresa=` correspondente.

9. Acessar `dashboard/login.php` para consultar as denúncias recebidas.

## Segurança / LGPD

- Quando o denunciante escolhe "Não" em "Gostaria de se identificar?", o campo
  `nome` fica `NULL` — nenhum dado de identificação é solicitado nesse fluxo.
- Apenas um hash diário do IP é armazenado (`ip_hash`), só para fins de
  controle anti-spam; não permite identificar o autor da denúncia.
- Os dados voluntários (idade 60+, gênero, deficiência) só são gravados se o
  usuário marcar o respectivo checkbox de consentimento.
- Arquivos de evidência ficam em `uploads/` protegidos por `.htaccess` e só
  são acessíveis autenticado, via `dashboard/evidencia.php`.
- Apague `gerar_hash.php` do servidor de produção depois de criar os usuários.

## Pendências / próximos passos

- Definir o domínio/subpasta onde o sistema ficará hospedado (conta separada).
- Confirmar nomes de arquivo das logos das 5 empresas.
- Confirmar e-mail(s) de destino das notificações por empresa (`empresas.email_destino`).
- Obter token da API Zeptomail.
- Criar usuário(s) do dashboard.
