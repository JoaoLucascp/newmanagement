<?php

/**
 * Newmanagement - Plugin GLPI
 * Sistema completo de Gestão de Documentação de Empresas
 *
 * @author   João Lucas
 * @license  MIT
 */

// -------------------------------------------------------
// Constantes centralizadas — altere APENAS aqui ao versionar
// -------------------------------------------------------
define('PLUGIN_NEWMANAGEMENT_VERSION',         '1.0.0');
define('PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION', '11.0.0');
define('PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION', '11.0.99');
define('PLUGIN_NEWMANAGEMENT_MIN_PHP_VERSION',  '8.1');

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

    // -------------------------------------------------------
    // [FIX E1/E4] Registra o namespace Twig @newmanagement
    //
    // PROBLEMA ANTERIOR: o registro estava dentro de post_init,
    // que é executado APÓS o TemplateRenderer singleton já ter
    // sido criado. Em requisições AJAX (ajax/common.tabs.php),
    // o display() era chamado antes do post_init rodar, causando:
    //   - LoaderError: "no registered paths for namespace GlpiPlugin"
    //   - SyntaxError: "Unknown trans filter" (erro encadeado)
    //
    // SOLUÇÃO: registrar o namespace diretamente no plugin_init,
    // garantindo que @newmanagement existe antes de qualquer
    // chamada a TemplateRenderer::display().
    // O TemplateRenderer é lazy — getEnvironment() só instancia
    // o Twig se ainda não foi criado, então chamá-lo aqui é seguro.
    // -------------------------------------------------------
    $tpl_dir = Plugin::getPhpDir('newmanagement') . '/templates';
    if (is_dir($tpl_dir)) {
        \Glpi\Application\View\TemplateRenderer::getInstance()
            ->getEnvironment()
            ->getLoader()
            ->addPath($tpl_dir, 'newmanagement');
    }

    // Registra as classes PSR-4
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Company::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Ipbx::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\Chatbot::class);
    \Plugin::registerClass(\GlpiPlugin\Newmanagement\FixedLine::class);

    // Task — entidade standalone + aba na ficha de Empresa
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\Task::class,
        ['addtabon' => [\GlpiPlugin\Newmanagement\Company::class]]
    );

    // Classes filhas do IPBX On-Premise — exibidas como abas na ficha do IPBX
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\IpbxExtension::class,
        ['addtabon' => [\GlpiPlugin\Newmanagement\Ipbx::class]]
    );
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\IpbxDevice::class,
        ['addtabon' => [\GlpiPlugin\Newmanagement\Ipbx::class]]
    );
    \Plugin::registerClass(
        \GlpiPlugin\Newmanagement\IpbxNetwork::class,
        ['addtabon' => [\GlpiPlugin\Newmanagement\Ipbx::class]]
    );

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
                'min' => PLUGIN_NEWMANAGEMENT_MIN_PHP_VERSION,
            ],
        ],
    ];
}

/**
 * Verifica pré-requisitos de versão do GLPI e do PHP.
 * Chamada pelo GLPI antes de ativar o plugin.
 */
function plugin_newmanagement_check_prerequisites()
{
    $ok = true;

    // Verifica versão do GLPI
    if (
        version_compare(GLPI_VERSION, PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION, 'lt')
        || version_compare(GLPI_VERSION, PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION, 'gt')
    ) {
        echo sprintf(
            'Este plugin requer GLPI entre %s e %s. Versão atual: %s.',
            PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION,
            PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION,
            GLPI_VERSION
        );
        $ok = false;
    }

    // Verifica versão do PHP
    if (version_compare(PHP_VERSION, PLUGIN_NEWMANAGEMENT_MIN_PHP_VERSION, 'lt')) {
        echo sprintf(
            'Este plugin requer PHP %s ou superior. Versão atual: %s.',
            PLUGIN_NEWMANAGEMENT_MIN_PHP_VERSION,
            PHP_VERSION
        );
        $ok = false;
    }

    return $ok;
}

/**
 * Verifica configuração
 */
function plugin_newmanagement_check_config($verbose = false)
{
    return true;
}
