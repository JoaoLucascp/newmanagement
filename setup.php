<?php

/**
 * Newmanagement - Plugin GLPI
 * Arquivo de configuração principal
 */

define('PLUGIN_NEWMANAGEMENT_VERSION', '1.0.0');
define('PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION', '10.0.0');
define('PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION', '11.9.9');

function plugin_init_newmanagement() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['newmanagement'] = true;
    $PLUGIN_HOOKS['use_massive_action']['newmanagement'] = true;

    // Registra os assets (CSS e JS)
    $PLUGIN_HOOKS['add_css']['newmanagement'][] = 'public/css/newmanagement.css';
    $PLUGIN_HOOKS['add_javascript']['newmanagement'][] = 'public/js/newmanagement.js';

    // Registra os tipos de itens para autoload PSR-4
    Plugin::registerClass('GlpiPlugin\\Newmanagement\\Company',       ['classname' => 'GlpiPlugin\\Newmanagement\\Company']);
    Plugin::registerClass('GlpiPlugin\\Newmanagement\\Ipbx',          ['classname' => 'GlpiPlugin\\Newmanagement\\Ipbx']);
    Plugin::registerClass('GlpiPlugin\\Newmanagement\\IpbxExtension', ['classname' => 'GlpiPlugin\\Newmanagement\\IpbxExtension']);
    Plugin::registerClass('GlpiPlugin\\Newmanagement\\IpbxDevice',    ['classname' => 'GlpiPlugin\\Newmanagement\\IpbxDevice']);
    Plugin::registerClass('GlpiPlugin\\Newmanagement\\IpbxNetwork',   ['classname' => 'GlpiPlugin\\Newmanagement\\IpbxNetwork']);
    Plugin::registerClass('GlpiPlugin\\Newmanagement\\IpbxLine',      ['classname' => 'GlpiPlugin\\Newmanagement\\IpbxLine']);
    Plugin::registerClass('GlpiPlugin\\Newmanagement\\IpbxCloud',     ['classname' => 'GlpiPlugin\\Newmanagement\\IpbxCloud']);
    Plugin::registerClass('GlpiPlugin\\Newmanagement\\Chatbot',       ['classname' => 'GlpiPlugin\\Newmanagement\\Chatbot']);
    Plugin::registerClass('GlpiPlugin\\Newmanagement\\Task',          ['classname' => 'GlpiPlugin\\Newmanagement\\Task']);

    // Adiciona itens ao menu do plugin
    $PLUGIN_HOOKS['menu_toadd']['newmanagement'] = [
        'plugins' => [
            'GlpiPlugin\\Newmanagement\\Company',
            'GlpiPlugin\\Newmanagement\\Ipbx',
            'GlpiPlugin\\Newmanagement\\IpbxCloud',
            'GlpiPlugin\\Newmanagement\\Chatbot',
            'GlpiPlugin\\Newmanagement\\Task',
        ],
    ];
}

function plugin_version_newmanagement() {
    return [
        'name'         => 'Newmanagement',
        'version'      => PLUGIN_NEWMANAGEMENT_VERSION,
        'author'       => 'Newbase',
        'license'      => 'GPL-2.0-or-later',
        'homepage'     => 'https://github.com/JoaoLucascp/newmanagement',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION,
                'max' => PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION,
            ],
        ],
    ];
}

function plugin_newmanagement_check_prerequisites() {
    if (version_compare(GLPI_VERSION, PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION, 'lt')) {
        echo 'Este plugin requer GLPI >= ' . PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION;
        return false;
    }
    return true;
}

function plugin_newmanagement_check_config() {
    return true;
}
