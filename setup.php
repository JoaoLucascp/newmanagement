<?php

/**
 * Newmanagement - Plugin GLPI
 * Sistema completo de Gestão de Documentação de Empresas
 *
 * @author   João Lucas
 * @license  MIT
 */

define('PLUGIN_NEWMANAGEMENT_VERSION', '1.0.0');

// Padronizado para GLPI 11
define('PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION', '11.0.0');
define('PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION', '11.0.99');

/**
 * Inicialização do plugin - chamado em todas as páginas do GLPI
 */
function plugin_init_newmanagement()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['newmanagement'] = true;
    $PLUGIN_HOOKS['use_massive_action']['newmanagement'] = 1;

    // CSS e JS — no GLPI 11, assets são servidos a partir de public/
    $PLUGIN_HOOKS['add_css']['newmanagement']        = 'public/css/newmanagement.css';
    $PLUGIN_HOOKS['add_javascript']['newmanagement'] = 'public/js/newmanagement.js';

    // Página de configuração
    $PLUGIN_HOOKS['config_page']['newmanagement'] = 'front/config.php';

    // Registra as classes PSR-4
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Company::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Ipbx::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\IpbxCloud::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Chatbot::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\FixedLine::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Task::class);

    // GLPI 11 — registra o menu lateral via MENU_TOADD.
    // A chave 'plugins' faz o menu aparecer na seção Plug-ins do topo.
    // Company::getMenuContent() define os sub-itens (Empresas, IPBX, Chatbots…).
    $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::MENU_TOADD]['newmanagement'] = [
        'plugins' => [\GlpiPlugin\Newmanagement\Company::class],
    ];
}

/**
 * Retorna informações do plugin para o GLPI
 */
function plugin_version_newmanagement()
{
    return [
        'name'         => 'Newmanagement',
        'version'      => PLUGIN_NEWMANAGEMENT_VERSION,
        'author'       => 'João Lucas',
        'license'      => 'MIT',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION,
                'max' => PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION,
            ],
            'php'  => [
                'min' => '8.1',
            ],
        ],
    ];
}

/**
 * Verifica pré-requisitos
 */
function plugin_newmanagement_check_prerequisites()
{
    if (
        version_compare(GLPI_VERSION, PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION, 'lt')
        || version_compare(GLPI_VERSION, PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION, 'gt')
    ) {
        echo 'Este plugin requer GLPI entre '
            . PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION
            . ' e '
            . PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION;
        return false;
    }
    return true;
}

/**
 * Verifica configuração
 */
function plugin_newmanagement_check_config($verbose = false)
{
    return true;
}
