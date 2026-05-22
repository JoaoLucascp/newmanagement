<?php

/**
 * Newmanagement — Desinstalação
 *
 * Remove todas as tabelas criadas pelo plugin.
 * Chamado por plugin_newmanagement_uninstall() em hook.php.
 *
 * ⚠️  ATENÇÃO: esta operação é IRREVERSÍVEL.
 *     Todos os dados das tabelas abaixo serão permanentemente excluídos.
 *
 * @internal Não chamar diretamente — use plugin_newmanagement_uninstall().
 */

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

/**
 * Lista canônica de tabelas do plugin, em ordem segura de remoção
 * (filhas antes das pais para evitar erros de FK em ambientes com
 * foreign keys explícitas).
 */
const NEWMANAGEMENT_TABLES = [
    // Tabelas filhas do Chatbot
    'glpi_plugin_newmanagement_chatbot_users',
    'glpi_plugin_newmanagement_chatbot_wa_restrictions',
    'glpi_plugin_newmanagement_chatbot_mass_comm',
    // Chatbot
    'glpi_plugin_newmanagement_chatbots',
    // Tabelas filhas do IPBX
    'glpi_plugin_newmanagement_ipbx_lines',
    'glpi_plugin_newmanagement_ipbx_network',
    'glpi_plugin_newmanagement_ipbx_devices',
    'glpi_plugin_newmanagement_ipbx_extensions',
    // IPBX
    'glpi_plugin_newmanagement_ipbx',
    // Tasks
    'glpi_plugin_newmanagement_tasks',
    // Tabela raiz
    'glpi_plugin_newmanagement_companies',
];

/**
 * Remove todas as tabelas do plugin.
 *
 * @return bool Sempre true (DROP IF EXISTS não levanta erro se a tabela não existe).
 */
function plugin_newmanagement_uninstall_tables(): bool
{
    global $DB;

    foreach (NEWMANAGEMENT_TABLES as $table) {
        $DB->doQuery("DROP TABLE IF EXISTS `{$table}`");
    }

    return true;
}
