<?php

/**
 * Newmanagement — Upgrades entre versões do plugin
 *
 * Chamado por plugin_newmanagement_upgrade() em hook.php.
 * Adicione um novo case para cada versão que introduza alterações
 * de esquema ou dados que não sejam cobertas pelo install.php
 * (que já lida com addField/changeField idempotente).
 *
 * Estrutura recomendada para um upgrade futuro:
 *
 *   case '1.0.0':
 *       // Mudanças específicas de 0.x → 1.0.0
 *       // fall through intencional
 *   case '1.1.0':
 *       // Mudanças de 1.0.0 → 1.1.0
 *       break;
 *
 * A lógica de install.php já garante idempotência via addField/changeField,
 * portanto a maioria das migrações simples (adicionar coluna, mudar tipo)
 * não precisa de um case aqui — só mudanças que install.php não cobre
 * (ex.: migração de dados, renomear coluna, reorganizar índices complexos).
 *
 * @internal Não chamar diretamente — use plugin_newmanagement_upgrade().
 */

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

/**
 * Executa upgrades de esquema específicos por versão.
 *
 * @param string $old_version Versão do plugin antes do upgrade.
 * @return bool true em sucesso.
 */
function plugin_newmanagement_run_upgrade(string $old_version): bool
{
    // install.php cobre todos os addField/changeField de forma idempotente.
    // Este switch é reservado para migrações que install.php não consegue
    // resolver sozinho (ex.: migração de dados entre colunas, DROP COLUMN, etc.).
    switch ($old_version) {
        // Exemplo de upgrade futuro:
        // case '1.0.0':
        //     global $DB;
        //     $DB->update('glpi_plugin_newmanagement_companies',
        //         ['contract_status' => 1],
        //         ['contract_status' => null]
        //     );
        //     break;

        default:
            // Nenhum upgrade específico necessário para esta versão.
            break;
    }

    // Sempre re-executa install para garantir colunas/índices atualizados
    return plugin_newmanagement_install_tables();
}
