<?php
/** @var array $empresa */
$opcoes = require __DIR__ . '/includes/opcoes.php';
$corPrimaria = $empresa['cor_primaria'] ?: '#0d3b66';
$corFundo = $empresa['cor_fundo'] ?: '#f4f6f9';
$corTextoDestaque = $empresa['cor_texto_destaque'] ?: null;
$logo = $empresa['logo_path'] ? 'assets/logos/' . $empresa['logo_path'] : null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<title>Canal de Denúncias - <?= htmlspecialchars($empresa['nome']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<style>:root{
    --cor-primaria: <?= htmlspecialchars($corPrimaria) ?>;
    --cor-fundo: <?= htmlspecialchars($corFundo) ?>;
    <?php if ($corTextoDestaque): ?>--cor-texto-destaque: <?= htmlspecialchars($corTextoDestaque) ?>;<?php endif; ?>
}</style>
</head>
<body>
<div class="container">
    <header class="form-header">
        <?php if ($logo): ?>
            <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($empresa['nome']) ?>" class="logo">
        <?php endif; ?>
        <h1>Canal de Denúncias</h1>
        <p class="subtitulo"><?= htmlspecialchars($empresa['nome']) ?></p>
    </header>

    <div class="progress-bar" id="progressBar">
        <div class="progress-step active" data-step="1">
            <span class="step-circle">1</span>
            <span class="step-label">Identificação</span>
        </div>
        <div class="progress-step" data-step="2">
            <span class="step-circle">2</span>
            <span class="step-label">O Incidente</span>
        </div>
        <div class="progress-step" data-step="3">
            <span class="step-circle">3</span>
            <span class="step-label">Contexto</span>
        </div>
        <div class="progress-step" data-step="4">
            <span class="step-circle">4</span>
            <span class="step-label">Dados Voluntários</span>
        </div>
    </div>

    <div id="formMessage" class="form-message" style="display:none;"></div>

    <form id="denunciaForm" action="submit.php" method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="empresa_slug" value="<?= htmlspecialchars($empresa['slug']) ?>">
        <input type="hidden" name="dispositivo_dados" id="dispositivoDados" value="">

        <!-- ETAPA 1 - Identificação -->
        <section class="form-step active" data-step="1">
            <h2>Identificação</h2>

            <div class="campo">
                <label>Você gostaria de se identificar? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option">
                            <input type="radio" name="identificado" value="<?= $op ?>" required> <?= $op ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo condicional" data-show-if="identificado=Sim">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" maxlength="255">
            </div>
        </section>

        <!-- ETAPA 2 - Sobre o incidente -->
        <section class="form-step" data-step="2">
            <h2>Sobre o Incidente</h2>

            <div class="campo">
                <label for="empresa_fato">1. Indique o nome da empresa onde ocorreram os fatos que você está denunciando. <span class="req">*</span></label>
                <input type="text" id="empresa_fato" name="empresa_fato" maxlength="255" required>
            </div>

            <div class="campo">
                <label for="vinculo">2. Qual seu vínculo com a empresa? <span class="req">*</span></label>
                <select id="vinculo" name="vinculo" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($opcoes['vinculo'] as $op): ?>
                        <option value="<?= $op ?>"><?= $op ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="tipo_incidente">3. Como você descreveria o incidente que está relatando? Selecione a opção que melhor se enquadre na sua denúncia. <span class="req">*</span></label>
                <select id="tipo_incidente" name="tipo_incidente" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($opcoes['tipo_incidente'] as $op): ?>
                        <option value="<?= $op ?>"><?= $op ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="filial_departamento">4. Indique a filial (caso se aplique) e departamento onde ocorreram os fatos que estão sendo denunciados. <span class="req">*</span></label>
                <input type="text" id="filial_departamento" name="filial_departamento" maxlength="255" required>
            </div>

            <div class="campo">
                <label for="resumo_fato">5. Resuma o fato denunciado: <span class="req">*</span></label>
                <textarea id="resumo_fato" name="resumo_fato" rows="5" required></textarea>
            </div>

            <div class="campo">
                <label for="como_soube">6. Como tomou conhecimento do fato denunciado? <span class="req">*</span></label>
                <select id="como_soube" name="como_soube" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($opcoes['como_soube'] as $op): ?>
                        <option value="<?= $op ?>"><?= $op ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </section>

        <!-- ETAPA 3 - Contexto investigativo -->
        <section class="form-step" data-step="3">
            <h2>Contexto da Denúncia</h2>

            <div class="campo">
                <label>7. O diretor ou gerente da área onde ocorreu o fato denunciado tem conhecimento do ocorrido? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="gerente_ciente" value="<?= $op ?>" required> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo">
                <label>8. Os gestores da área onde ocorreu o fato denunciado participaram do fato? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="gestores_participaram" value="<?= $op ?>" required> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo">
                <label>9. Houve afastamentos recentes de colaboradores da área onde ocorreu o fato denunciado? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="afastamentos_recentes" value="<?= $op ?>" required> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo condicional" data-show-if="afastamentos_recentes=Sim">
                <label for="descricao_afastamentos">Descreva os afastamentos recentes: <span class="req">*</span></label>
                <textarea id="descricao_afastamentos" name="descricao_afastamentos" rows="3"></textarea>
            </div>

            <div class="campo">
                <label>10. Há alta rotatividade de colaboradores na área onde ocorreu o fato denunciado? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="alta_rotatividade" value="<?= $op ?>" required> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo condicional" data-show-if="alta_rotatividade=Sim">
                <label for="descricao_rotatividade">Descreva a alta rotatividade: <span class="req">*</span></label>
                <textarea id="descricao_rotatividade" name="descricao_rotatividade" rows="3"></textarea>
            </div>

            <div class="campo">
                <label for="testemunhas">11. Existem testemunhas? Em caso positivo, indique-as, por favor. Isso poderá ajudar a conseguirmos resolver o problema relatado e cuidar cada dia mais do nosso meio ambiente laboral. Todos assinarão termos de confidencialidade e não divulgação de informações no tratamento da denúncia.</label>
                <textarea id="testemunhas" name="testemunhas" rows="3"></textarea>
            </div>

            <div class="campo">
                <label for="evidencia">12. Você tem conhecimento da existência de evidências relacionadas ao fato? Se sim, por favor, anexe-as, se possível.</label>
                <input type="file" id="evidencia" name="evidencia" accept=".jpg,.jpeg,.png,.gif">
                <small>Formatos aceitos: JPG, JPEG, PNG, GIF. Tamanho máximo: 5MB.</small>
            </div>

            <div class="campo">
                <label for="sugestoes">13. Você tem alguma sugestão que gostaria de propor como solução para o problema e fatos relatados na denúncia?</label>
                <textarea id="sugestoes" name="sugestoes" rows="3"></textarea>
            </div>

            <div class="campo">
                <label>14. Você já relatou esse fato denunciado a alguém dentro da empresa? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="ja_relatou" value="<?= $op ?>" required> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo">
                <label>15. Você acredita que existam medidas ou políticas que poderiam ter prevenido o fato que você está denunciando? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="medidas_prevencao" value="<?= $op ?>" required> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo">
                <label>16. Você considera que houve falha nos processos internos da Empresa que possa ter contribuído para o fato relatado na presente denúncia? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="falha_processos" value="<?= $op ?>" required> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo">
                <label>17. Você já procurou algum tipo de suporte ou orientação relacionada ao fato que você está denunciando? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['buscou_suporte'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="buscou_suporte" value="<?= $op ?>" required> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo">
                <label>18. Você sente necessidade de apoio psicológico pelo fato relatado? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="apoio_psicologico" value="<?= $op ?>" required> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo">
                <label>19. Você já presenciou ou teve conhecimento de outras situações semelhantes a essa na empresa? <span class="req">*</span></label>
                <div class="radio-group">
                    <?php foreach ($opcoes['presenciou_similar'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="presenciou_similar" value="<?= $op ?>" required> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ETAPA 4 - Dados demográficos voluntários -->
        <section class="form-step" data-step="4">
            <h2>Dados Voluntários</h2>
            <p class="info-box">Estas últimas respostas serão apenas utilizadas para proteger e apoiar vítimas de grupos historicamente discriminados. A prestação destes dados é completamente voluntária e não terá impacto caso opte por não fornecê-los.</p>

            <p class="info-box"><strong>Finalidades do Tratamento:</strong> As informações coletadas serão usadas para fins de suporte, análise e proteção a grupos historicamente discriminados. Preencher de forma voluntária (respostas não obrigatórias) os 3 itens a seguir apenas se você for vítima.</p>

            <div class="campo">
                <label>Você tem 60+ anos?</label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="idade_60mais" value="<?= $op ?>"> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
                <label class="checkbox-option">
                    <input type="checkbox" name="consentimento_idade" value="1">
                    Concordo com a coleta e tratamento desta informação.
                </label>
                <small>A condição de idoso ajuda a priorizar investigações envolvendo vítimas acima de 60 anos.</small>
            </div>

            <div class="campo">
                <label>Gênero:</label>
                <div class="radio-group">
                    <?php foreach ($opcoes['genero'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="genero" value="<?= $op ?>"> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
                <label class="checkbox-option">
                    <input type="checkbox" name="consentimento_genero" value="1">
                    Concordo com a coleta e tratamento desta informação.
                </label>
                <small>O gênero é utilizado para assegurar uma investigação com perspectiva de gênero em benefício da titular.</small>
            </div>

            <div class="campo">
                <label>Possui deficiência?</label>
                <div class="radio-group">
                    <?php foreach ($opcoes['sim_nao'] as $op): ?>
                        <label class="radio-option"><input type="radio" name="deficiencia" value="<?= $op ?>"> <?= $op ?></label>
                    <?php endforeach; ?>
                </div>
                <label class="checkbox-option">
                    <input type="checkbox" name="consentimento_deficiencia" value="1">
                    Concordo com a coleta e tratamento desta informação.
                </label>
                <small>A condição de deficiente também é considerada para dar preferência no suporte a grupos minoritários.</small>
            </div>
        </section>

        <div class="form-nav">
            <?php if (!empty($empresa['url_site'])): ?>
                <a href="<?= htmlspecialchars($empresa['url_site']) ?>" class="btn btn-secundario btn-voltar-site">&larr; Voltar ao Site</a>
            <?php endif; ?>
            <button type="button" id="btnAnterior" class="btn btn-secundario" style="display:none;">Voltar</button>
            <button type="button" id="btnProximo" class="btn btn-primario">Próximo</button>
            <button type="submit" id="btnEnviar" class="btn btn-primario" style="display:none;">Enviar</button>
        </div>
    </form>
</div>

<script src="assets/js/form.js"></script>
</body>
</html>
