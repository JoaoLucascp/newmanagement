<?php

/**
 * Newmanagement - Plugin GLPI
 * Sistema completo de Gestão de Documentação de Empresas
 *
 * @author   João Lucas
 * @license  MIT
 */

define('PLUGIN_NEWMANAGEMENT_VERSION', '1.0.0');

// Versão mínima e máxima do GLPI suportada
define('PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION', '11.0.0');
define('PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION', '11.0.99');

/**
 * Inicialização do plugin - chamado em todas as páginas do GLPI
 */
function plugin_init_newmanagement()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    // Ativa o uso de ações massivas
    $PLUGIN_HOOKS['use_massive_action']['newmanagement'] = 1;

    // Adiciona CSS e JS do plugin
    $PLUGIN_HOOKS['add_css']['newmanagement']        = 'css/newmanagement.css';
    $PLUGIN_HOOKS['add_javascript']['newmanagement'] = 'js/newmanagement.js';

    // Registra as classes do plugin com autoload PSR-4
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\Company::class
    );
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\Ipbx::class
    );
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\IpbxCloud::class
    );
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\Chatbot::class
    );
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\FixedLine::class
    );
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\Task::class
    );

    // Menu lateral do plugin (visível somente quando o plugin está ativo)
    if (\Session::haveRight('plugin_newmanagement_company', READ)) {
        $PLUGIN_HOOKS['menu_toadd']['newmanagement'] = [
            'tools' => 'GlpiPlugin\\Newmanagement\\Company',
        ];
    }

    // Configuração do menu principal do plugin
    $PLUGIN_HOOKS['config_page']['newmanagement'] = 'front/config.php';
}

/**
 * Retorna nome e versão do plugin para o GLPI
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
 * Verifica pré-requisitos do plugin
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
 * Verifica configuração do plugin
 */
function plugin_newmanagement_check_config($verbose = false)
{
    return true;
}
