<?php
/**
 * Opções de campos select/radio, compartilhadas entre o formulário (render)
 * e o submit.php (validação server-side). Mantém os textos idênticos
 * ao formulário original do Gravity Forms.
 */

return [
    'vinculo' => [
        'Funcionário', 'Ex-funcionário', 'Fornecedor', 'Parceiro',
        'Prestador de serviço', 'Cliente', 'Outros',
    ],
    'tipo_incidente' => [
        'Assédio sexual', 'Assédio moral', 'Discriminação', 'Agressão física',
        'Não cumprimento de Políticas, Normas e Procedimentos', 'Conflito de interesses',
        'Violação de Leis Trabalhistas', 'Violação de Leis Ambientais',
        'Favorecimento de fornecedores ou clientes', 'Fraude, furto ou roubo de dinheiro',
        'Roubo, furto ou desvio de mercadorias', 'Corrupção', 'Outros',
    ],
    'como_soube' => [
        'Aconteceu comigo', 'Eu observei', 'Ouvi por acaso', 'Um colega me contou',
        'A vítima me contou', 'Encontrei um documento ou arquivo acidentalmente', 'Outros',
    ],
    'sim_nao' => ['Sim', 'Não'],
    'buscou_suporte' => ['Sim', 'Não', 'Não, mas estou considerando buscar suporte'],
    'presenciou_similar' => ['Sim', 'Não', 'Não tenho certeza'],
    'genero' => ['Feminino', 'Masculino', 'Outro'],
];
